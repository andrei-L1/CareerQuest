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
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --muted-text: #6c757d;
            --border-color: #e9ecef;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
        }
        
        body {
            background-color: #f5f7fb;
            color: var(--dark-text);
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
        }
        
        .forum-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .post-card {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: none;
            margin-bottom: 2.5rem;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .post-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
        }
        
        .post-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            position: relative;
        }
        
        .post-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 100%;
            height: 10px;
            background: linear-gradient(to bottom, rgba(0,0,0,0.1), transparent);
        }
        
        .post-title {
            font-weight: 700;
            margin-bottom: 0.75rem;
            font-size: 1.75rem;
            line-height: 1.3;
        }
        
        .post-meta {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .post-body {
            padding: 2rem;
            background-color: white;
        }
        
        .post-content {
            line-height: 1.7;
            font-size: 1.1rem;
            color: var(--dark-text);
        }
        
        .post-footer {
            background-color: white;
            border-top: 1px solid var(--border-color);
            padding: 1.25rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .post-actions .btn {
            margin-left: 0.75rem;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .stats {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            font-size: 0.95rem;
            color: var(--muted-text);
        }
        
        .stat-item i {
            margin-right: 6px;
            font-size: 1.1rem;
        }
        
        .avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 16px;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid rgba(255,255,255,0.2);
        }
        
        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar i {
            font-size: 1.5rem;
            color: var(--muted-text);
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .username {
            font-weight: 600;
            margin-bottom: 0;
            color: inherit;
        }
        
        .badge-pinned {
            background-color: #ffd166;
            color: #333;
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
        }
        
        .comment-card {
            border-radius: 10px;
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s ease;
        }
        
        .comment-card:hover {
            transform: translateX(5px);
            border-left: 3px solid var(--accent-color);
        }
        
        .comment-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background-color: white;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .comment-body {
            padding: 1.5rem;
            line-height: 1.6;
        }
        
        .comment-meta {
            font-size: 0.85rem;
            color: var(--muted-text);
        }
        
        .section-title {
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
            color: var(--accent-color);
        }
        
        .comment-form {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            margin-top: 2rem;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            border-color: var(--accent-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            border-radius: 8px;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-outline-secondary {
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
        }
        
        .like-btn.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 0.75rem 0;
            margin-bottom: 2rem;
        }
        
        .breadcrumb-item a {
            color: var(--muted-text);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .breadcrumb-item a:hover {
            color: var(--primary-color);
        }
        
        .alert {
            border-radius: 8px;
            padding: 1rem 1.5rem;
        }
        
        @media (max-width: 768px) {
            .post-title {
                font-size: 1.5rem;
            }
            
            .post-body, .post-footer {
                padding: 1.5rem;
            }
            
            .post-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .stats {
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="forum-container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard/forums.php"><i class="bi bi-arrow-left"></i> Forums</a></li>
                    <li class="breadcrumb-item"><a href="../dashboard/forums.php?forum_id=<?php echo $post['forum_id']; ?>"><?php echo htmlspecialchars($post['forum_title']); ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Thread</li>
                </ol>
            </nav>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Main Post Card -->
            <div class="post-card card">
                <div class="post-header card-header">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h1 class="post-title"><?php echo htmlspecialchars($post['post_title']); ?></h1>
                            <div class="user-info">
                                <div class="avatar">
                                    <?php if (!empty($post['poster_picture'])): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($post['poster_picture']); ?>" alt="Poster Picture">
                                    <?php else: ?>
                                        <i class="bi bi-person-fill"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="username mb-0 text-white"><?php echo htmlspecialchars($post['poster_name']); ?></p>
                                    <span class="post-meta">Posted <?php echo date('M j, Y \a\t g:i a', strtotime($post['posted_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php if ($post['is_pinned']): ?>
                            <span class="badge-pinned"><i class="bi bi-pin-angle"></i> Pinned</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="post-body card-body">
                    <div class="post-content">
                        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                    </div>
                </div>
                
                <div class="post-footer card-footer">
                    <div class="stats">
                        <div class="stat-item like-btn" role="button">
                            <i class="bi bi-hand-thumbs-up"></i>
                            <span><?php echo $post['up_count']; ?> Likes</span>
                        </div>
                        <div class="stat-item">
                            <i class="bi bi-eye"></i>
                            <span><?php echo $post['view_count']; ?> Views</span>
                        </div>
                        <div class="stat-item">
                            <i class="bi bi-chat-square-text"></i>
                            <span><?php echo count($comments); ?> Comments</span>
                        </div>
                    </div>
                    <div class="post-actions">
                        <?php if ($currentUser['actor_id'] == $post['poster_id']): ?>
                            <a href="edit_post.php?post_id=<?php echo $post_id; ?>" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i> Edit</a>
                        <?php endif; ?>
                        <a href="../dashboard/forums.php?forum_id=<?php echo $post['forum_id']; ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Forum</a>
                    </div>
                </div>
            </div>
            
            <!-- Comments Section -->
            <h4 class="section-title mt-5">
                <i class="bi bi-chat-square-text"></i>
                Discussion (<?php echo count($comments); ?>)
            </h4>
            
            <?php if (count($comments) > 0): ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-card">
                        <div class="comment-header">
                            <div class="user-info">
                                <div class="avatar">
                                    <?php if (!empty($comment['commenter_picture'])): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($comment['commenter_picture']); ?>" alt="Commenter Picture">
                                    <?php else: ?>
                                        <i class="bi bi-person-fill"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="username mb-0"><?php echo htmlspecialchars($comment['commenter_name']); ?></p>
                                    <span class="comment-meta"><?php echo date('M j, Y \a\t g:i a', strtotime($comment['commented_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="comment-body">
                            <div class="comment-content">
                                <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-chat-square-text"></i>
                    <h5 class="mb-2">No comments yet</h5>
                    <p class="text-muted">Be the first to share your thoughts on this post</p>
                </div>
            <?php endif; ?>
            
            <!-- Comment Form -->
            <div class="comment-form">
                <h5 class="mb-4"><i class="bi bi-pencil-square"></i> Add your comment</h5>
                <form method="POST">
                    <div class="mb-3">
                        <textarea class="form-control" name="comment" rows="5" placeholder="Write a thoughtful comment..." required style="resize: none;"></textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-send-fill"></i> Post Comment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enhanced like button interaction
        document.querySelectorAll('.like-btn').forEach(button => {
            button.addEventListener('click', function() {
                const icon = this.querySelector('i');
                const countElement = this.querySelector('span');
                let count = parseInt(countElement.textContent);
                
                this.classList.toggle('active');
                
                if (this.classList.contains('active')) {
                    icon.classList.remove('bi-hand-thumbs-up');
                    icon.classList.add('bi-hand-thumbs-up-fill');
                    count++;
                } else {
                    icon.classList.remove('bi-hand-thumbs-up-fill');
                    icon.classList.add('bi-hand-thumbs-up');
                    count--;
                }
                
                countElement.textContent = count;
                
                // Here you would typically make an AJAX call to update the database
                // For now, it's just a UI enhancement
            });
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>