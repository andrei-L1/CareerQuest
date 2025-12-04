<?php
require_once '../config/dbcon.php';
/** @var PDO $conn */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
    $response['message'] = 'You must be logged in to resolve reports.';
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

$moderator_id = $actor['actor_id'];

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

$forumModeratorForums = [];
$query = "SELECT forum_id FROM forum_membership WHERE actor_id = ? AND role IN ('Moderator', 'Admin') AND status = 'Active'";
$stmt = $conn->prepare($query);
$stmt->execute([$moderator_id]);
$forumModeratorForums = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'forum_id');

if (!$isModerator && empty($forumModeratorForums)) {
    $response['message'] = 'You do not have permission to resolve reports.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$report_id = $_POST['report_id'] ?? '';
$resolution = $_POST['resolution'] ?? '';
$edited_content = $_POST['edited_content'] ?? null;

if (empty($report_id) || empty($resolution) || !in_array($resolution, ['approved', 'edited', 'deleted'])) {
    $response['message'] = 'Invalid report ID or resolution.';
    echo json_encode($response);
    exit;
}

// Fetch report details
$query = "SELECT content_type, content_id FROM report WHERE report_id = ? AND status = 'pending'";
$stmt = $conn->prepare($query);
$stmt->execute([$report_id]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    $response['message'] = 'Report not found or already resolved.';
    echo json_encode($response);
    exit;
}

$content_type = $report['content_type'];
$content_id = $report['content_id'];

// Validate moderator permissions for non-system moderators
if (!$isModerator) {
    if ($content_type !== 'user') {
        $query = "SELECT f.forum_id 
                  FROM forum_post fp 
                  LEFT JOIN forum_comment fc ON fc.post_id = fp.post_id 
                  LEFT JOIN forum f ON fp.forum_id = f.forum_id 
                  WHERE (fp.post_id = ? AND ? = 'post') OR (fc.comment_id = ? AND ? = 'comment')";
        $stmt = $conn->prepare($query);
        $stmt->execute([$content_id, $content_type, $content_id, $content_type]);
        $forum = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$forum || !in_array($forum['forum_id'], $forumModeratorForums)) {
            $response['message'] = 'You do not have permission to resolve this report.';
            echo json_encode($response);
            exit;
        }
    }
}

// Handle resolution based on content type
try {
    $conn->beginTransaction();

    if ($content_type === 'post') {
        if ($resolution === 'deleted') {
            $query = "UPDATE forum_post SET deleted_at = NOW() WHERE post_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$content_id]);
        } elseif ($resolution === 'edited' && $edited_content !== null) {
            $query = "UPDATE forum_post SET content = ?, updated_at = NOW() WHERE post_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$edited_content, $content_id]);
        }
    } elseif ($content_type === 'comment') {
        if ($resolution === 'deleted') {
            $query = "UPDATE forum_comment SET deleted_at = NOW() WHERE comment_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$content_id]);
        } elseif ($resolution === 'edited' && $edited_content !== null) {
            $query = "UPDATE forum_comment SET content = ?, updated_at = NOW() WHERE comment_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$edited_content, $content_id]);
        }
    } elseif ($content_type === 'user') {
        if ($resolution === 'deleted') {
            $query = "UPDATE actor SET status = 'Suspended' WHERE actor_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$content_id]);
        } elseif ($resolution === 'edited') {
            // Send a warning notification
            $warning_message = "You have received a warning due to a report: " . ($_POST['reason'] ?? 'Inappropriate behavior.');
            $query = "INSERT INTO notification (actor_id, message, notification_type, reference_type, reference_id, created_at) 
                      VALUES (?, ?, 'warning', 'report', ?, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->execute([$content_id, $warning_message, $report_id]);
        }
    }

    // Update report status
    $query = "UPDATE report SET status = 'resolved', resolution = ?, resolved_at = NOW(), moderator_id = ? WHERE report_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$resolution, $moderator_id, $report_id]);

    $conn->commit();
    $response['success'] = true;
    $response['message'] = 'Report resolved successfully!';
} catch (Exception $e) {
    $conn->rollBack();
    $response['message'] = 'An error occurred: ' . $e->getMessage();
}

echo json_encode($response);
?>