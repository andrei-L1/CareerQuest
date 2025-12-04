<?php
session_start();
require "../config/dbcon.php";
/** @var PDO $conn */
// Debugging: Log received data
error_log("Received POST: " . print_r($_POST, true));

if (!isset($_SESSION['user_id'])) {
    error_log("No session user_id found.");
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate input
if (!isset($_POST['comment_id']) || !isset($_POST['action']) || !in_array($_POST['action'], ['approve', 'delete'])) {
    error_log("Invalid request: comment_id=" . ($_POST['comment_id'] ?? 'null') . ", action=" . ($_POST['action'] ?? 'null'));
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$comment_id = filter_var($_POST['comment_id'], FILTER_VALIDATE_INT);
$action = $_POST['action'];

if ($comment_id === false) {
    error_log("Invalid comment ID: " . ($_POST['comment_id'] ?? 'null'));
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['success' => false, 'message' => 'Invalid comment ID.']);
    exit();
}

try {
    // First get the actor_id for the moderator
    $stmt = $conn->prepare("SELECT actor_id FROM actor WHERE entity_type = 'user' AND entity_id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $actor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$actor) {
        throw new Exception("Moderator actor record not found");
    }
    
    $moderator_id = $actor['actor_id'];
    
    $conn->beginTransaction();

    // Update reports for this comment
    $stmt = $conn->prepare("
        UPDATE report 
        SET status = 'resolved', 
            resolution = :resolution, 
            resolved_at = NOW(), 
            moderator_id = :moderator_id
        WHERE content_id = :comment_id 
        AND content_type = 'comment' 
        AND status = 'pending'
    ");
    $resolution = ($action === 'approve') ? 'approved' : 'deleted';
    
    $stmt->bindParam(':resolution', $resolution, PDO::PARAM_STR);
    $stmt->bindParam(':moderator_id', $moderator_id, PDO::PARAM_INT);
    $stmt->bindParam(':comment_id', $comment_id, PDO::PARAM_INT);
    $stmt->execute();

    // ONLY delete the comment if action is 'delete'
    if ($action === 'delete') {
        $stmt = $conn->prepare("UPDATE forum_comment SET deleted_at = NOW() WHERE comment_id = :comment_id");
        $stmt->bindParam(':comment_id', $comment_id, PDO::PARAM_INT);
        $stmt->execute();
        
        error_log("Soft-deleted comment_id: $comment_id");
    }

    $conn->commit();
    
    error_log("Successfully processed comment_id: $comment_id with action: $action");
    echo json_encode([
        'success' => true, 
        'message' => 'Comment ' . ($action === 'approve' ? 'approved' : 'deleted') . ' successfully'
    ]);
    
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Error moderating comment: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while processing your request.'
    ]);
}
?>