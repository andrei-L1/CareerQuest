<?php
require_once '../config/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get the current user's actor_id
if (isset($_SESSION['user_id'])) {
    $entity_type = 'user';
    $entity_id = $_SESSION['user_id'];
} else {
    $entity_type = 'student';
    $entity_id = $_SESSION['stud_id'];
}

// Get actor_id for the current user
$query = "SELECT actor_id FROM actor WHERE entity_type = ? AND entity_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$entity_type, $entity_id]);
$actor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$actor) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$actor_id = $actor['actor_id'];

// Validate input
if (!isset($_POST['notification_id']) || !is_numeric($_POST['notification_id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit;
}

$notification_id = $_POST['notification_id'];

// Verify the notification belongs to the current user
$query = "SELECT notification_id FROM notification 
          WHERE notification_id = ? AND actor_id = ? AND is_read = FALSE AND deleted_at IS NULL";
$stmt = $conn->prepare($query);
$stmt->execute([$notification_id, $actor_id]);
$notification = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notification) {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['success' => false, 'message' => 'Notification not found or already read']);
    exit;
}

// Mark the notification as read
$query = "UPDATE notification SET is_read = TRUE WHERE notification_id = ?";
$stmt = $conn->prepare($query);
$success = $stmt->execute([$notification_id]);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
}