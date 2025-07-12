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
            --primary-color: #3b82f6; /* Softer blue for modern look */
            --secondary-color: #64748b; /* Neutral gray */
            --accent-color: #10b981; /* Green for success states */
            --danger-color: #ef4444; /* Red for errors */
            --light-gray: #f1f5f9; /* Lighter background */
            --dark-color: #1e293b; /* Darker text */
            --border-radius: 8px; /* Modern radius */
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.2s ease-in-out;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif; /* Modern font stack */
            background-color: #f8fafc;
            color: var(--dark-color);
            min-height: 100vh;
        }

        .forum-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        .post-card {
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            background: white;
            margin-bottom: 2rem;
            transition: var(--transition);
        }

        .post-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .post-header {
            background: linear-gradient(135deg, var(--primary-color), #2563eb);
            color: white;
            padding: 1.25rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .post-title {
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .post-meta {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .post-body {
            padding: 1.5rem;
        }

        .post-content {
            line-height: 1.6;
            font-size: 1rem;
            color: var(--dark-color);
        }

        .post-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stats {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: var(--secondary-color);
            cursor: pointer;
            transition: var(--transition);
        }

        .stat-item:hover {
            color: var(--primary-color);
        }

        .stat-item i {
            margin-right: 0.4rem;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 0.75rem;
            background-color: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar i {
            font-size: 1.25rem;
            color: var(--secondary-color);
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .username {
            font-weight: 600;
            margin: 0;
            font-size: 0.95rem;
        }

        .badge-pinned {
            background-color: #fef3c7;
            color: var(--dark-color);
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .comment-card {
            border-radius: var(--border-radius);
            border: 1px solid var(--light-gray);
            background: white;
            box-shadow: var(--card-shadow);
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .comment-card:hover {
            transform: translateX(3px);
            border-left: 3px solid var(--accent-color);
        }

        .comment-header {
            padding: 1rem;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .comment-body {
            padding: 1rem;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .comment-meta {
            font-size: 0.8rem;
            color: var(--secondary-color);
        }

        .section-title {
            font-weight: 600;
            font-size: 1.25rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 0.5rem;
            color: var(--accent-color);
        }

        .comment-form {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            margin-top: 1.5rem;
        }

        .form-control {
            border-radius: var(--border-radius);
            padding: 0.75rem;
            border: 1px solid var(--light-gray);
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary:hover {
            background-color: #2563eb;
            border-color: #2563eb;
            transform: translateY(-1px);
        }

        .btn-outline-secondary {
            border-radius: var(--border-radius);
            padding: 0.5rem 1rem;
            font-weight: 500;
        }

        .like-btn.active {
            color: var(--primary-color);
            font-weight: 500;
        }

        .like-btn i {
            transition: var(--transition);
        }

        .like-btn.active i {
            transform: scale(1.1);
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            margin: 1rem 0;
        }

        .empty-state i {
            font-size: 2.5rem;
            color: var(--light-gray);
            margin-bottom: 0.75rem;
        }

        .breadcrumb {
            background: transparent;
            padding: 0.5rem 0;
            margin-bottom: 1.5rem;
        }

        .breadcrumb-item a {
            color: var(--secondary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb-item a:hover {
            color: var(--primary-color);
        }

        .alert {
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert i {
            font-size: 1.25rem;
        }

        /* Toast Notification */
        .toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1050;
        }

        .toast {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 1rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
            transform: translateX(100%);
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast-success {
            border-left: 4px solid var(--accent-color);
        }

        .toast-error {
            border-left: 4px solid var(--danger-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .forum-container {
                padding: 1rem;
            }

            .post-title {
                font-size: 1.25rem;
            }

            .post-header, .post-body, .post-footer {
                padding: 1rem;
            }

            .post-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .stats {
                gap: 0.75rem;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .post-card, .comment-card, .comment-form {
            animation: fadeIn 0.3s ease-out forwards;
        }

        /* Accessibility */
        .like-btn:focus, .btn-primary:focus, .btn-outline-secondary:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="forum-container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard/forums.php" aria-label="Back to Forums"><i class="bi bi-arrow-left"></i> Forums</a></li>
                    <li class="breadcrumb-item"><a href="../dashboard/forums.php?forum_id=<?php echo $post['forum_id']; ?>" aria-label="Back to <?php echo htmlspecialchars($post['forum_title']); ?> Forum"><?php echo htmlspecialchars($post['forum_title']); ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Thread</li>
                </ol>
            </nav>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Main Post Card -->
            <div class="post-card card" role="article">
                <div class="post-header card-header">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h1 class="post-title"><?php echo htmlspecialchars($post['post_title']); ?></h1>
                            <div class="user-info">
                                <div class="avatar" aria-hidden="true">
                                    <?php if (!empty($post['poster_picture'])): ?>
                                        <img src="../Uploads/<?php echo htmlspecialchars($post['poster_picture']); ?>" alt="Profile Picture of <?php echo htmlspecialchars($post['poster_name']); ?>">
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
                        <div class="stat-item like-btn" role="button" data-post-id="<?php echo $post['post_id']; ?>" 
                             aria-label="Like Post (<?php echo $post['up_count']; ?> likes)">
                            <i class="bi bi-hand-thumbs-up"></i>
                            <span class="like-count"><?php echo $post['up_count']; ?> Likes</span>
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
                            <a href="edit_post.php?post_id=<?php echo $post_id; ?>" class="btn btn-outline-secondary" aria-label="Edit Post">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                        <?php endif; ?>
                        <a href="../dashboard/forums.php?forum_id=<?php echo $post['forum_id']; ?>" 
                           class="btn btn-outline-secondary" aria-label="Back to Forum">
                            <i class="bi bi-arrow-left"></i> Back to Forum
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Comments Section -->
            <h4 class="section-title mt-4">
                <i class="bi bi-chat-square-text"></i>
                Discussion (<?php echo count($comments); ?>)
            </h4>
            
            <?php if (count($comments) > 0): ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-card" role="article">
                        <div class="comment-header">
                            <div class="user-info">
                                <div class="avatar" aria-hidden="true">
                                    <?php if (!empty($comment['commenter_picture'])): ?>
                                        <img src="../Uploads/<?php echo htmlspecialchars($comment['commenter_picture']); ?>" 
                                             alt="Profile Picture of <?php echo htmlspecialchars($comment['commenter_name']); ?>">
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
                <h5 class="mb-3"><i class="bi bi-pencil-square"></i> Add your comment</h5>
                <form method="POST">
                    <div class="mb-3">
                        <textarea class="form-control" name="comment" rows="4" placeholder="Write a thoughtful comment..." 
                                  required style="resize: none;" aria-label="Comment input"></textarea>
                        <div class="form-text">Share your thoughts. Be respectful and concise.</div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary new-post-btn" aria-label="Post Comment">
                            <i class="bi bi-send-fill"></i> Post Comment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container"></div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Like button handler with toast notification
            document.querySelectorAll('.like-btn').forEach(button => {
                const postId = button.dataset.postId;
                const icon = button.querySelector('i');
                const countElement = button.querySelector('.like-count');

                // Check if already liked
                if (localStorage.getItem(`liked_post_${postId}`)) {
                    button.classList.add('active');
                    icon.classList.remove('bi-hand-thumbs-up');
                    icon.classList.add('bi-hand-thumbs-up-fill');
                    button.style.pointerEvents = 'none';
                }

                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (localStorage.getItem(`liked_post_${postId}`)) {
                        showToast('You already liked this post.', 'error');
                        return;
                    }

                    button.classList.add('disabled');
                    fetch('../forum/like_post.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `post_id=${postId}`
                    })
                    .then(res => res.json())
                    .then(data => {
                        button.classList.remove('disabled');
                        if (data.success) {
                            countElement.textContent = data.like_count;
                            button.classList.add('active');
                            icon.classList.remove('bi-hand-thumbs-up');
                            icon.classList.add('bi-hand-thumbs-up-fill');
                            localStorage.setItem(`liked_post_${postId}`, 'true');
                            button.style.pointerEvents = 'none';
                            showToast('Post liked successfully!', 'success');
                        } else {
                            showToast(data.message || 'Failed to like post.', 'error');
                        }
                    })
                    .catch(err => {
                        button.classList.remove('disabled');
                        showToast('An error occurred.', 'error');
                        console.error('Error:', err);
                    });
                });
            });

            // Toast notification function
            function showToast(message, type = 'success') {
                const toastContainer = document.querySelector('.toast-container');
                const toast = document.createElement('div');
                toast.className = `toast toast-${type} show`;
                toast.innerHTML = `
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    <span>${message}</span>
                `;
                toastContainer.appendChild(toast);
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelector(this.getAttribute('href')).scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            });
        });
    </script>
</body>
</html>