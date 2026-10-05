<?php
require_once('actions/db.php');
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $user = loginUser($_POST['username'] ?? '', $_POST['password'] ?? '');
    if ($user) {
        $_SESSION['user'] = $user;
        $_SESSION['user_id'] = $user['id'];
        header('Location: index.php');
        exit;
    }

    $error = 'Invalid username or password.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card">
            <p class="brand"><span class="brand-mark" aria-hidden="true">B</span>Blog Page</p>
            <p class="eyebrow">Welcome back</p>
            <h1>Login to your account</h1>
            <?php if (($_GET['session'] ?? '') === 'expired'): ?>
                <p class="notice" role="alert">Your login session was no longer valid. Please log in again.</p>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <p class="notice" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <form action="login.php" method="post" class="auth-form">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <button type="submit" class="primary-button" name="login" value="1">Login</button>
            </form>
            <p class="auth-switch">Don't have an account? <a href="register.php">Register</a></p>
        </section>
    </main>
</body>
</html>