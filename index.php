<?php
session_start();
require ('actions/db.php');


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
    }
    
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    logoutUser();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['content'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $userId = $_SESSION['user_id'];
    createPost($title, $content, $userId);
}



?>

<!DOCTYPE html>
<html lang="en">

<script>
    document.addEventListener('DOMContentLoaded', function() {
        fetch('index.php?action=getPosts')
            .then(response => response.json())
            .then(posts => {
                const postsContainer = document.getElementById('postsContainer');
                posts.forEach(post => {
                    const postElement = document.createElement('div');
                    postElement.innerHTML = `<h2>${post.title}</h2><p>${post.content}</p>`;
                    postsContainer.appendChild(postElement);
                });
            })
            .catch(error => console.error('Error fetching posts:', error));
    });
</script>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
</head>
<body>
    <h1>Blog Page</h1>
    <div id="postsContainer"></div>
    <form method="post">
        <button type="submit" name="logout" value="1">Log out</button>
    </form>
</body>
</html>