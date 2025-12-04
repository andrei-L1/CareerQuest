<?php
require_once '../config/dbcon.php';
/** @var PDO $conn */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit;
}

// Identify current actor
$entityType = isset($_SESSION['user_id']) ? 'user' : 'student';
$entityId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['stud_id'];

// Retrieve actor_id
$query = "SELECT actor_id FROM actor WHERE entity_type = ? AND entity_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$entityType, $entityId]);
$actor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$actor) {
    header("Location: ../dashboard/forums.php?error=actor_not_found");
    exit;
}

$actorId = $actor['actor_id'];

// Check if forum_id is provided
if (!isset($_POST['forum_id'])) {
    header("Location: ../dashboard/forums.php?error=missing_forum_id");
    exit;
}

$forumId = $_POST['forum_id'];

// Soft delete the membership: set deleted_at and update status to 'Left'
$query = "UPDATE forum_membership 
          SET status = 'Left', deleted_at = NOW() 
          WHERE forum_id = ? AND actor_id = ? AND deleted_at IS NULL";
$stmt = $conn->prepare($query);
$stmt->execute([$forumId, $actorId]);

// Redirect back to forums page
header("Location: ../dashboard/forums.php");
exit;
?>
