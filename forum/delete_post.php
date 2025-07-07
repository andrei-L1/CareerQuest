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
$query = "SELECT fp.*, f.forum_id
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

// Check if current user is the post author or an admin
$isAdmin = false;
if (isset($_SESSION['user_id'])) {
    $query = "SELECT role_title FROM user u JOIN role r ON u.role_id = r.role_id WHERE u.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $role = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = ($role && in_array(strtolower($role['role_title']), ['admin', 'administrator', 'moderator']));
}

if ($currentUser['actor_id'] != $post['poster_id'] && !$isAdmin) {
    $_SESSION['error'] = "You can only delete your own posts";
    header("Location: post.php?post_id=" . $post_id);
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_delete'])) {
        // Soft delete the post
        $query = "UPDATE forum_post SET deleted_at = CURRENT_TIMESTAMP WHERE post_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$post_id]);
        
        $_SESSION['success'] = "Post deleted successfully!";
        header("Location: ../dashboard/forums.php?forum_id=" . $post['forum_id']);
        exit;
    } else {
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
    <title>Delete Post - Career Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Delete Post</h3>
                    </div>
                    <div class="card-body">
                        <p>Are you sure you want to delete this post?</p>
                        <p><strong><?php echo htmlspecialchars($post['post_title']); ?></strong></p>
                        <p>This action cannot be undone.</p>
                        
                        <form method="POST">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="post.php?post_id=<?php echo $post_id; ?>" class="btn btn-secondary me-md-2">Cancel</a>
                                <button type="submit" name="confirm_delete" class="btn btn-danger">Delete Post</button>
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