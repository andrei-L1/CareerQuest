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
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">New Post in <?php echo htmlspecialchars($forum['title']); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="title" class="form-label">Post Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="content" class="form-label">Content</label>
                                <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
                            </div>
                            <?php if ($currentUser['entity_type'] === 'user'): ?>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_pinned" name="is_pinned">
                                    <label class="form-check-label" for="is_pinned">Pin this post (for moderators)</label>
                                </div>
                            <?php endif; ?>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Submit Post</button>
                                <a href="forum.php?forum_id=<?php echo $forum_id; ?>" class="btn btn-secondary">Cancel</a>
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