<?php
require_once '../config/dbcon.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['status' => 'error', 'message' => 'Invalid action'];
$action = $_REQUEST['action'] ?? null;

// Get actor ID from session or create if doesn't exist
function getCurrentActorId($conn) {
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
        return null;
    }

    $entityType = isset($_SESSION['user_id']) ? 'user' : 'student';
    $entityId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['stud_id'];

    $stmt = $conn->prepare("SELECT actor_id FROM actor WHERE entity_type = :entityType AND entity_id = :entityId");
    $stmt->execute([':entityType' => $entityType, ':entityId' => $entityId]);
    
    if ($stmt->rowCount() > 0) {
        return $stmt->fetch(PDO::FETCH_ASSOC)['actor_id'];
    }

    // Create new actor if not exists
    $stmt = $conn->prepare("INSERT INTO actor (entity_type, entity_id) VALUES (:entityType, :entityId)");
    $stmt->execute([':entityType' => $entityType, ':entityId' => $entityId]);
    return $conn->lastInsertId();
}

// Get actor ID for any entity
function getActorId($entityType, $entityId, $conn) {
    $stmt = $conn->prepare("SELECT actor_id FROM actor WHERE entity_type = :entityType AND entity_id = :entityId");
    $stmt->execute([':entityType' => $entityType, ':entityId' => $entityId]);
    
    if ($stmt->rowCount() > 0) {
        return $stmt->fetch(PDO::FETCH_ASSOC)['actor_id'];
    }

    // Create new actor if not exists
    $stmt = $conn->prepare("INSERT INTO actor (entity_type, entity_id) VALUES (:entityType, :entityId)");
    $stmt->execute([':entityType' => $entityType, ':entityId' => $entityId]);
    return $conn->lastInsertId();
}

$currentActorId = getCurrentActorId($conn);
if (!$currentActorId && $action !== 'get_user_info') {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}







// Function to get notifications for the current actor
function getNotifications($actorId, $conn, $limit = 10, $unreadOnly = false) {
    try {
        $sql = "SELECT * FROM notification 
                WHERE actor_id = :actorId 
                AND deleted_at IS NULL";
        
        if ($unreadOnly) {
            $sql .= " AND is_read = FALSE";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT :limit";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':actorId', $actorId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting notifications: " . $e->getMessage());
        return [];
    }
}

// Function to mark notifications as read
function markNotificationsAsRead($notificationIds, $conn) {
    if (empty($notificationIds)) {
        return false;
    }
    
    try {
        $placeholders = implode(',', array_fill(0, count($notificationIds), '?'));
        $sql = "UPDATE notification SET is_read = TRUE 
                WHERE notification_id IN ($placeholders)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($notificationIds);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error marking notifications as read: " . $e->getMessage());
        return false;
    }
}

// Example usage to get notifications
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_notifications') {
    $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
    $lastCheck = isset($_GET['last_check']) ? $_GET['last_check'] : null;

    try {
        // Build base query
        $sql = "SELECT * FROM notification 
                WHERE actor_id = :actorId 
                AND deleted_at IS NULL";

        if ($unreadOnly) {
            $sql .= " AND is_read = FALSE";
        }

        if (!empty($lastCheck)) {
            $sql .= " AND created_at > :lastCheck";
        }

        $sql .= " ORDER BY created_at DESC LIMIT 10";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':actorId', $currentActorId, PDO::PARAM_INT);

        if (!empty($lastCheck)) {
            $stmt->bindValue(':lastCheck', $lastCheck);
        }

        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response = [
            'status' => 'success',
            'new_count' => count($notifications),
            'latest_notification' => !empty($notifications) ? $notifications[0] : null
        ];
    } catch (PDOException $e) {
        $response = [
            'status' => 'error',
            'message' => 'Failed to fetch notifications',
            'error' => $e->getMessage()
        ];
    }

    echo json_encode($response);
    exit;
}

// Example usage to mark notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_as_read') {
    if (!empty($_POST['notification_ids'])) {
        $notificationIds = is_array($_POST['notification_ids']) 
            ? $_POST['notification_ids'] 
            : [$_POST['notification_ids']];
        
        $success = markNotificationsAsRead($notificationIds, $conn);
        
        $response = [
            'status' => $success ? 'success' : 'error',
            'message' => $success ? 'Notifications marked as read' : 'Failed to mark notifications as read'
        ];
    } else {
        $response = ['status' => 'error', 'message' => 'No notification IDs provided'];
    }
    
    echo json_encode($response);
    exit;
}


?>
