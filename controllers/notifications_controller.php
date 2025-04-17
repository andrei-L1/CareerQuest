<?php
require_once '../config/dbcon.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if both user_id and stud_id are not set simultaneously
if (isset($_SESSION['user_id']) && isset($_SESSION['stud_id'])) {
    echo "Error: Both user and student IDs are set. Only one should be set.";
    exit;
}

// Check if neither user_id nor stud_id is set
if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit;
}

// Initialize current user data (unchanged from your original code)
if (isset($_SESSION['user_id'])) {
    $currentUser = [
        'entity_type' => 'user',
        'entity_id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_first_name'] ?? 'User',
        'role' => $_SESSION['user_type'] ?? 'Unknown',
        'email' => $_SESSION['user_email'] ?? '',
        'picture' => $_SESSION['picture_file'] ?? ''
    ];

    $user_id = $currentUser['entity_id'];
    $query = "SELECT * FROM user WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $userDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userDetails) {
        $currentUser['full_name'] = $userDetails['user_first_name'] . ' ' . $userDetails['user_last_name'];
        $currentUser['email'] = $userDetails['user_email'];
        $currentUser['picture'] = $userDetails['picture_file'];
        $currentUser['status'] = $userDetails['status'];
    }
} else {
    $currentUser = [
        'entity_type' => 'student',
        'entity_id' => $_SESSION['stud_id'],
        'name' => $_SESSION['stud_first_name'] ?? 'Student',
        'role' => 'Student',
        'email' => $_SESSION['stud_email'] ?? '',
        'picture' => $_SESSION['profile_picture'] ?? ''
    ];

    $stud_id = $currentUser['entity_id'];
    $query = "SELECT * FROM student WHERE stud_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$stud_id]);
    $studentDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($studentDetails) {
        $currentUser['full_name'] = $studentDetails['stud_first_name'] . ' ' . $studentDetails['stud_last_name'];
        $currentUser['email'] = $studentDetails['stud_email'];
        $currentUser['picture'] = $studentDetails['profile_picture'];
        $currentUser['status'] = $studentDetails['status'];
    }
}

// Set reference type and ID
$reference_type = $currentUser['entity_type'];
$reference_id = $currentUser['entity_id'];

// Handle actions (enhanced with bulk actions)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_all_read'])) {
        $stmt = $conn->prepare("UPDATE notification SET is_read = TRUE WHERE reference_type = ? AND reference_id = ? AND deleted_at IS NULL");
        $stmt->execute([$reference_type, $reference_id]);
        $_SESSION['success_message'] = "All notifications marked as read";
        header("Location: notifications.php");
        exit;
    }
    
    if (isset($_POST['delete_all'])) {
        $stmt = $conn->prepare("UPDATE notification SET deleted_at = CURRENT_TIMESTAMP WHERE reference_type = ? AND reference_id = ? AND deleted_at IS NULL");
        $stmt->execute([$reference_type, $reference_id]);
        $_SESSION['success_message'] = "All notifications deleted";
        header("Location: notifications.php");
        exit;
    }
}

// Handle single notification actions (unchanged)
if (isset($_GET['notification_id'])) {
    $notification_id = $_GET['notification_id'];
    $stmt = $conn->prepare("UPDATE notification SET is_read = TRUE WHERE notification_id = ?");
    $stmt->execute([$notification_id]);
    header("Location: notifications.php");
    exit;
}

if (isset($_GET['delete_notification_id'])) {
    $notification_id = $_GET['delete_notification_id'];
    $stmt = $conn->prepare("UPDATE notification SET deleted_at = CURRENT_TIMESTAMP WHERE notification_id = ?");
    $stmt->execute([$notification_id]);
    header("Location: notifications.php");
    exit;
}

// Fetch notifications (unchanged query)
$query = "
    SELECT n.*, 
        a.entity_type AS actor_entity_type,
        a.entity_id AS actor_entity_id,
        COALESCE(u.user_first_name, s.stud_first_name) AS actor_first_name,
        COALESCE(u.user_last_name, s.stud_last_name) AS actor_last_name,
        COALESCE(u.picture_file, s.profile_picture) AS actor_picture
    FROM notification n
    LEFT JOIN actor a ON n.actor_id = a.actor_id
    LEFT JOIN user u ON a.entity_type = 'user' AND a.entity_id = u.user_id
    LEFT JOIN student s ON a.entity_type = 'student' AND a.entity_id = s.stud_id
    WHERE n.reference_type = ? 
    AND n.reference_id = ? 
    AND n.deleted_at IS NULL
    ORDER BY n.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute([$reference_type, $reference_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process notifications (unchanged)
foreach ($notifications as &$notification) {
    if ($notification['actor_id']) {
        $actor_name = $notification['actor_first_name'] . ' ' . $notification['actor_last_name'];
        $notification['message'] = str_replace('{actor}', $actor_name, $notification['message']);
    }
}
unset($notification);

// Count unread notifications
$unread_count = 0;
foreach ($notifications as $n) {
    if (!$n['is_read']) $unread_count++;
}
?>