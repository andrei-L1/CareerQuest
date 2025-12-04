<?php
session_start();
require "../config/dbcon.php";
/** @var PDO $conn */
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate input
$required_fields = ['post_id', 'content', 'post_title', 'is_pinned', 'is_announcement'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field])) {
        header("HTTP/1.1 400 Bad Request");
        echo json_encode(['success' => false, 'message' => "Missing field: $field"]);
        exit();
    }
}

$post_id = filter_var($_POST['post_id'], FILTER_VALIDATE_INT);
$content = trim($_POST['content']);
$post_title = trim($_POST['post_title']);
$is_pinned = filter_var($_POST['is_pinned'], FILTER_VALIDATE_BOOLEAN);
$is_announcement = filter_var($_POST['is_announcement'], FILTER_VALIDATE_BOOLEAN);

if ($post_id === false || empty($content) || empty($post_title)) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

try {
    // Get the actor_id for the moderator
    $stmt = $conn->prepare("SELECT actor_id FROM actor WHERE entity_type = 'user' AND entity_id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $actor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$actor) {
        throw new Exception("Moderator actor record not found");
    }
    
    $moderator_id = $actor['actor_id'];
    
    $conn->beginTransaction();

    // Update the post
    $stmt = $conn->prepare("
        UPDATE forum_post 
        SET 
            content = :content,
            post_title = :post_title,
            is_pinned = :is_pinned,
            is_announcement = :is_announcement,
            updated_at = NOW() 
        WHERE post_id = :post_id
    ");
    $stmt->bindParam(':content', $content, PDO::PARAM_STR);
    $stmt->bindParam(':post_title', $post_title, PDO::PARAM_STR);
    $stmt->bindParam(':is_pinned', $is_pinned, PDO::PARAM_BOOL);
    $stmt->bindParam(':is_announcement', $is_announcement, PDO::PARAM_BOOL);
    $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $stmt->execute();

    // Update all reports for this post
    $stmt = $conn->prepare("
        UPDATE report 
        SET 
            status = 'resolved', 
            resolution = 'edited', 
            resolved_at = NOW(), 
            moderator_id = :moderator_id
        WHERE content_id = :post_id 
        AND content_type = 'post' 
        AND status = 'pending'
    ");
    $stmt->bindParam(':moderator_id', $moderator_id, PDO::PARAM_INT);
    $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $stmt->execute();

    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Post updated successfully'
    ]);
    
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Error editing post: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode([
        'success' => false,
        'message' => 'Error updating post'
    ]);
}
?>