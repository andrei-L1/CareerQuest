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
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Edit Post in <?php echo htmlspecialchars($post['forum_title']); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="title" class="form-label">Post Title</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($post['post_title']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="content" class="form-label">Content</label>
                                <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                            </div>
                            <?php if ($currentUser['entity_type'] === 'user'): ?>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_pinned" name="is_pinned" <?php echo $post['is_pinned'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_pinned">Pin this post (for moderators)</label>
                                </div>
                            <?php endif; ?>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Post</button>
                                <a href="post.php?post_id=<?php echo $post_id; ?>" class="btn btn-secondary">Cancel</a>
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