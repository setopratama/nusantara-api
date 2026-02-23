<?php
require_once 'auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (login_user($username, $password)) {
        header("Location: index.php");
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}

if (is_logged_in()) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Nusantara API</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/modern-ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            background: radial-gradient(circle at top right, #1e293b, #0f172a);
        }
        .login-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 16px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header i {
            font-size: 3rem;
            color: var(--accent-primary);
            margin-bottom: 15px;
        }
        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }
        .error-msg {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            padding: 10px;
            border-radius: 6px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            border: 1px solid rgba(239, 68, 68, 0.2);
            text-align: center;
        }
        .login-footer {
            margin-top: 20px;
            text-align: center;
            color: var(--text-dim);
            font-size: 0.8rem;
        }
        .login-btn {
            width: 100%;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-bolt"></i>
            <h1>Nusantara API</h1>
        </div>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="admin">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary login-btn">Sign In</button>
        </form>

        <div class="login-footer">
            <p>Default access: <b>admin / admin123</b></p>
        </div>
    </div>
</body>
</html>
