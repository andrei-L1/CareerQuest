<?php
session_start();
require "../config/dbcon.php";
require "../auth/auth_check.php";

error_log("Received POST: " . print_r($_POST, true));

if (!isset($_SESSION['user_id'])) {
    error_log("No session user_id found.");
    header("Location: ../auth/login.php");
    exit();
}

// Fetch actor_id
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT actor_id FROM actor WHERE entity_type = 'user' AND entity_id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$actor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$actor) {
    error_log("No actor found for user_id: " . $_SESSION['user_id']);
    echo json_encode(['success' => false, 'message' => 'Invalid user.']);
    exit();
}
$actor_id = $actor['actor_id'];

// Validate input
if (!isset($_POST['post_id']) || !isset($_POST['action']) || !in_array($_POST['action'], ['approve', 'delete'])) {
    error_log("Invalid request: post_id=" . ($_POST['post_id'] ?? 'null') . ", action=" . ($_POST['action'] ?? 'null'));
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$post_id = filter_var($_POST['post_id'], FILTER_VALIDATE_INT);
$action = $_POST['action'];

if ($post_id === false) {
    error_log("Invalid post ID: " . ($_POST['post_id'] ?? 'null'));
    echo json_encode(['success' => false, 'message' => 'Invalid post ID.']);
    exit();
}

// Check if post exists and is not already soft-deleted
$stmt = $conn->prepare("SELECT deleted_at FROM forum_post WHERE post_id = :post_id");
$stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
$stmt->execute();
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) {
    error_log("Post not found for post_id: $post_id");
    echo json_encode(['success' => false, 'message' => 'Post not found.']);
    exit();
}
if ($post['deleted_at'] !== null && $action === 'delete') {
    error_log("Post already soft-deleted for post_id: $post_id");
    echo json_encode(['success' => false, 'message' => 'Post is already deleted.']);
    exit();
}

try {
    $conn->beginTransaction();

    // Update reports
    $stmt = $conn->prepare("
        UPDATE report 
        SET status = 'resolved', 
            resolution = :resolution, 
            resolved_at = :resolved_at, 
            moderator_id = :actor_id
        WHERE content_id = :post_id 
        AND content_type = 'post' 
        AND status = 'pending'
    ");
    $resolution = ($action === 'approve') ? 'approved' : 'deleted';
    $resolved_at = date('Y-m-d H:i:s');
    $stmt->bindParam(':resolution', $resolution, PDO::PARAM_STR);
    $stmt->bindParam(':resolved_at', $resolved_at, PDO::PARAM_STR);
    $stmt->bindParam(':actor_id', $actor_id, PDO::PARAM_INT);
    $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);

    $updated = $stmt->execute();

    if ($updated && $stmt->rowCount() > 0) {
        if ($action === 'delete') {
            // Soft delete associated comments
            $stmt = $conn->prepare("UPDATE forum_comment SET deleted_at = :deleted_at WHERE post_id = :post_id AND deleted_at IS NULL");
            $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
            $stmt->bindParam(':deleted_at', $resolved_at, PDO::PARAM_STR);
            $stmt->execute();

            // Soft delete the post
            $stmt = $conn->prepare("UPDATE forum_post SET deleted_at = :deleted_at WHERE post_id = :post_id");
            $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
            $stmt->bindParam(':deleted_at', $resolved_at, PDO::PARAM_STR);
            $stmt->execute();
        }

        $conn->commit();
        error_log("Successfully $action post_id: $post_id");
        echo json_encode(['success' => true, 'message' => ucfirst($action) . 'd post successfully.']);
    } else {
        $conn->rollBack();
        error_log("No rows updated for post_id: $post_id, action: $action");
        echo json_encode(['success' => false, 'message' => 'No pending reports found for this post or action failed.']);
    }
} catch (PDOException $e) {
    $conn->rollBack();
    error_log("Error moderating post: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request.']);
}
?>