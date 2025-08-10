<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/dbcon.php';
header('Content-Type: application/json');
ini_set('display_errors', 0);

// Debug: Log incoming POST and session data
error_log("POST data: " . print_r($_POST, true));
error_log("Session data: " . print_r($_SESSION, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$userId = intval($_POST['user_id'] ?? 0);
$userType = strtolower(trim($_POST['user_type'] ?? '')); // expected: 'user' or 'student'
$message = trim($_POST['message'] ?? '');
$moderatorUserId = intval($_SESSION['user_id'] ?? 0);

if (!$userId || !$userType || !$message || !$moderatorUserId) {
    $missingFields = [];
    if (!$userId) $missingFields[] = 'user_id';
    if (!$userType) $missingFields[] = 'user_type';
    if (!$message) $missingFields[] = 'message';
    if (!$moderatorUserId) $missingFields[] = 'moderatorUserId (session)';
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields: ' . implode(', ', $missingFields)]);
    exit;
}

// Validate the type
if (!in_array($userType, ['user', 'student'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user type']);
    exit;
}

try {
    $conn->beginTransaction();

    // 1. Get actor_id for the user being warned
    $stmt = $conn->prepare("
        SELECT actor_id 
        FROM actor 
        WHERE entity_id = ? AND entity_type = ?
    ");
    $stmt->execute([$userId, $userType]);
    $actor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$actor) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }

    $actorId = $actor['actor_id'];

    // 2. Get moderator's actor_id
    $stmt = $conn->prepare("
        SELECT actor_id 
        FROM actor 
        WHERE entity_id = ? AND entity_type = 'user'
    ");
    $stmt->execute([$moderatorUserId]);
    $moderator = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$moderator) {
        echo json_encode(['status' => 'error', 'message' => 'Moderator not found']);
        exit;
    }

    $moderatorActorId = $moderator['actor_id'];

    // 3. Insert the warning notification
    $stmt = $conn->prepare("
        INSERT INTO notification (actor_id, message, notification_type, reference_type, reference_id)
        VALUES (?, ?, 'warning', ?, ?)
    ");
    $stmt->execute([$actorId, $message, $userType, $userId]);

    // 4. Update report table for all pending user reports
    $stmt = $conn->prepare("
        UPDATE report
        SET status = 'resolved',
            resolution = 'approved',
            resolved_at = CURRENT_TIMESTAMP,
            moderator_id = ?
        WHERE content_type = 'user'
          AND content_id = ?
          AND status = 'pending'
    ");
    $stmt->execute([$moderatorActorId, $actorId]);
    $affectedReportRows = $stmt->rowCount();
    error_log("Updated $affectedReportRows reports for actor_id: $actorId");

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Warning sent and reports resolved successfully']);
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Warn User Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while processing the warning']);
}
?>