<?php
// Set JSON content type
header('Content-Type: application/json');

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/dbcon.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize response
$response = [
    'success' => false,
    'new_count' => 0,
    'latest_notification' => null,
    'error' => null
];

// Check if required parameters are provided
$action = $_GET['action'] ?? '';
$actor_id = filter_input(INPUT_GET, 'actor_id', FILTER_VALIDATE_INT) ?: 0;
$last_check = $_GET['last_check'] ?? null;

if ($action !== 'get_notifications' || $actor_id <= 0) {
    $response['error'] = 'Invalid action or actor_id';
    echo json_encode($response);
    exit;
}

// Determine reference_type and reference_id based on session
if (isset($_SESSION['user_id'])) {
    $reference_type = 'user';
    $reference_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['stud_id'])) {
    $reference_type = 'student';
    $reference_id = $_SESSION['stud_id'];
} else {
    $response['error'] = 'User not authenticated';
    echo json_encode($response);
    exit;
}

try {
    // Fetch new notifications since last_check
    // Option 1: Use reference_type and reference_id (if your notification table uses these for the recipient)
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
        AND n.is_read = FALSE";
    
    // Add time filter if last_check is provided
    if ($last_check) {
        $query .= " AND n.created_at > ?";
        $params = [$reference_type, $reference_id, $last_check];
    } else {
        $params = [$reference_type, $reference_id];
    }
    
    $query .= " ORDER BY n.created_at DESC LIMIT 1";

    /* 
    // Option 2: Use actor_id 
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
        WHERE n.actor_id = ? 
        AND n.deleted_at IS NULL
        AND n.is_read = FALSE";
    if ($last_check) {
        $query .= " AND n.created_at > ?";
        $params = [$actor_id, $last_check];
    } else {
        $params = [$actor_id];
    }
    $query .= " ORDER BY n.created_at DESC LIMIT 1";
    */

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($notifications) {
        $notification = $notifications[0];
        // Process actor name
        if ($notification['actor_id'] && $notification['actor_first_name'] && $notification['actor_last_name']) {
            $actor_name = trim($notification['actor_first_name'] . ' ' . $notification['actor_last_name']);
            $notification['message'] = str_replace('{actor}', htmlspecialchars($actor_name), $notification['message']);
        } else {
            $notification['message'] = str_replace('{actor}', 'Someone', $notification['message']);
        }

        $response['success'] = true;
        $response['new_count'] = count($notifications); // Will be 1 due to LIMIT 1
        $response['latest_notification'] = [
            'notification_type' => $notification['notification_type'],
            'message' => $notification['message'],
            'action_url' => $notification['action_url'] ?? null
        ];
    }

} catch (PDOException $e) {
    $response['error'] = 'Database error: ' . $e->getMessage();
    error_log('Notifications controller error: ' . $e->getMessage());
}

echo json_encode($response);
exit;
?>