<?php
require_once '../config/dbcon.php';
/** @var PDO $conn */
if (session_status() === PHP_SESSION_NONE) session_start();

// Generate CSRF token if not already set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
    die("Error: Actor not found.");
}
$currentActorId = $actor['actor_id'];

// Validate POST inputs
$forumId = $_POST['forum_id'] ?? null;
$targetActorId = $_POST['actor_id'] ?? null;
$action = $_POST['action'] ?? null;

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Error: Invalid CSRF token.");
}

if (!$forumId || !$targetActorId || !$action) {
    die("Error: Missing required data.");
}

// Check if current user is Admin or Moderator
$stmt = $conn->prepare("SELECT role FROM forum_membership WHERE forum_id = ? AND actor_id = ? AND status = 'Active'");
$stmt->execute([$forumId, $currentActorId]);
$currentMembership = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentMembership || !in_array($currentMembership['role'], ['Admin', 'Moderator'])) {
    die("Error: Permission denied.");
}

// Prevent user from modifying themselves
if ($currentActorId == $targetActorId) {
    die("Error: You cannot perform actions on yourself.");
}

// Handle actions
try {
    switch ($action) {
        case 'update_role':
            $newRole = $_POST['new_role'] ?? null;
            if (!in_array($newRole, ['Member', 'Moderator', 'Admin'])) {
                die("Error: Invalid role specified.");
            }

            // Admins can assign any role, Moderators can only assign Member
            if ($currentMembership['role'] !== 'Admin' && $newRole !== 'Member') {
                die("Error: Only Admins can assign Moderator or Admin roles.");
            }

            $stmt = $conn->prepare("UPDATE forum_membership SET role = ? WHERE forum_id = ? AND actor_id = ?");
            $success = $stmt->execute([$newRole, $forumId, $targetActorId]);
            if (!$success || $stmt->rowCount() === 0) {
                die("Error: Failed to update role. No matching record found.");
            }
            break;

        case 'ban':
            $stmt = $conn->prepare("UPDATE forum_membership SET status = 'Banned', deleted_at = NOW() WHERE forum_id = ? AND actor_id = ?");
            $success = $stmt->execute([$forumId, $targetActorId]);
            if (!$success || $stmt->rowCount() === 0) {
                die("Error: Failed to ban member.");
            }
            break;

        case 'unban':
            $stmt = $conn->prepare("UPDATE forum_membership SET status = 'Active', deleted_at = NULL WHERE forum_id = ? AND actor_id = ?");
            $success = $stmt->execute([$forumId, $targetActorId]);
            if (!$success || $stmt->rowCount() === 0) {
                die("Error: Failed to unban member.");
            }
            break;

        case 'remove':
            $stmt = $conn->prepare("UPDATE forum_membership SET status = 'Left', deleted_at = NOW() WHERE forum_id = ? AND actor_id = ?");
            $success = $stmt->execute([$forumId, $targetActorId]);
            if (!$success || $stmt->rowCount() === 0) {
                die("Error: Failed to remove member.");
            }
            break;

        case 'approve':
            $stmt = $conn->prepare("UPDATE forum_membership SET status = 'Active', deleted_at = NULL WHERE forum_id = ? AND actor_id = ? AND status = 'Pending'");
            $success = $stmt->execute([$forumId, $targetActorId]);
            if (!$success || $stmt->rowCount() === 0) {
                die("Error: Failed to approve member.");
            }
            break;

        default:
            die("Error: Unknown action.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Redirect back to manage page with success message
header("Location: manage_forum.php?forum_id=" . urlencode($forumId) . "&message=" . urlencode("Action completed successfully."));
exit;
?>