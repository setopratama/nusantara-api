<?php
session_start();
require_once 'config.php';

function get_db_conn() {
    $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }
    return $conn;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

function login_user($username, $password) {
    $conn = get_db_conn();
    $stmt = mysqli_prepare($conn, "SELECT id, username, password, role FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

function logout_user() {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
