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
$query = "SELECT fp.*, f.title AS forum_title
          FROM forum_post fp
          JOIN forum f ON fp.forum_id = f.forum_id
          WHERE fp.post_id = ? AND fp.deleted_at IS NULL";
$stmt = $conn->prepare($query);
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    $_SESSION['error'] = "Post not found";
    header("Location: ../dashboard/forums.php");
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
    $_SESSION['error'] = "User not found";
    header("Location: ../dashboard/forums.php");
    exit;
}

$currentUser['actor_id'] = $actor['actor_id'];

// Check if current user is the post author
if ($currentUser['actor_id'] != $post['poster_id']) {
    $_SESSION['error'] = "You can only edit your own posts";
    header("Location: post.php?post_id=" . $post_id);
    exit;
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
        // Update the post
        $query = "UPDATE forum_post 
                  SET post_title = ?, content = ?, is_pinned = ?, updated_at = CURRENT_TIMESTAMP
                  WHERE post_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$title, $content, $is_pinned, $post_id]);
        
        $_SESSION['success'] = "Post updated successfully!";
        header("Location: post.php?post_id=" . $post_id);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post - Career Platform</title>
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

        .edit-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .card {
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid var(--light-gray);
            background: white;
            transition: var(--transition);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), #2563eb);
            color: white;
            padding: 1rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            font-weight: 600;
            font-size: 1.25rem;
        }

        .card-body {
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
            .edit-container {
                padding: 0 0.75rem;
                margin: 1rem auto;
            }

            .card-body {
                padding: 1rem;
            }

            .card-header {
                font-size: 1.1rem;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            animation: fadeIn 0.3s ease-out forwards;
        }

        /* Accessibility */
        .form-control:focus, .btn-primary:focus, .btn-secondary:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <div class="card">
            <div class="card-header">
                Edit Post in <?php echo htmlspecialchars($post['forum_title']); ?>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="editPostForm">
                    <div class="mb-3">
                        <label for="title" class="form-label">Post Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo htmlspecialchars($post['post_title']); ?>" 
                               required aria-describedby="titleHelp">
                        <div id="titleHelp" class="form-text">Enter a concise and descriptive title for your post.</div>
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="content" name="content" rows="8" 
                                  required aria-describedby="contentHelp"><?php echo htmlspecialchars($post['content']); ?></textarea>
                        <div id="contentHelp" class="form-text">Provide the main content of your post. Be clear and respectful.</div>
                    </div>
                    <?php if ($currentUser['entity_type'] === 'user'): ?>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_pinned" name="is_pinned" 
                                   <?php echo $post['is_pinned'] ? 'checked' : ''; ?> aria-describedby="pinHelp">
                            <label class="form-check-label" for="is_pinned">Pin this post (for moderators)</label>
                            <div id="pinHelp" class="form-text">Pinned posts appear at the top of the forum.</div>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Update Post</button>
                        <a href="post.php?post_id=<?php echo $post_id; ?>" class="btn btn-primary" aria-label="Cancel Edit">Cancel</a>
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
            const form = document.getElementById('editPostForm');
            const titleInput = document.getElementById('title');
            const contentInput = document.getElementById('content');

            form.addEventListener('submit', function(e) {
                let hasError = false;

                // Client-side validation
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