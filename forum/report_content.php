<?php
require_once '../config/dbcon.php';
/** @var PDO $conn */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
    $response['message'] = 'You must be logged in to report content.';
    echo json_encode($response);
    exit;
}

// Initialize current user data
if (isset($_SESSION['user_id'])) {
    $currentUser = [
        'entity_type' => 'user',
        'entity_id' => $_SESSION['user_id']
    ];
} else {
    $currentUser = [
        'entity_type' => 'student',
        'entity_id' => $_SESSION['stud_id']
    ];
}

// Get actor ID
$query = "SELECT actor_id FROM actor WHERE entity_type = ? AND entity_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$currentUser['entity_type'], $currentUser['entity_id']]);
$actor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$actor) {
    $response['message'] = 'User not found.';
    echo json_encode($response);
    exit;
}

$actor_id = $actor['actor_id'];

// Check if user is a moderator
$isModerator = false;
if ($currentUser['entity_type'] === 'user') {
    $query = "SELECT r.role_title FROM user u LEFT JOIN role r ON u.role_id = r.role_id WHERE u.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$currentUser['entity_id']]);
    $userDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($userDetails && in_array($userDetails['role_title'], ['Admin', 'Moderator'])) {
        $isModerator = true;
    }
}

if ($isModerator) {
    $response['message'] = 'Moderators cannot report content.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$content_type = $_POST['content_type'] ?? '';
$content_id = $_POST['content_id'] ?? '';
$reason = trim($_POST['reason'] ?? '');

if (empty($content_type) || empty($content_id) || empty($reason)) {
    $response['message'] = 'All fields are required.';
    echo json_encode($response);
    exit;
}

if (!in_array($content_type, ['post', 'comment', 'user'])) {
    $response['message'] = 'Invalid content type.';
    echo json_encode($response);
    exit;
}

// Validate content_id based on content_type
if ($content_type === 'post') {
    $query = "SELECT post_id FROM forum_post WHERE post_id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($query);
    $stmt->execute([$content_id]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        $response['message'] = 'Post not found.';
        echo json_encode($response);
        exit;
    }
} elseif ($content_type === 'comment') {
    $query = "SELECT comment_id FROM forum_comment WHERE comment_id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($query);
    $stmt->execute([$content_id]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        $response['message'] = 'Comment not found.';
        echo json_encode($response);
        exit;
    }
} elseif ($content_type === 'user') {
    $query = "SELECT actor_id FROM actor WHERE actor_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$content_id]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        $response['message'] = 'User not found.';
        echo json_encode($response);
        exit;
    }
    // Prevent reporting oneself
    if ($content_id == $actor_id) {
        $response['message'] = 'You cannot report yourself.';
        echo json_encode($response);
        exit;
    }
}

// Check if a report already exists for this content by this user
$query = "SELECT report_id FROM report WHERE content_type = ? AND content_id = ? AND reported_by = ? AND resolved_at IS NULL";
$stmt = $conn->prepare($query);
$stmt->execute([$content_type, $content_id, $actor_id]);
if ($stmt->fetch(PDO::FETCH_ASSOC)) {
    $response['message'] = 'You have already reported this content.';
    echo json_encode($response);
    exit;
}

// Insert report into the database
$query = "INSERT INTO report (content_type, content_id, reported_by, reason, reported_at) VALUES (?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($query);
$stmt->execute([$content_type, $content_id, $actor_id, $reason]);

if ($stmt->rowCount() > 0) {
    $response['success'] = true;
    $response['message'] = 'Report submitted successfully!';
} else {
    $response['message'] = 'Failed to submit report.';
}

echo json_encode($response);
?>