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

// Check if forum_id is provided
if (!isset($_GET['forum_id'])) {
    $_SESSION['error'] = "Forum not specified";
    header("Location: forums.php");
    exit;
}

$forum_id = $_GET['forum_id'];

// Verify forum exists
$query = "SELECT * FROM forum WHERE forum_id = ? AND deleted_at IS NULL";
$stmt = $conn->prepare($query);
$stmt->execute([$forum_id]);
$forum = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$forum) {
    $_SESSION['error'] = "Forum not found";
    header("Location: forums.php");
    exit;
}

// Initialize current user data
if (isset($_SESSION['user_id'])) {
    $currentUser = [
        'entity_type' => 'user',
        'entity_id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_first_name'] ?? 'User',
        'role' => $_SESSION['user_role'] ?? 'Unknown'
    ];
    // Check if user is a moderator or admin
    $query = "SELECT r.role_title FROM user u JOIN role r ON u.role_id = r.role_id WHERE u.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$currentUser['entity_id']]);
    $userDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    $isModerator = $userDetails && in_array($userDetails['role_title'], ['Admin', 'Moderator']);
} else {
    $currentUser = [
        'entity_type' => 'student',
        'entity_id' => $_SESSION['stud_id'],
        'name' => $_SESSION['stud_first_name'] ?? 'Student',
        'role' => 'Student'
    ];
    $isModerator = false;
}

// Get actor ID
$query = "SELECT actor_id FROM actor WHERE entity_type = ? AND entity_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$currentUser['entity_type'], $currentUser['entity_id']]);
$actor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$actor) {
    $query = "INSERT INTO actor (entity_type, entity_id) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$currentUser['entity_type'], $currentUser['entity_id']]);
    $currentUser['actor_id'] = $conn->lastInsertId();
} else {
    $currentUser['actor_id'] = $actor['actor_id'];
}

