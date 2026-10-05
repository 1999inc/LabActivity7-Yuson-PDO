<?php 
$pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=blog;charset=utf8mb4", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

/*
* Table: Users
* Columns: id (int, primary key, auto-increment), username (varchar), password (varchar)
*/
function createUser (string $username, string $password) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        $statement = $pdo->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
        $statement->bindValue(':username', $username);
        $statement->bindValue(':password', password_hash($password, PASSWORD_DEFAULT));
        $statement->execute();

        $userId = $pdo->lastInsertId();
        $userStatement = $pdo->prepare("SELECT id, username FROM users WHERE id = :id");
        $userStatement->bindValue(':id', $userId);
        $userStatement->execute();

        $pdo->commit();
        return $userStatement->fetch();
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
    }

    return $user;
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

/*
* Table: Posts
* Columns: id (int, primary key, auto-increment), user_id (int, foreign key referencing Users.id),
* title (varchar), content (text), edited (boolean, default false),
*/

function getPosts () {
    global $pdo;
    $statement = $pdo->query("SELECT posts.*, users.username FROM posts JOIN users ON users.id = posts.user_id ORDER BY posts.id DESC");
    return $statement->fetchAll();
}

function getComments (int $postId) {
    global $pdo;
    $statement = $pdo->prepare(
        'SELECT comments.*, users.username
         FROM comments
         JOIN users ON users.id = comments.user_id
         WHERE comments.post_id = :postId
         ORDER BY comments.id ASC'
    );
    $statement->execute([':postId' => $postId]);
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
        $post = $statement->execute();
        $pdo->commit();
        return $post;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function editPost (int $postId, int $userId, string $title, string $content) {
    global $pdo;
    try {
        $postCheck = $pdo->prepare('SELECT id FROM posts WHERE id = :postId AND user_id = :userId');
        $postCheck->execute([':postId' => $postId, ':userId' => $userId]);
        if (!$postCheck->fetch()) {
            throw new Exception('Post not found or you do not have permission to edit this post');
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE posts SET title = :title, content = :content, edited = 1 WHERE id = :postId AND user_id = :userId');
        $stmt->execute([
            ':title' => $title,
            ':content' => $content,
            ':postId' => $postId,
            ':userId' => $userId,
        ]);
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

/*
* Table: Comments
* Columns: id (int, primary key, auto-increment), post_id (int, foreign key referencing Posts.id),
* user_id (int, foreign key referencing Users.id), content (text), edited (boolean, default false),
*/
function createComment (string $content, int $postId, int $userId) {
    global $pdo;
    try {
        $postCheck = $pdo->prepare('SELECT id FROM posts WHERE id = :postId');
        $postCheck->execute([':postId' => $postId]);
        if (!$postCheck->fetch()) {
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
        $commentCheck = $pdo->prepare('SELECT id FROM comments WHERE id = :commentId AND user_id = :userId');
        $commentCheck->execute([':commentId' => $commentId, ':userId' => $userId]);
        if (!$commentCheck->fetch()) {
            throw new Exception('Comment not found or you do not have permission to edit this comment');
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE comments SET content = :content, edited = 1 WHERE id = :commentId AND user_id = :userId');
        $stmt->execute([
            ':content' => $content,
            ':commentId' => $commentId,
            ':userId' => $userId,
        ]);
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}


/*
* Table: Users
* Columns: id (int, primary key, auto-increment), username (varchar), password (varchar)
*/

/*
* Table: Posts
* Columns: id (int, primary key, auto-increment), user_id (int, foreign key referencing Users.id),
* title (varchar), content (text), edited (boolean, default false),
*/

/*
* Table: Comments
* Columns: id (int, primary key, auto-increment), post_id (int, foreign key referencing Posts.id),
* user_id (int, foreign key referencing Users.id), content (text), edited (boolean, default false),
*/

?>