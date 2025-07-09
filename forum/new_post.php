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
    header("Location: forum.php");
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
    header("Location: forum.php");
    exit;
}

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
    
    // Validate input
    if (empty($title) || empty($content)) {
        $_SESSION['error'] = "Title and content are required";
    } else {
        // Create the post
        $query = "INSERT INTO forum_post (forum_id, post_title, poster_id, content, is_pinned) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$forum_id, $title, $currentUser['actor_id'], $content, $is_pinned]);
        
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1A4D8F;
            --secondary-color: #3A7BD5;
            --primary-light: #e0e7ff;
            --accent-color: #4cc9f0;
            --success-color: #38b000;
            --warning-color: #ffaa00;
            --danger-color: #ef233c;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --card-hover-shadow: 0 8px 16px rgba(67, 97, 238, 0.15);
            --border-radius: 12px;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #2b2d42;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .forum-header {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .forum-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .forum-description {
            color: #6c757d;
            font-size: 0.95rem;
        }
        
        .post-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: none;
            overflow: hidden;
        }
        
        .post-card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            color: var(--primary-color);
            padding: 1.25rem 1.5rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            font-size: 1.1rem;
        }
        
        .post-card-body {
            padding: 1.5rem;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(26, 77, 143, 0.25);
        }
        
        textarea.form-control {
            min-height: 200px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            border-radius: 8px;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .form-check-input:focus {
            box-shadow: 0 0 0 0.25rem rgba(26, 77, 143, 0.25);
        }
        
        .form-label {
            font-weight: 500;
            color: #2b2d42;
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 0.75rem 0;
            font-size: 0.9rem;
        }
        
        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .breadcrumb-item a:hover {
            text-decoration: underline;
        }
        
        .breadcrumb-item.active {
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .post-card-header {
                padding: 1rem;
            }
            
            .post-card-body {
                padding: 1rem;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/forums.php"><i class="bi bi-arrow-left"></i>  Forums</a></li>
                <li class="breadcrumb-item"><a href="../dashboard/forums.php?forum_id=<?php echo $forum_id; ?>"><?php echo htmlspecialchars($forum['title']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">New Post</li>
            </ol>
        </nav>
        
        <!-- Forum Header -->
        <div class="forum-header fade-in">
            <h2 class="forum-title"><?php echo htmlspecialchars($forum['title']); ?></h2>
            <?php if (!empty($forum['description'])): ?>
                <p class="forum-description"><?php echo htmlspecialchars($forum['description']); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="row justify-content-center fade-in">
            <div class="col-lg-8">
                <div class="post-card mb-4">
                    <div class="post-card-header">
                        <i class="bi bi-pencil-square me-2"></i> Create New Post
                    </div>
                    <div class="post-card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-4">
                                <label for="title" class="form-label">Post Title</label>
                                <input type="text" class="form-control" id="title" name="title" required placeholder="Enter a descriptive title for your post">
                            </div>
                            <div class="mb-4">
                                <label for="content" class="form-label">Content</label>
                                <textarea class="form-control" id="content" name="content" rows="10" required placeholder="Write your post content here..."></textarea>
                            </div>
                            <?php if ($currentUser['entity_type'] === 'user'): ?>
                                <div class="mb-4 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_pinned" name="is_pinned">
                                    <label class="form-check-label" for="is_pinned">Pin this post (for moderators)</label>
                                </div>
                            <?php endif; ?>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="../dashboard/forums.php?forum_id=<?php echo $forum_id; ?>" class="btn btn-secondary me-md-2">
                                    <i class="bi bi-x-circle me-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-1"></i> Submit Post
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Posting Guidelines -->
                <div class="post-card fade-in">
                    <div class="post-card-header">
                        <i class="bi bi-info-circle me-2"></i> Posting Guidelines
                    </div>
                    <div class="post-card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Be respectful and professional</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Keep discussions relevant to the forum topic</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Use clear and descriptive titles</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Provide as much detail as possible in your posts</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Avoid sharing sensitive personal information</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple animation trigger
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.fade-in');
            elements.forEach((element, index) => {
                element.style.animationDelay = `${index * 0.1}s`;
            });
            
            // Auto-resize textarea as user types
            const textarea = document.getElementById('content');
            if (textarea) {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
                
                // Trigger the resize initially if there's content
                if (textarea.value) {
                    textarea.dispatchEvent(new Event('input'));
                }
            }
        });
    </script>
</body>
</html>