// Check forum membership and role
$query = "SELECT role, status FROM forum_membership WHERE forum_id = ? AND actor_id = ? AND deleted_at IS NULL";
$stmt = $conn->prepare($query);
$stmt->execute([$forum_id, $currentUser['actor_id']]);
$membership = $stmt->fetch(PDO::FETCH_ASSOC);
$isForumModerator = $membership && in_array($membership['role'], ['Moderator', 'Admin']) && $membership['status'] === 'Active';
$isModerator = $isModerator || $isForumModerator;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $is_pinned = isset($_POST['is_pinned']) && $isModerator ? 1 : 0;
    $is_announcement = isset($_POST['is_announcement']) && $isModerator ? 1 : 0;

    // Validate input
    if (empty($title) || empty($content)) {
        $_SESSION['error'] = "Title and content are required";
    } else {
        // Create the post
        $query = "INSERT INTO forum_post (forum_id, post_title, poster_id, content, is_pinned, is_announcement) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$forum_id, $title, $currentUser['actor_id'], $content, $is_pinned, $is_announcement]);
        $post_id = $conn->lastInsertId();

        // Send notifications for announcements
        if ($is_announcement) {
            $query = "SELECT actor_id FROM forum_membership WHERE forum_id = ? AND status = 'Active' AND actor_id != ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$forum_id, $currentUser['actor_id']]);
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($members as $member) {
                $query = "INSERT INTO notification (actor_id, message, notification_type, reference_type, reference_id, action_url)
                          VALUES (?, ?, 'announcement', 'post', ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    $member['actor_id'],
                    "New announcement in " . htmlspecialchars($forum['title']) . ": " . htmlspecialchars($title),
                    $post_id,
                    "../dashboard/forums.php?forum_id=$forum_id#post-$post_id"
                ]);
            }
        }

        $_SESSION['success'] = "Post created successfully!";
        header("Location: ../dashboard/forums.php?forum_id=" . $forum_id);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Post - Career Platform</title>
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

        .post-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .forum-header {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--light-gray);
        }

        .forum-title {
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .forum-description {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .post-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid var(--light-gray);
            transition: var(--transition);
        }

        .post-card-header {
            background: linear-gradient(135deg, var(--primary-color), #2563eb);
            color: white;
            padding: 1rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            font-weight: 600;
            font-size: 1.25rem;
        }

        .post-card-body {
            padding: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .form-control {
            border-radius: var(--border-radius);
            border: 1px solid var(--light-gray);
            padding: 0.75rem;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-control.is-invalid {
            border-color: var(--danger-color);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .form-check {
            margin-bottom: 1rem;
        }

        .form-check-label {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .form-text {
            font-size: 0.8rem;
            color: var(--secondary-color);
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

        .btn-secondary {
            border-radius: var(--border-radius);
            padding: 0.5rem 1rem;
            font-weight: 500;
            border-color: var(--light-gray);
            color: var(--secondary-color);
        }

        .btn-secondary:hover {
            background-color: var(--light-gray);
            border-color: var(--secondary-color);
            color: var(--dark-color);
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

        .guidelines-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid var(--light-gray);
            margin-top: 1.5rem;
        }

        .guidelines-card ul {
            padding-left: 0;
        }

        .guidelines-card li {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: var(--secondary-color);
        }

        .guidelines-card i {
            color: var(--accent-color);
            margin-right: 0.5rem;
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

        .toast-error {
            border-left: 4px solid var(--danger-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .post-container {
                padding: 0 0.75rem;
                margin: 1rem auto;
            }

            .forum-title {
                font-size: 1.25rem;
            }

            .post-card-header {
                font-size: 1.1rem;
                padding: 1rem;
            }

            .post-card-body {
                padding: 1rem;
            }

            .d-md-flex {
                flex-direction: column;
                gap: 0.5rem;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .forum-header, .post-card, .guidelines-card {
            animation: fadeIn 0.3s ease-out forwards;
        }

        /* Accessibility */
        .form-control:focus, .btn-primary:focus, .btn-secondary:focus, .form-check-input:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="post-container">
        <!-- Sidebar Navigation -->
        <?php require '../includes/forum_sidebar.php'; ?>
        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/forums.php" aria-label="Back to Forums"><i class="bi bi-arrow-left"></i> Forums</a></li>
                <li class="breadcrumb-item"><a href="../dashboard/forums.php?forum_id=<?php echo $forum_id; ?>" aria-label="Back to <?php echo htmlspecialchars($forum['title']); ?> Forum"><?php echo htmlspecialchars($forum['title']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">New Post</li>
            </ol>
        </nav>
        
        <!-- Forum Header -->
        <div class="forum-header">
            <h2 class="forum-title"><?php echo htmlspecialchars($forum['title']); ?></h2>
            <?php if (!empty($forum['description'])): ?>
                <p class="forum-description"><?php echo htmlspecialchars($forum['description']); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="post-card mb-4">
                    <div class="post-card-header">
                        <i class="bi bi-pencil-square me-2"></i> Create New Post
                    </div>
                    <div class="post-card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="newPostForm">
                            <div class="mb-3">
                                <label for="title" class="form-label">Post Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       required placeholder="Enter a descriptive title for your post" 
                                       aria-describedby="titleHelp">
                                <div id="titleHelp" class="form-text">Enter a concise and descriptive title for your post.</div>
                            </div>
                            <div class="mb-3">
                                <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="content" name="content" rows="6" 
                                          required placeholder="Write your post content here..." 
                                          aria-describedby="contentHelp"></textarea>
                                <div id="contentHelp" class="form-text">Provide the main content of your post. Be clear and respectful.</div>
                            </div>
                            <?php if ($isModerator): ?>
                                <div class="mb-3 form-check">
                                    <input class="form-check-input" type="checkbox" id="is_pinned" name="is_pinned" 
                                           aria-describedby="pinHelp">
                                    <label class="form-check-label" for="is_pinned">Pin this post</label>
                                    <div id="pinHelp" class="form-text">Pinned posts appear at the top of the forum.</div>
                                </div>
                                <div class="mb-3 form-check">
                                    <input class="form-check-input" type="checkbox" id="is_announcement" name="is_announcement" 
                                           aria-describedby="announcementHelp">
                                    <label class="form-check-label" for="is_announcement">Mark as Announcement</label>
                                    <div id="announcementHelp" class="form-text">Announcements are highlighted and notify all forum members.</div>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="../dashboard/forums.php?forum_id=<?php echo $forum_id; ?>" 
                                   class="btn btn-secondary" aria-label="Cancel Post Creation" style="color: black;">
                                    <i class="bi bi-x-circle me-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary" aria-label="Submit Post">
                                    <i class="bi bi-check-circle me-1"></i> Submit Post
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Posting Guidelines -->
                <div class="guidelines-card">
                    <div class="post-card-header">
                        <i class="bi bi-info-circle me-2"></i> Posting Guidelines
                    </div>
                    <div class="post-card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="bi bi-check-circle-fill me-2"></i> Be respectful and professional</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill me-2"></i> Keep discussions relevant to the forum topic</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill me-2"></i> Use clear and descriptive titles</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill me-2"></i> Provide as much detail as possible in your posts</li>
                            <li><i class="bi bi-check-circle-fill me-2"></i> Avoid sharing sensitive personal information</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container"></div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('newPostForm');
            const titleInput = document.getElementById('title');
            const contentInput = document.getElementById('content');

            // Auto-resize textarea
            contentInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = `${this.scrollHeight}px`;
            });

            // Client-side validation
            form.addEventListener('submit', function(e) {
                let hasError = false;

                if (!titleInput.value.trim()) {
                    titleInput.classList.add('is-invalid');
                    showToast('Post title is required.', 'error');
                    hasError = true;
                } else {
                    titleInput.classList.remove('is-invalid');
                }

                if (!contentInput.value.trim()) {
                    contentInput.classList.add('is-invalid');
                    showToast('Post content is required.', 'error');
                    hasError = true;
                } else {
                    contentInput.classList.remove('is-invalid');
                }

                if (hasError) {
                    e.preventDefault();
                }
            });

            // Remove invalid class on input
            [titleInput, contentInput].forEach(input => {
                input.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                });
            });

            // Toast notification function
            function showToast(message, type = 'error') {
                const toastContainer = document.querySelector('.toast-container');
                const toast = document.createElement('div');
                toast.className = `toast toast-${type} show`;
                toast.innerHTML = `
                    <i class="bi bi-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
                    <span>${message}</span>
                `;
                toastContainer.appendChild(toast);
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }
        });
    </script>
</body>
</html>