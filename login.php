<?php 
require_once('actions/db.php');
session_start();
$user = loginUser($_POST['username'], $_POST['password']);
if ($user) {
    $_SESSION['user'] = $user;
    header('Location: index.php');
    exit;
} else {
    echo "Invalid username or password";
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <form action="login_action.php" method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <br>
        <input type="submit" value="Login">
    </form>
</body>
</html>