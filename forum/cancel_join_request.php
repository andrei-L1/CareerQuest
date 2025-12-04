<?php
require_once '../config/dbcon.php';
/** @var PDO $conn */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit;
}

// Get current user type and ID
$entityType = isset($_SESSION['user_id']) ? 'user' : 'student';
$entityId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['stud_id'];

// Get actor_id
$stmt = $conn->prepare("SELECT actor_id FROM actor WHERE entity_type = ? AND entity_id = ?");
$stmt->execute([$entityType, $entityId]);
$actor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$actor) {
    header("Location: ../dashboard/forums.php?error=actor_not_found");
    exit;
}

$actorId = $actor['actor_id'];

// Ensure forum_id is provided
if (!isset($_POST['forum_id'])) {
    header("Location: ../dashboard/forums.php?error=missing_forum_id");
    exit;
}

$forumId = $_POST['forum_id'];

// Set status to 'Left' and soft-delete
$stmt = $conn->prepare("UPDATE forum_membership SET status = 'Left', deleted_at = NOW() WHERE forum_id = ? AND actor_id = ?");
$stmt->execute([$forumId, $actorId]);

header("Location: ../dashboard/forums.php?forum_id=" . $forumId);
exit;
?>
