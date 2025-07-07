<?php
require_once '../config/dbcon.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Check login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit;
}

// Identify current actor
$entityType = isset($_SESSION['user_id']) ? 'user' : 'student';
$entityId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['stud_id'];

$stmt = $conn->prepare("SELECT actor_id FROM actor WHERE entity_type = ? AND entity_id = ?");
$stmt->execute([$entityType, $entityId]);
$actor = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$actor) {
    die("Actor not found.");
}
$currentActorId = $actor['actor_id'];

// Validate POST inputs
$forumId = $_POST['forum_id'] ?? null;
$targetActorId = $_POST['actor_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$forumId || !$targetActorId || !$action) {
    die("Missing required data.");
}

// Check if current user is Admin or Moderator
$stmt = $conn->prepare("SELECT role FROM forum_membership WHERE forum_id = ? AND actor_id = ? AND status = 'Active'");
$stmt->execute([$forumId, $currentActorId]);
$currentMembership = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentMembership || !in_array($currentMembership['role'], ['Admin', 'Moderator'])) {
    die("Permission denied.");
}

// Prevent user from modifying themselves
if ($currentActorId == $targetActorId) {
    die("You cannot perform actions on yourself.");
}

// Handle actions
switch ($action) {
    case 'update_role':
        $newRole = $_POST['new_role'] ?? 'Member';
        if (!in_array($newRole, ['Member', 'Moderator', 'Admin'])) {
            die("Invalid role.");
        }

        // Admins can assign any role, Moderators can only assign Member
        if ($currentMembership['role'] !== 'Admin' && $newRole !== 'Member') {
            die("Only Admins can assign higher roles.");
        }

        $stmt = $conn->prepare("UPDATE forum_membership SET role = ? WHERE forum_id = ? AND actor_id = ?");
        $stmt->execute([$newRole, $forumId, $targetActorId]);
        break;

    case 'ban':
        $stmt = $conn->prepare("UPDATE forum_membership SET status = 'Banned', deleted_at = NOW() WHERE forum_id = ? AND actor_id = ?");
        $stmt->execute([$forumId, $targetActorId]);
        break;

    case 'unban':
        $stmt = $conn->prepare("UPDATE forum_membership SET status = 'Active', deleted_at = NULL WHERE forum_id = ? AND actor_id = ?");
        $stmt->execute([$forumId, $targetActorId]);
        break;

    case 'remove':
        $stmt = $conn->prepare("UPDATE forum_membership SET status = 'Left', deleted_at = NOW() WHERE forum_id = ? AND actor_id = ?");
        $stmt->execute([$forumId, $targetActorId]);
        break;

    case 'approve':
        // Approve pending membership
        $stmt = $conn->prepare("UPDATE forum_membership SET status = 'Active', deleted_at = NULL WHERE forum_id = ? AND actor_id = ? AND status = 'Pending'");
        $stmt->execute([$forumId, $targetActorId]);
        break;

    default:
        die("Unknown action.");
}

// Redirect back to manage page
header("Location: manage_forum.php?forum_id=" . urlencode($forumId));
exit;
?>
