<?php
header('Content-Type: application/json');
require_once 'auth.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = get_db_conn();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// --- Router ---
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    if ($action === 'projects') {
        $res = mysqli_query($conn, "SELECT * FROM projects ORDER BY name ASC");
        $projects = [];
        while ($row = mysqli_fetch_assoc($res)) { $projects[] = $row; }
        echo json_encode($projects);
    } 
    elseif ($action === 'endpoints') {
        $project_id = $_GET['project_id'] ?? null;
        $sql = "SELECT e.*, u.username as updated_by_name 
                FROM endpoints e 
                LEFT JOIN users u ON e.last_updated_by = u.id";
        if ($project_id) {
            $sql .= " WHERE project_id = " . intval($project_id);
        }
        $sql .= " ORDER BY category ASC, name ASC";
        
        $res = mysqli_query($conn, $sql);
        $endpoints = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $row['params'] = json_decode($row['params'], true) ?: new stdClass();
            $row['headers'] = json_decode($row['headers'], true) ?: new stdClass();
            $endpoints[] = $row;
        }
        echo json_encode($endpoints);
    }
    elseif ($action === 'users') {
        if ($user_role !== 'superadmin') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
            exit;
        }
        $res = mysqli_query($conn, "SELECT id, username, role, created_at FROM users ORDER BY username ASC");
        $users = [];
        while ($row = mysqli_fetch_assoc($res)) { $users[] = $row; }
        echo json_encode($users);
    }
} 
elseif ($method === 'POST') {
    // Role Check
    if ($user_role === 'viewer') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Viewer role cannot perform changes.']);
        exit;
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if ($action === 'create_project') {
        $name = $data['name'] ?? '';
        $desc = $data['description'] ?? '';
        
        $stmt = mysqli_prepare($conn, "INSERT INTO projects (name, description) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $name, $desc);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success', 'id' => mysqli_insert_id($conn)]);
            // Log Action
            mysqli_query($conn, "INSERT INTO audit_logs (user_id, action, details) VALUES ($user_id, 'CREATE_PROJECT', 'Created project: $name')");
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
    } 
    elseif ($action === 'update_project') {
        $id = $data['id'] ?? null;
        $name = $data['name'] ?? '';
        $desc = $data['description'] ?? '';
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Project ID required']);
            exit;
        }

        $stmt = mysqli_prepare($conn, "UPDATE projects SET name=?, description=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ssi", $name, $desc, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success']);
            // Log Action
            mysqli_query($conn, "INSERT INTO audit_logs (user_id, action, details) VALUES ($user_id, 'UPDATE_PROJECT', 'Updated project: $name (ID: $id)')");
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
    }
    elseif ($action === 'delete_project') {
        if ($user_role !== 'superadmin') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Only superadmin can delete collections.']);
            exit;
        }

        $id = $data['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Collection ID required']);
            exit;
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM projects WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success']);
            mysqli_query($conn, "INSERT INTO audit_logs (user_id, action, details) VALUES ($user_id, 'DELETE_PROJECT', 'Deleted collection ID: $id')");
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
    }
    elseif ($action === 'save_endpoint') {
        $id = $data['id'] ?? null;
        $project_id = $data['project_id'];
        $name = $data['name'];
        $method_type = $data['method'];
        $url = $data['url'];
        $category = $data['category'] ?: 'Default';
        $params = json_encode($data['params']);
        $headers = json_encode($data['headers']);
        $body_type = $data['bodyType'];
        $body = is_array($data['body']) ? json_encode($data['body']) : $data['body'];

        if ($id) {
            $stmt = mysqli_prepare($conn, "UPDATE endpoints SET project_id=?, name=?, method=?, url=?, category=?, params=?, headers=?, body_type=?, body=?, last_updated_by=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "issssssssii", $project_id, $name, $method_type, $url, $category, $params, $headers, $body_type, $body, $user_id, $id);
            $action_log = "UPDATE_ENDPOINT";
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO endpoints (project_id, name, method, url, category, params, headers, body_type, body, last_updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "issssssssi", $project_id, $name, $method_type, $url, $category, $params, $headers, $body_type, $body, $user_id);
            $action_log = "CREATE_ENDPOINT";
        }

        if (mysqli_stmt_execute($stmt)) {
            $new_id = $id ? $id : mysqli_insert_id($conn);
            echo json_encode(['status' => 'success', 'id' => $new_id]);
            // Log Action
            mysqli_query($conn, "INSERT INTO audit_logs (user_id, action, endpoint_id, details) VALUES ($user_id, '$action_log', $new_id, 'Saved endpoint: $name')");
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
    }
    elseif ($action === 'delete_endpoint') {
        if ($user_role !== 'superadmin') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Only superadmin can delete endpoints.']);
            exit;
        }

        $id = $data['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Endpoint ID required']);
            exit;
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM endpoints WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success']);
            mysqli_query($conn, "INSERT INTO audit_logs (user_id, action, endpoint_id, details) VALUES ($user_id, 'DELETE_ENDPOINT', $id, 'Deleted endpoint ID: $id')");
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
    }
    elseif ($action === 'rename_category') {
        $project_id = $data['project_id'] ?? null;
        $old_name = $data['old_name'] ?? null;
        $new_name = $data['new_name'] ?? null;

        if (!$project_id || !$old_name || !$new_name) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            exit;
        }

        $stmt = mysqli_prepare($conn, "UPDATE endpoints SET category=? WHERE project_id=? AND category=?");
        mysqli_stmt_bind_param($stmt, "sis", $new_name, $project_id, $old_name);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success']);
            mysqli_query($conn, "INSERT INTO audit_logs (user_id, action, details) VALUES ($user_id, 'RENAME_CATEGORY', 'Renamed category from \"$old_name\" to \"$new_name\" in collection ID: $project_id')");
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
    }
    elseif ($action === 'import_collection') {
        $collection_data = $data['collection'] ?? null;
        if (!$collection_data) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'No collection data provided']);
            exit;
        }

        $info = $collection_data['info'] ?? [];
        $col_name = $info['name'] ?? 'Imported Collection';
        $col_desc = $info['description'] ?? '';

        // Create Collection
        $stmt = mysqli_prepare($conn, "INSERT INTO projects (name, description) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $col_name, $col_desc);
        
        if (mysqli_stmt_execute($stmt)) {
            $project_id = mysqli_insert_id($conn);
            mysqli_query($conn, "INSERT INTO audit_logs (user_id, action, details) VALUES ($user_id, 'IMPORT_COLLECTION', 'Imported collection: $col_name (ID: $project_id)')");

            // Process Items
            if (!function_exists('processItems')) {
                function processItems($conn, $project_id, $items, $user_id, $category = 'Default') {
                    foreach ($items as $item) {
                        if (isset($item['item'])) {
                            // This is a folder
                            processItems($conn, $project_id, $item['item'], $user_id, $item['name']);
                        } else {
                            // This is a request
                            $name = $item['name'] ?? 'Untitled';
                            $req = $item['request'] ?? [];
                            $method = $req['method'] ?? 'GET';
                            
                            // Handle URL
                            $url = '';
                            if (is_array($req['url'] ?? null)) {
                                $url = $req['url']['raw'] ?? '';
                            } else {
                                $url = $req['url'] ?? '';
                            }

                            // Handle Params from URL
                            $params = [];
                            if (isset($req['url']['query'])) {
                                foreach ($req['url']['query'] as $q) {
                                    if (isset($q['key'])) $params[$q['key']] = $q['value'] ?? '';
                                }
                            }

                            // Handle Headers
                            $headers = [];
                            if (isset($req['header'])) {
                                foreach ($req['header'] as $h) {
                                    if (isset($h['key'])) $headers[$h['key']] = $h['value'] ?? '';
                                }
                            }

                            // Handle Body
                            $body_type = 'none';
                            $body_content = '';
                            if (isset($req['body'])) {
                                $mode = $req['body']['mode'] ?? 'none';
                                if ($mode === 'raw') {
                                    $body_type = 'raw';
                                    $body_content = $req['body']['raw'] ?? '';
                                } elseif ($mode === 'formdata') {
                                    $body_type = 'form-data';
                                    $form_data = [];
                                    foreach ($req['body']['formdata'] as $f) {
                                        if (isset($f['key'])) $form_data[$f['key']] = $f['value'] ?? '';
                                    }
                                    $body_content = json_encode($form_data);
                                }
                            }

                            $params_json = json_encode($params);
                            $headers_json = json_encode($headers);

                            $stmt = mysqli_prepare($conn, "INSERT INTO endpoints (project_id, name, method, url, category, params, headers, body_type, body, last_updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            mysqli_stmt_bind_param($stmt, "issssssssi", $project_id, $name, $method, $url, $category, $params_json, $headers_json, $body_type, $body_content, $user_id);
                            mysqli_stmt_execute($stmt);
                        }
                    }
                }
            }

            if (isset($collection_data['item'])) {
                processItems($conn, $project_id, $collection_data['item'], $user_id);
            }

            echo json_encode(['status' => 'success', 'project_id' => $project_id]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
    }
    elseif ($action === 'create_user') {
        if ($user_role !== 'superadmin') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Only superadmin can manage users.']);
            exit;
        }

        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'viewer';

        if (!$username || !$password) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Username and password required']);
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $username, $hashed_password, $role);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success']);
            mysqli_query($conn, "INSERT INTO audit_logs (user_id, action, details) VALUES ($user_id, 'CREATE_USER', 'Created user: $username with role: $role')");
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
    }
    elseif ($action === 'delete_user') {
        if ($user_role !== 'superadmin') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Only superadmin can manage users.']);
            exit;
        }

        $target_id = $data['id'] ?? null;
        if (!$target_id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'User ID required']);
            exit;
        }

        if ($target_id == $user_id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'You cannot delete yourself.']);
            exit;
        }

        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $target_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success']);
            mysqli_query($conn, "INSERT INTO audit_logs (user_id, action, details) VALUES ($user_id, 'DELETE_USER', 'Deleted user ID: $target_id')");
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
    }
    elseif ($action === 'update_user') {
        if ($user_role !== 'superadmin') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Only superadmin can manage users.']);
            exit;
        }

        $target_id = $data['id'] ?? null;
        $new_password = $data['password'] ?? '';

        if (!$target_id || !$new_password) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'User ID and new password required']);
            exit;
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $target_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success']);
            mysqli_query($conn, "INSERT INTO audit_logs (user_id, action, details) VALUES ($user_id, 'UPDATE_USER_PASSWORD', 'Updated password for user ID: $target_id')");
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
    }
    elseif ($action === 'update_profile') {
        $old_password = $data['old_password'] ?? '';
        $new_password = $data['new_password'] ?? '';

        if (!$old_password || !$new_password) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Current and new password required']);
            exit;
        }

        // Verify current password
        $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($res);

        if (!$user || !password_verify($old_password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Incorrect current password']);
            exit;
        }

        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success']);
            mysqli_query($conn, "INSERT INTO audit_logs (user_id, action, details) VALUES ($user_id, 'UPDATE_PROFILE_PASSWORD', 'User updated their own password')");
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
    }
    elseif ($action === 'send_request') {
        $url = $data['url'] ?? '';
        $method_req = $data['method'] ?? 'GET';
        $headers_req = $data['headers'] ?? [];
        $body_req = $data['body'] ?? '';

        if (!$url) {
            echo json_encode(['status' => 'error', 'message' => 'URL is required']);
            exit;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method_req);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $formatted_headers = [];
        foreach ($headers_req as $k => $v) {
            $formatted_headers[] = "$k: $v";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $formatted_headers);
        
        if ($method_req !== 'GET' && $body_req) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($body_req) ? json_encode($body_req) : $body_req);
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $time = curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000; // ms
        $size = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);

        if (curl_errno($ch)) {
            echo json_encode(['status' => 'error', 'message' => curl_error($ch)]);
        } else {
            echo json_encode([
                'status' => 'success',
                'data' => $response,
                'http_code' => $http_code,
                'content_type' => $content_type,
                'time' => round($time),
                'size' => $size
            ]);
        }
        curl_close($ch);
        exit;
    }
}
?>
