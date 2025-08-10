<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/dbcon.php';
header('Content-Type: application/json');

// Debug: Log incoming POST and session data
error_log("POST data: " . print_r($_POST, true));
error_log("Session data: " . print_r($_SESSION, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$userId = intval($_POST['user_id'] ?? 0);
$userType = trim($_POST['user_type'] ?? '');
$reason = trim($_POST['reason'] ?? '');
$moderatorUserId = intval($_SESSION['user_id'] ?? 0);

// Check for missing fields
if (!$userId || !$userType || !$reason || !$moderatorUserId) {
    $missingFields = [];
    if (!$userId) $missingFields[] = 'user_id';
    if (!$userType) $missingFields[] = 'user_type';
    if (!$reason) $missingFields[] = 'reason';
    if (!$moderatorUserId) $missingFields[] = 'moderatorUserId (session)';
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields: ' . implode(', ', $missingFields)]);
    exit;
}

try {
    $conn->beginTransaction();

    // 1. Get actor_id for the user being banned
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

    // 3. Get all forums the user is a member of
    $stmt = $conn->prepare("
        SELECT forum_id 
        FROM forum_membership 
        WHERE actor_id = ?
    ");
    $stmt->execute([$actorId]);
    $memberships = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Update report table for all pending user reports
    $stmt = $conn->prepare("
        UPDATE report
        SET status = 'resolved',
            resolution = 'deleted',
            resolved_at = CURRENT_TIMESTAMP,
            moderator_id = ?
        WHERE content_type = 'user'
          AND content_id = ?
          AND status = 'pending'
    ");
    $stmt->execute([$moderatorActorId, $actorId]);
    $affectedReportRows = $stmt->rowCount();
    error_log("Updated $affectedReportRows reports for actor_id: $actorId");

    // 5. If no forum memberships, still consider the ban action complete
    if (empty($memberships)) {
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'User reports resolved successfully (user not in any forums)']);
        exit;
    }

    // 6. Update forum membership status to Banned for all forums
    $stmt = $conn->prepare("
        UPDATE forum_membership
        SET status = 'Banned'
        WHERE actor_id = ?
    ");
    $stmt->execute([$actorId]);
    $affectedMembershipRows = $stmt->rowCount();
    error_log("Updated $affectedMembershipRows forum memberships for actor_id: $actorId");

    // 7. Send ban notification for each forum
    $stmt = $conn->prepare("
        INSERT INTO notification (actor_id, message, notification_type, reference_type, reference_id)
        VALUES (?, ?, 'forum_ban', 'forum', ?)
    ");
    foreach ($memberships as $membership) {
        $forumId = $membership['forum_id'];
        $banMessage = "You have been banned from all forums. Reason: {$reason}.";
        $stmt->execute([$actorId, $banMessage, $forumId]);
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'User banned from all forums and reports resolved successfully']);
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Ban User Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while processing the ban']);
}
?>