<?php
require_once '../config/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit;
}

// Identify current user
$entityType = isset($_SESSION['user_id']) ? 'user' : 'student';
$entityId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['stud_id'];

// Ensure actor exists
$query = "SELECT actor_id FROM actor WHERE entity_type = ? AND entity_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$entityType, $entityId]);
$actor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$actor) {
    // Create actor record
    $insert = $conn->prepare("INSERT INTO actor (entity_type, entity_id) VALUES (?, ?)");
    $insert->execute([$entityType, $entityId]);
    $actorId = $conn->lastInsertId();
} else {
    $actorId = $actor['actor_id'];
}

// Check if forum_id is provided
if (!isset($_POST['forum_id'])) {
    header("Location: ../dashboard/forums.php?error=missing_forum_id");
    exit;
}

$forumId = $_POST['forum_id'];

// Check if the forum is private
$query = "SELECT is_private FROM forum WHERE forum_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$forumId]);
$forum = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$forum) {
    header("Location: ../dashboard/forums.php?error=forum_not_found");
    exit;
}

$isPrivate = $forum['is_private'] == 1;

$newStatus = $isPrivate ? 'Pending' : 'Active';

// Check membership record
$query = "SELECT membership_id, status FROM forum_membership WHERE forum_id = ? AND actor_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$forumId, $actorId]);
$membership = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$membership) {
    // No record found, insert new membership
    $insert = $conn->prepare("INSERT INTO forum_membership (forum_id, actor_id, role, status) VALUES (?, ?, 'Member', ?)");
    $insert->execute([$forumId, $actorId, $newStatus]);
} elseif ($membership['status'] === 'Left') {
    // Rejoining: update status
    $update = $conn->prepare("UPDATE forum_membership SET status = ?, deleted_at = NULL WHERE membership_id = ?");
    $update->execute([$newStatus, $membership['membership_id']]);
}

// Redirect to forum page
header("Location: ../dashboard/forums.php?forum_id=" . $forumId);
exit;
?>
