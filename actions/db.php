<?php 
$pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=blog;charset=utf8mb4", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

function createUser (string $username, string $password) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        $statement = $pdo->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
        $statement->bindValue(':username', $username);
        $statement->bindValue(':password', password_hash($password, PASSWORD_DEFAULT));
        $statement->execute();
        $pdo->commit(); 
        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return false;
    }
}

function loginUser (string $username, string $password) {
    global $pdo;
    $statement = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $statement->bindValue(':username', $username);
    $statement->execute();
    $user = $statement->fetch();
    if (!$user || !password_verify($password, $user['password'])) {
        return false;
        throw new Exception('Invalid username or password');
    }
    return true;
}

function logoutUser () {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

function getPosts () {
    global $pdo;
    $statement = $pdo->query("SELECT * FROM posts");
    return $statement->fetchAll();
}

function createPost (string $title, string $content, int $userId) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        $statement = $pdo->prepare("INSERT INTO posts (title, content, user_id) VALUES (:title, :content, :user_id)");
        $statement->bindValue(':title', $title);
        $statement->bindValue(':content', $content);
        $statement->bindValue(':user_id', $userId);
        $statement->execute();
        $pdo->commit(); 
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function editPost (int $postId, int $userId, string $title, string $content) {
    global $pdo;
    try {
        $postCheck = $pdo->prepare('SELECT * FROM posts WHERE id = :postId AND user_id = :userId')
            ->execute([':postId' => $postId, ':userId' => $userId]);
        if (!$postCheck) {
            throw new Exception('Post not found or you do not have permission to edit this post');
        }
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE posts SET title = :title, content = :content WHERE id = :postId AND user_id = :userId');
        $stmt->execute([':title' => $title, ':content' => $content]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function createComment (string $content, int $postId, int $userId) {
    global $pdo;
    try {
        $postCheck = $pdo->prepare('SELECT * FROM posts WHERE id = :postId')
            ->execute([':postId' => $postId]);
        if (!$postCheck) {
            throw new Exception('Post not found');
        }
        $pdo->beginTransaction();
        $statement = $pdo->prepare("INSERT INTO comments (content, post_id, user_id) VALUES (:content, :post_id, :user_id)");
        $statement->bindValue(':content', $content);
        $statement->bindValue(':post_id', $postId);
        $statement->bindValue(':user_id', $userId);
        $statement->execute();
        $pdo->commit(); 
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function editComment (int $commentId, int $userId, string $content) {
    global $pdo;
    try {
        $commentCheck = $pdo->prepare('SELECT * FROM comments WHERE id = :commentId AND user_id = :userId')
            ->execute([':commentId' => $commentId, ':userId' => $userId]);
        if (!$commentCheck) {
            throw new Exception('Comment not found or you do not have permission to edit this comment');
        }
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE comments SET content = :content WHERE id = :commentId AND user_id = :userId');
        $stmt->execute([':content' => $content]);
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}


?>