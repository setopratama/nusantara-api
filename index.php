<?php require_once 'auth.php'; require_login(); ?>
<input type="hidden" id="user-role-data" value="<?php echo $_SESSION['role']; ?>">
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nusantara API Documentation - Postman Clone</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/modern-ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dark-theme">
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-bolt"></i>
                    <span>Nusantara API</span>
                </div>
                <button id="add-endpoint-btn" class="icon-btn" title="Add New Endpoint">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="endpoint-search" placeholder="Filter endpoints...">
            </div>

            <nav class="endpoint-list" id="endpoint-list">
                <!-- Endpoints will be loaded here -->
                <div class="loading-shimmer sidebar-shimmer"></div>
            </nav>

            <!-- User Profile Quick Info -->
            <div class="user-sidebar-profile">
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                <div class="user-details">
                    <span class="uname"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <span class="urole"><?php echo ucfirst($_SESSION['role']); ?></span>
                </div>
                <a href="logout.php" class="logout-link" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <div class="breadcrumb">
                    <span>Endpoints</span>
                    <i class="fas fa-chevron-right"></i>
                    <span id="current-endpoint-name">New Request</span>
                </div>
                <div class="actions">
                    <div class="env-selector-container">
                        <select id="env-selector" class="env-select">
                            <option value="">No Environment</option>
                        </select>
                        <button class="icon-btn" id="manage-envs-btn" title="Manage Environments">
                            <i class="fas fa-cog"></i>
                        </button>
                    </div>
                    <button id="save-collection-btn" class="btn btn-secondary">
                        <i class="fas fa-save"></i> Save All
                    </button>
                    <div class="top-menu-container" style="position: relative;">
                        <button id="top-burger-btn" class="btn btn-secondary" title="More Actions">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div class="dropdown-menu hidden" id="top-burger-dropdown" style="right: 0; min-width: 200px;">
                            <div class="dropdown-item" id="import-collection-btn">
                                <i class="fas fa-file-import"></i> Import Collection
                            </div>
                            <div class="dropdown-item" id="create-project-btn">
                                <i class="fas fa-plus"></i> New Collection
                            </div>
                            <div class="dropdown-item" id="my-profile-btn">
                                <i class="fas fa-user-circle"></i> My Profile
                            </div>
                            <?php if ($_SESSION['role'] === 'superadmin'): ?>
                            <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 5px 0;">
                            <div class="dropdown-item" id="manage-users-btn">
                                <i class="fas fa-users-cog"></i> Manage Users
                            </div>
                            <div class="dropdown-item" id="view-logs-btn">
                                <i class="fas fa-history"></i> Activity Logs
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </header>

            <section class="request-builder">
                <div class="url-bar-container">
                    <div class="method-selector">
                        <select id="request-method">
                            <option value="GET">GET</option>
                            <option value="POST">POST</option>
                            <option value="PUT">PUT</option>
                            <option value="PATCH">PATCH</option>
                            <option value="DELETE">DELETE</option>
                        </select>
                    </div>
                    <input type="text" id="request-url" placeholder="https://api.example.com/v1/resource">
                    <button id="send-request-btn" class="btn btn-primary">
                        <span>Send</span>
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>

                <div class="tabs-container">
                    <div class="tabs-header">
                        <button class="tab-link active" data-tab="params">Params</button>
                        <button class="tab-link" data-tab="headers">Headers</button>
                        <button class="tab-link" data-tab="body">Body</button>
                        <button class="tab-link" data-tab="auth">Auth</button>
                    </div>
                    
                    <div class="tab-content" id="params-tab">
                        <div class="key-value-editor" id="params-editor">
                            <!-- JS will inject rows -->
                        </div>
                        <button class="add-row-btn" data-target="params-editor">+ Add Parameter</button>
                    </div>

                    <div class="tab-content hidden" id="headers-tab">
                        <div class="key-value-editor" id="headers-editor">
                            <!-- JS will inject rows -->
                        </div>
                        <button class="add-row-btn" data-target="headers-editor">+ Add Header</button>
                    </div>

                    <div class="tab-content hidden" id="body-tab">
                        <div class="body-options">
                            <label><input type="radio" name="body-type" value="none" checked> None</label>
                            <label><input type="radio" name="body-type" value="raw"> Raw (JSON)</label>
                            <label><input type="radio" name="body-type" value="form-data"> Form Data</label>
                        </div>
                        <div id="body-raw-container" class="hidden">
                            <textarea id="request-body-json" placeholder='{ "key": "value" }'></textarea>
                        </div>
                        <div id="body-form-container" class="hidden">
                            <div class="key-value-editor" id="body-form-editor"></div>
                            <button class="add-row-btn" data-target="body-form-editor">+ Add Field</button>
                        </div>
                    </div>

                    <div class="tab-content hidden" id="auth-tab">
                        <div class="auth-config">
                            <label>Auth Type</label>
                            <select id="auth-type" class="select-control">
                                <option value="none">No Auth</option>
                                <option value="bearer">Bearer Token</option>
                                <option value="basic">Basic Auth</option>
                            </select>
                            <div id="auth-bearer-fields" class="hidden mt-3">
                                <label>Token</label>
                                <input type="text" id="auth-bearer-token" placeholder="Bearer Token" class="form-control">
                            </div>
                            <div id="auth-basic-fields" class="hidden mt-3">
                                <div style="display: flex; gap: 15px;">
                                    <div style="flex: 1;">
                                        <label>Username</label>
                                        <input type="text" id="auth-basic-username" placeholder="Username" class="form-control">
                                    </div>
                                    <div style="flex: 1;">
                                        <label>Password</label>
                                        <input type="password" id="auth-basic-password" placeholder="Password" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="response-section">
                <div class="response-header">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <h3>Response</h3>
                        <label class="wrap-toggle" title="Toggle Word Wrap">
                            <input type="checkbox" id="toggle-word-wrap">
                            <span>Wrap Text</span>
                        </label>
                    </div>
                    <div id="response-meta" class="hidden">
                        <span class="status-badge" id="response-status"></span>
                        <span class="time-badge" id="response-time"></span>
                        <span class="size-badge" id="response-size"></span>
                    </div>
                </div>
                <div class="response-body-container">
                    <div id="response-output" class="json-code">Waiting for request...</div>
                </div>
            </section>
        </main>
    </div>

    <!-- Modals -->
    <div id="save-endpoint-modal" class="modal">
        <div class="modal-content">
            <h2>Save Endpoint Documentation</h2>
            <div class="form-group">
                <label>Collection</label>
                <select id="save-project-id">
                    <!-- Loaded from API -->
                </select>
            </div>
            <div class="form-group">
                <label>Name</label>
                <input type="text" id="save-name" placeholder="Get User Profile">
            </div>
            <div class="form-group">
                <label>Category/Group</label>
                <input type="text" id="save-category" list="category-list" placeholder="Users">
                <datalist id="category-list">
                    <!-- Loaded dynamically via JS -->
                </datalist>
            </div>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal('save-endpoint-modal')">Cancel</button>
                <button class="btn btn-primary" id="confirm-save-btn">Save</button>
            </div>
        </div>
    </div>

    <!-- Create/Edit Project Modal -->
    <div id="create-project-modal" class="modal">
        <div class="modal-content">
            <h2 id="project-modal-title">Create New Collection</h2>
            <input type="hidden" id="edit-project-id">
            <div class="form-group">
                <label>Collection Name</label>
                <input type="text" id="new-project-name" placeholder="E-Commerce API">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="new-project-desc" placeholder="Project description..."></textarea>
            </div>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal('create-project-modal')">Cancel</button>
                <button class="btn btn-primary" id="confirm-project-btn">Create</button>
            </div>
        </div>
    </div>

    <!-- Import Collection Modal -->
    <div id="import-collection-modal" class="modal">
        <div class="modal-content">
            <h2>Import Postman Collection</h2>
            <p style="font-size: 0.8rem; color: var(--text-dim); margin-bottom: 15px;">Upload a Postman collection JSON file (v2.0 or v2.1).</p>
            <div class="form-group">
                <label>Select JSON File</label>
                <input type="file" id="import-file-input" accept=".json" style="width:100%; color:var(--text-main);">
            </div>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal('import-collection-modal')">Cancel</button>
                <button class="btn btn-primary" id="confirm-import-btn">Import</button>
            </div>
        </div>
    </div>

    <!-- User Management Modal -->
    <div id="user-management-modal" class="modal">
        <div class="modal-content" style="width: 600px;">
            <h2>User Management</h2>
            <div class="user-list-container" style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--border-color); color: var(--text-dim);">
                            <th style="padding: 10px;">Username</th>
                            <th style="padding: 10px;">Role</th>
                            <th style="padding: 10px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="user-list-table-body">
                        <!-- Loaded from API -->
                    </tbody>
                </table>
            </div>
            
            <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 20px 0;">
            
            <h3>Add New User</h3>
            <div class="form-row" style="display: flex; gap: 10px; align-items: flex-end; margin-top: 10px;">
                <div class="form-group" style="flex: 1; margin: 0;">
                    <label>Username</label>
                    <input type="text" id="new-user-username" placeholder="john_doe">
                </div>
                <div class="form-group" style="flex: 1; margin: 0;">
                    <label>Password</label>
                    <input type="password" id="new-user-password" placeholder="••••••••">
                </div>
                <div class="form-group" style="width: 120px; margin: 0;">
                    <label>Role</label>
                    <select id="new-user-role">
                        <option value="editor">Editor</option>
                        <option value="viewer">Viewer</option>
                    </select>
                </div>
                <button class="btn btn-primary" id="confirm-add-user-btn" style="height: 38px; padding: 0 15px;">Add</button>
            </div>
            
            <div class="modal-actions" style="margin-top: 30px;">
                <button class="btn btn-secondary" onclick="closeModal('user-management-modal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="edit-user-modal" class="modal">
        <div class="modal-content" style="width: 400px;">
            <h2>Change User Password</h2>
            <input type="hidden" id="edit-user-id">
            <div class="form-group">
                <label>Username</label>
                <input type="text" id="edit-user-username" readonly style="opacity: 0.7;">
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" id="edit-user-password" placeholder="••••••••">
            </div>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal('edit-user-modal')">Cancel</button>
                <button class="btn btn-primary" id="confirm-edit-user-btn">Update Password</button>
            </div>
        </div>
    </div>

    <!-- My Profile Modal -->
    <div id="profile-modal" class="modal">
        <div class="modal-content" style="width: 400px;">
            <h2>My Profile</h2>
            <div class="form-group">
                <label>Username</label>
                <input type="text" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly style="opacity: 0.7;">
            </div>
            <div class="form-group">
                <label>Role</label>
                <input type="text" value="<?php echo ucfirst($_SESSION['role']); ?>" readonly style="opacity: 0.7;">
            </div>
            <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 20px 0;">
            <h3>Change Password</h3>
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" id="current-user-old-password" placeholder="••••••••">
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" id="current-user-new-password" placeholder="••••••••">
            </div>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal('profile-modal')">Cancel</button>
                <button class="btn btn-primary" id="confirm-profile-update-btn">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Activity Logs Modal -->
    <div id="logs-modal" class="modal">
        <div class="modal-content" style="width: 800px; max-height: 80vh; display: flex; flex-direction: column;">
            <h2>Activity Logs</h2>
            <div class="user-list-container" style="flex: 1; overflow-y: auto; margin: 15px 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid var(--border-color); color: var(--text-dim);">
                            <th style="padding: 10px;">Time</th>
                            <th style="padding: 10px;">User</th>
                            <th style="padding: 10px;">Action</th>
                            <th style="padding: 10px;">Details</th>
                        </tr>
                    </thead>
                    <tbody id="logs-table-body">
                        <!-- Loaded from API -->
                    </tbody>
                </table>
            </div>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal('logs-modal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Environments Modal -->
    <div id="envs-modal" class="modal">
        <div class="modal-content" style="width: 600px; max-height: 80vh; display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Environments</h2>
                <button class="btn btn-primary" id="add-env-btn" style="height: 35px; padding: 0 15px;">
                    <i class="fas fa-plus"></i> Add
                </button>
            </div>
            
            <div class="env-list-container" style="flex: 1; overflow-y: auto;">
                <div id="env-items-list">
                    <!-- Loaded from API -->
                </div>
            </div>

            <div id="env-edit-section" class="hidden" style="margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                <input type="hidden" id="edit-env-id">
                <div class="form-group">
                    <label>Environment Name</label>
                    <input type="text" id="edit-env-name" placeholder="e.g. Production">
                </div>
                <div class="form-group">
                    <label>Variables (Key/Value)</label>
                    <div id="env-vars-editor" class="key-value-editor">
                        <!-- Key-Value pairs -->
                    </div>
                    <button class="add-row-btn" onclick="createRow('env-vars-editor')">+ Add Variable</button>
                </div>
                <div class="modal-actions">
                    <button class="btn btn-secondary" id="cancel-env-edit-btn">Cancel</button>
                    <button class="btn btn-primary" id="save-env-btn">Save Environment</button>
                </div>
            </div>

            <div class="modal-actions" id="env-modal-main-actions">
                <button class="btn btn-secondary" onclick="closeModal('envs-modal')">Close</button>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
