<?php
session_start();
require 'actions/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$userCheck = $pdo->prepare('SELECT id FROM users WHERE id = :userId');
$userCheck->execute([':userId' => $userId]);
if (!$userCheck->fetch()) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php?session=expired');
    exit;
}

$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['logout'])) {
        logoutUser();
    }

    $action = $_POST['action'] ?? '';
    $postId = filter_var($_POST['post_id'] ?? null, FILTER_VALIDATE_INT);
    $commentId = filter_var($_POST['comment_id'] ?? null, FILTER_VALIDATE_INT);

    if ($action === 'createPost' || $action === 'editPost') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if ($title === '' || $content === '') {
            $notice = 'Both a title and post content are required.';
        } elseif ($action === 'createPost') {
            try {
                if (createPost($title, $content, $userId)) {
                    header('Location: index.php');
                    exit;
                }
                $notice = 'The post was not saved. Please try again.';
            } catch (Exception $e) {
                error_log('createPost failed: ' . $e->getMessage());
                $notice = 'The post could not be saved. Please try again.';
            }
        } elseif ($postId !== false && $postId > 0) {
            try {
                editPost($postId, $userId, $title, $content);
                header('Location: index.php');
                exit;
            } catch (Exception $e) {
                error_log($e->getMessage());
                $notice = 'The post could not be edited. Make sure it is your post and try again.';
            }
        } else {
            $notice = 'A valid post is required to edit.';
        }
    } elseif ($action === 'createComment' || $action === 'editComment') {
        $comment = trim($_POST['comment'] ?? '');
        if ($comment === '') {
            $notice = 'Comment content is required.';
        } elseif ($postId === false || $postId < 1) {
            $notice = 'A valid post is required to comment.';
        } else {
            try {
                if ($action === 'createComment') {
                    createComment($comment, $postId, $userId);
                } elseif ($commentId !== false && $commentId > 0) {
                    editComment($commentId, $userId, $comment);
                } else {
                    throw new InvalidArgumentException('A valid comment is required to edit.');
                }
                header('Location: index.php');
                exit;
            } catch (Exception $e) {
                error_log($e->getMessage());
                $notice = $action === 'createComment'
                    ? 'The comment could not be added. Please try again.'
                    : 'The comment could not be edited. Make sure it is your comment and try again.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'getPosts') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(getPosts(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    exit;
}

$posts = getPosts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f5f6f8">
    <title>Blog Page</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="page-shell">
        <header class="topbar">
            <p class="brand"><span class="brand-mark" aria-hidden="true">B</span>Blog Page</p>
            <form method="post" class="logout-form">
                <button type="submit" class="quiet-button" name="logout" value="1">Log out</button>
            </form>
        </header>

        <main>
            <section class="intro" aria-labelledby="page-title">
                <p class="eyebrow">A space to share</p>
                <h1 id="page-title">Blog Stuff</h1>
                <p class="intro-copy">Thoughts, stories, and ideas from the community.</p>
            </section>

            <?php if ($notice !== ''): ?>
                <p class="notice" role="alert"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <section class="composer" aria-label="Create a post">
                <button type="button" class="composer-toggle" id="createPostButton" aria-expanded="false" aria-controls="createPostForm">＋ &nbsp; Write something new</button>
                <form method="post" class="composer-form" id="createPostForm" hidden>
                    <input type="hidden" name="action" value="createPost">
                    <label for="newPostTitle">Title</label>
                    <input type="text" id="newPostTitle" name="title" placeholder="Give your post a title" required>
                    <label for="newPostContent">Your story</label>
                    <textarea id="newPostContent" name="content" placeholder="What's on your mind?" required></textarea>
                    <div class="form-actions"><button type="submit" class="primary-button">Publish post</button></div>
                </form>
            </section>

            <section aria-labelledby="feed-title">
                <div class="feed-heading">
                    <h2 id="feed-title">Recent posts</h2>
                    <span class="post-count"><?= count($posts) ?> <?= count($posts) === 1 ? 'post' : 'posts' ?></span>
                </div>
                <?php if ($posts === []): ?>
                    <p class="empty-state">No posts yet. Start the conversation with the first one.</p>
                <?php else: ?>
                    <div class="feed" id="postsContainer">
                        <?php foreach ($posts as $post): ?>
                            <?php
                                $postId = (int) $post['id'];
                                $comments = getComments($postId);
                            ?>
                            <article class="post-card">
                                <div class="post-meta">
                                    <span class="author"><?= htmlspecialchars($post['username'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if ((int) $post['edited'] === 1): ?>
                                        <span class="edited-label">Edited</span>
                                    <?php endif; ?>
                                </div>
                                <h2><?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="post-content"><?= nl2br(htmlspecialchars($post['content'], ENT_QUOTES, 'UTF-8')) ?></p>

                                <div class="post-actions">
                                    <button type="button" class="quiet-button" data-toggle-target="commentForm-<?= $postId ?>" aria-expanded="false">Comment</button>
                                    <?php if ((int) $post['user_id'] === $userId): ?>
                                        <button type="button" class="quiet-button" data-toggle-target="editForm-<?= $postId ?>" aria-expanded="false">Edit post</button>
                                    <?php endif; ?>
                                </div>

                                <form method="post" class="inline-form editor-panel" id="commentForm-<?= $postId ?>" hidden>
                                    <input type="hidden" name="action" value="createComment">
                                    <input type="hidden" name="post_id" value="<?= $postId ?>">
                                    <label for="newComment-<?= $postId ?>">Add a comment</label>
                                    <textarea id="newComment-<?= $postId ?>" name="comment" placeholder="Write a thoughtful reply..." required></textarea>
                                    <div class="form-actions"><button type="submit" class="primary-button">Add comment</button></div>
                                </form>

                                <?php if ((int) $post['user_id'] === $userId): ?>
                                    <form method="post" class="inline-form editor-panel" id="editForm-<?= $postId ?>" hidden>
                                        <input type="hidden" name="action" value="editPost">
                                        <input type="hidden" name="post_id" value="<?= $postId ?>">
                                        <label for="title-<?= $postId ?>">Title</label>
                                        <input type="text" id="title-<?= $postId ?>" name="title" value="<?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>" required>
                                        <label for="content-<?= $postId ?>">Content</label>
                                        <textarea id="content-<?= $postId ?>" name="content" required><?= htmlspecialchars($post['content'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                        <div class="form-actions"><button type="submit" class="primary-button">Save changes</button></div>
                                    </form>
                                <?php endif; ?>

                                <section class="comments" aria-label="Comments">
                                    <h3 class="comments-heading">Comments · <?= count($comments) ?></h3>
                                    <?php foreach ($comments as $comment): ?>
                                        <?php $commentId = (int) $comment['id']; ?>
                                        <article class="comment-card">
                                            <div class="comment-meta">
                                                <span class="author"><?= htmlspecialchars($comment['username'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php if ((int) $comment['edited'] === 1): ?>
                                                    <span class="edited-label">Edited</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="comment-content"><?= nl2br(htmlspecialchars($comment['content'], ENT_QUOTES, 'UTF-8')) ?></p>
                                            <?php if ((int) $comment['user_id'] === $userId): ?>
                                                <div class="comment-edit">
                                                    <button type="button" class="quiet-button" data-toggle-target="editCommentForm-<?= $commentId ?>" aria-expanded="false">Edit comment</button>
                                                </div>
                                                <form method="post" class="inline-form editor-panel" id="editCommentForm-<?= $commentId ?>" hidden>
                                                    <input type="hidden" name="action" value="editComment">
                                                    <input type="hidden" name="post_id" value="<?= $postId ?>">
                                                    <input type="hidden" name="comment_id" value="<?= $commentId ?>">
                                                    <label for="commentText-<?= $commentId ?>">Edit comment</label>
                                                    <textarea id="commentText-<?= $commentId ?>" name="comment" required><?= htmlspecialchars($comment['content'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                    <div class="form-actions"><button type="submit" class="primary-button">Save comment</button></div>
                                                </form>
                                            <?php endif; ?>
                                        </article>
                                    <?php endforeach; ?>
                                </section>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-toggle-target]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const form = document.getElementById(button.dataset.toggleTarget);
                    const isExpanded = button.getAttribute('aria-expanded') === 'true';
                    form.hidden = isExpanded;
                    button.setAttribute('aria-expanded', String(!isExpanded));
                });
            });

            const createPostButton = document.getElementById('createPostButton');
            const createPostForm = document.getElementById('createPostForm');
            createPostButton.addEventListener('click', function () {
                const isExpanded = createPostButton.getAttribute('aria-expanded') === 'true';
                createPostForm.hidden = isExpanded;
                createPostButton.setAttribute('aria-expanded', String(!isExpanded));
                if (!isExpanded) {
                    document.getElementById('newPostTitle').focus();
                }
            });
        });
    </script>
</body>
</html>
