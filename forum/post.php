<?php
require_once '../config/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit;
}

// Check if post_id is provided
if (!isset($_GET['post_id'])) {
    $_SESSION['error'] = "Post not specified";
    header("Location: ../dashboard/forums.php");
    exit;
}

$post_id = $_GET['post_id'];

// Get post details
$query = "SELECT fp.*, 
                 f.title AS forum_title,
                 f.forum_id,
                 CONCAT(COALESCE(u.user_first_name, s.stud_first_name), ' ', COALESCE(u.user_last_name, s.stud_last_name)) AS poster_name,
                 COALESCE(u.picture_file, s.profile_picture) AS poster_picture
          FROM forum_post fp
          JOIN forum f ON fp.forum_id = f.forum_id
          LEFT JOIN actor a ON fp.poster_id = a.actor_id
          LEFT JOIN user u ON (a.entity_type = 'user' AND a.entity_id = u.user_id)
          LEFT JOIN student s ON (a.entity_type = 'student' AND a.entity_id = s.stud_id)
          WHERE fp.post_id = ? AND fp.deleted_at IS NULL";
$stmt = $conn->prepare($query);
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    $_SESSION['error'] = "Post not found";
    header("Location: forum.php");
    exit;
}

// Increment view count
$query = "UPDATE forum_post SET view_count = view_count + 1 WHERE post_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$post_id]);

// Get comments for this post
$query = "SELECT fc.*, 
                 CONCAT(COALESCE(u.user_first_name, s.stud_first_name), ' ', COALESCE(u.user_last_name, s.stud_last_name)) AS commenter_name,
                 COALESCE(u.picture_file, s.profile_picture) AS commenter_picture
          FROM forum_comment fc
          LEFT JOIN actor a ON fc.commenter_id = a.actor_id
          LEFT JOIN user u ON (a.entity_type = 'user' AND a.entity_id = u.user_id)
          LEFT JOIN student s ON (a.entity_type = 'student' AND a.entity_id = s.stud_id)
          WHERE fc.post_id = ? AND fc.deleted_at IS NULL
          ORDER BY fc.commented_at ASC";
$stmt = $conn->prepare($query);
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize current user data
if (isset($_SESSION['user_id'])) {
    $currentUser = [
        'entity_type' => 'user',
        'entity_id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_first_name'] ?? 'User'
    ];
} else {
    $currentUser = [
        'entity_type' => 'student',
        'entity_id' => $_SESSION['stud_id'],
        'name' => $_SESSION['stud_first_name'] ?? 'Student'
    ];
}

// Get actor ID
$query = "SELECT actor_id FROM actor WHERE entity_type = ? AND entity_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$currentUser['entity_type'], $currentUser['entity_id']]);
$actor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$actor) {
    // Create actor record if it doesn't exist
    $query = "INSERT INTO actor (entity_type, entity_id) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$currentUser['entity_type'], $currentUser['entity_id']]);
    $currentUser['actor_id'] = $conn->lastInsertId();
} else {
    $currentUser['actor_id'] = $actor['actor_id'];
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = trim($_POST['comment']);
    
    if (!empty($comment)) {
        $query = "INSERT INTO forum_comment (post_id, commenter_id, content) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$post_id, $currentUser['actor_id'], $comment]);
        
        $_SESSION['success'] = "Comment added successfully!";
        header("Location: post.php?post_id=" . $post_id);
        exit;
    } else {
        $_SESSION['error'] = "Comment cannot be empty";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['post_title']); ?> - Career Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .comment {
            border-left: 3px solid #dee2e6;
            padding-left: 15px;
            margin-bottom: 20px;
        }
        .comment-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .comment-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 10px;
        }
        .comment-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .comment-author {
            font-weight: 600;
            margin-right: 10px;
        }
        .comment-date {
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/forums.php">Forums</a></li>
                        <li class="breadcrumb-item"><a href="../dashboard/forums.php?forum_id=<?php echo $post['forum_id']; ?>"><?php echo htmlspecialchars($post['forum_title']); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Post</li>
                    </ol>
                </nav>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="mb-0"><?php echo htmlspecialchars($post['post_title']); ?></h2>
                            <?php if ($post['is_pinned']): ?>
                                <span class="badge bg-primary">Pinned</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-4">
                            <div class="flex-shrink-0">
                                <div class="comment-avatar">
                                    <?php if (!empty($post['poster_picture'])): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($post['poster_picture']); ?>" alt="Poster Picture">
                                    <?php else: ?>
                                        <i class="bi bi-person-fill text-muted"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <div class="fw-bold"><?php echo htmlspecialchars($post['poster_name']); ?></div>
                                <small class="text-muted">Posted on <?php echo date('M j, Y g:i a', strtotime($post['posted_at'])); ?></small>
                            </div>
                        </div>
                        
                        <div class="post-content mb-4">
                            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button class="btn btn-sm btn-outline-primary me-2">
                                    <i class="bi bi-hand-thumbs-up"></i> Like (<?php echo $post['up_count']; ?>)
                                </button>
                                <span class="text-muted">
                                    <i class="bi bi-eye"></i> <?php echo $post['view_count']; ?> views
                                </span>
                            </div>
                            <div>
                                <?php if ($currentUser['actor_id'] == $post['poster_id']): ?>
                                    <a href="edit_post.php?post_id=<?php echo $post_id; ?>" class="btn btn-sm btn-outline-secondary me-2">Edit</a>
                                <?php endif; ?>
                                <a href="../dashboard/forums.php?forum_id=<?php echo $post['forum_id']; ?>" class="btn btn-sm btn-outline-secondary">Back to Forum</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Comments (<?php echo count($comments); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($comments) > 0): ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment mb-3">
                                    <div class="comment-header">
                                        <div class="comment-avatar">
                                            <?php if (!empty($comment['commenter_picture'])): ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($comment['commenter_picture']); ?>" alt="Commenter Picture">
                                            <?php else: ?>
                                                <i class="bi bi-person-fill text-muted"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="comment-author"><?php echo htmlspecialchars($comment['commenter_name']); ?></div>
                                        <div class="comment-date"><?php echo date('M j, Y g:i a', strtotime($comment['commented_at'])); ?></div>
                                    </div>
                                    <div class="comment-content">
                                        <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No comments yet. Be the first to comment!</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Add a Comment</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <textarea class="form-control" name="comment" rows="3" placeholder="Write your comment here..." required></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Post Comment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>