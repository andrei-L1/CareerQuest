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
$query = "SELECT is_private, title FROM forum WHERE forum_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$forumId]);
$forum = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$forum) {
    header("Location: ../dashboard/forums.php?error=forum_not_found");
    exit;
}

$isPrivate = $forum['is_private'] == 1;
$newStatus = $isPrivate ? 'Pending' : 'Active';

// Get user display name for notification
$nameQuery = "SELECT CONCAT(COALESCE(u.user_first_name, s.stud_first_name), ' ', COALESCE(u.user_last_name, s.stud_last_name)) AS display_name
              FROM actor a
              LEFT JOIN user u ON a.entity_type = 'user' AND a.entity_id = u.user_id
              LEFT JOIN student s ON a.entity_type = 'student' AND a.entity_id = s.stud_id
              WHERE a.actor_id = ?";
$nameStmt = $conn->prepare($nameQuery);
$nameStmt->execute([$actorId]);
$userName = $nameStmt->fetchColumn() ?: 'User';

// Check membership record
$query = "SELECT membership_id, status FROM forum_membership WHERE forum_id = ? AND actor_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$forumId, $actorId]);
$membership = $stmt->fetch(PDO::FETCH_ASSOC);

$notificationCreated = false;

if (!$membership) {
    // No record found, insert new membership
    $insert = $conn->prepare("INSERT INTO forum_membership (forum_id, actor_id, role, status) VALUES (?, ?, 'Member', ?)");
    $insert->execute([$forumId, $actorId, $newStatus]);
    $notificationCreated = true;
} elseif ($membership['status'] === 'Left') {
    // Rejoining: update status
    $update = $conn->prepare("UPDATE forum_membership SET status = ?, deleted_at = NULL WHERE membership_id = ?");
    $update->execute([$newStatus, $membership['membership_id']]);
    $notificationCreated = true;
}

// Create notification for forum moderators/admins if membership was created or updated
// Create notification for forum moderators/admins if membership was created or updated
if ($notificationCreated) {
    // Get forum moderators and admins
    $modQuery = "SELECT actor_id FROM forum_membership WHERE forum_id = ? AND role IN ('Moderator', 'Admin') AND status = 'Active'";
    $modStmt = $conn->prepare($modQuery);
    $modStmt->execute([$forumId]);
    $moderators = $modStmt->fetchAll(PDO::FETCH_ASSOC);

    $message = $isPrivate
        ? "$userName has requested to join the forum '{$forum['title']}'."
        : "$userName has joined the forum '{$forum['title']}'.";
    $notificationType = $isPrivate ? 'membership_request' : 'membership_joined';
    
    // Determine the user's profile URL based on their entity type
    if ($entityType === 'user') {
        $profileUrl = "../admin/view_user.php?id=$entityId"; // Adjust this to your actual user profile URL
    } else {
        $profileUrl = "../admin/view_student.php?id=$entityId"; // Adjust this to your actual student profile URL
    }
    
    $actionUrl = "../forum/manage_forum.php?forum_id=$forumId&user_id=$entityId&user_type=$entityType";

    foreach ($moderators as $moderator) {
        $notify = $conn->prepare("
            INSERT INTO notification (actor_id, message, notification_type, action_url, reference_type, reference_id)
            VALUES (?, ?, ?, ?, 'forum_membership', ?)
        ");
        $notify->execute([
            $moderator['actor_id'],
            $message,
            $notificationType,
            $actionUrl,
            $forumId
        ]);
    }

    // If no moderators exist, notify the system admin
    if (empty($moderators)) {
        $adminQuery = "SELECT a.actor_id 
                       FROM actor a 
                       JOIN user u ON a.entity_type = 'user' AND a.entity_id = u.user_id 
                       JOIN role r ON u.role_id = r.role_id 
                       WHERE r.role_title = 'Admin'";
        $adminStmt = $conn->prepare($adminQuery);
        $adminStmt->execute();
        $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($admins as $admin) {
            $notify = $conn->prepare("
                INSERT INTO notification (actor_id, message, notification_type, action_url, reference_type, reference_id)
                VALUES (?, ?, ?, ?, 'forum_membership', ?)
            ");
            $notify->execute([
                $admin['actor_id'],
                $message,
                $notificationType,
                $actionUrl,
                $forumId
            ]);
        }
    }
}

// Redirect to forum page
header("Location: ../dashboard/forums.php?forum_id=" . $forumId);
exit;
?>