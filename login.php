<?php
// ============================================================
//  login.php — Authentication Entry Point
//  Prototype Bank
// ============================================================
session_start();
require_once 'config/db.php';

// Already logged in → go to dashboard
if (!empty($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT username, password, permissions
             FROM users
             WHERE username = ? AND is_deleted = 0
             LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $res  = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($user && $user['password'] === md5($password)) {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            $_SESSION['username']    = $user['username'];
            $_SESSION['permissions'] = (int)$user['permissions'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid Username/Password!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Prototype Bank — Secure login portal for bank management.">
    <title>Sign In — Prototype Bank</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Huninn&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
</head>
<body>

<div class="login-page">
    <!-- Background decoration -->
    <div class="login-deco" style="width:400px;height:400px;top:-120px;left:-120px;"></div>
    <div class="login-deco" style="width:300px;height:300px;bottom:-80px;right:-80px;"></div>
    <div class="login-deco" style="width:180px;height:180px;top:50%;right:18%;opacity:.4;"></div>

    <div class="login-card">

        <!-- Green header -->
        <div class="login-head">
            <div class="login-logo-box">PB</div>
            <div class="login-title">Prototype Bank</div>
            <div class="login-subtitle">Management System — Sign In to continue</div>
        </div>

        <!-- Form -->
        <div class="login-body">

            <?php if ($error !== ''): ?>
            <div class="alert alert-error" id="loginError">
                <span>⚠</span>
                <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" autocomplete="off" id="loginForm">

                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="Enter your username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:8px">
                    Sign In →
                </button>
            </form>

            <p class="text-center text-muted mt-4" style="font-size:12px">
                Prototype Bank v1.0 &nbsp;·&nbsp; Secure Management System
            </p>
        </div>
    </div>
</div>

</body>
</html>
