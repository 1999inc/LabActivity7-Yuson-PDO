<?php
session_start();
require 'actions/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {
        $user = createUser($username, $password);
        if ($user) {
            $_SESSION['user'] = $user;
            $_SESSION['user_id'] = $user['id'];
            header('Location: index.php');
            exit;
        }

        $error = 'Registration failed. Please try a different username.';
    } else {
        $error = 'Username and password are required.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card">
            <p class="brand"><span class="brand-mark" aria-hidden="true">B</span>Blog Page</p>
            <p class="eyebrow">Join the conversation</p>
            <h1>Register your account</h1>
            <?php if ($error !== ''): ?>
                <p class="notice" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <form action="register.php" method="post" class="auth-form">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <button type="submit" class="primary-button" name="register" value="1">Register</button>
            </form>
            <p class="auth-switch">Already have an account? <a href="login.php">Login</a></p>
        </section>
    </main>
</body>
</html>