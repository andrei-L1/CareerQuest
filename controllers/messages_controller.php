<?php

require_once '../config/dbcon.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/../vendor/autoload.php';

$options = array(
  'cluster' => 'ap1',
  'useTLS' => true
);
$pusher = new Pusher\Pusher(
  'd9d029433bbefa08b6a2',
  'a7bb805091608ca9cc5d',
  '1971903',
  $options
);

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid action'];

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

switch ($action) {
        case 'send_message':
            $receiverType = $_POST['receiver_type'];
            $receiverId = (int)$_POST['receiver_id'];
            $content = $_POST['content'];
            $threadId = isset($_POST['thread_id']) ? (int)$_POST['thread_id'] : null;
        
            $receiverActorId = getActorId($receiverType, $receiverId, $conn);
        
            if (!$threadId) {
                // Check if a thread already exists between these two participants
                $stmt = $conn->prepare("
                    SELECT tp1.thread_id
                    FROM thread_participants tp1
                    JOIN thread_participants tp2 ON tp1.thread_id = tp2.thread_id
                    WHERE tp1.actor_id = :actorId1 AND tp2.actor_id = :actorId2
                    LIMIT 1
                ");
                $stmt->execute([
                    ':actorId1' => $currentActorId,
                    ':actorId2' => $receiverActorId
                ]);
                
                if ($stmt->rowCount() > 0) {
                    // Existing thread found
                    $threadId = $stmt->fetch(PDO::FETCH_ASSOC)['thread_id'];
                } else {
                    // Create new thread
                    $conn->beginTransaction();
                    try {
                        $stmt = $conn->prepare("INSERT INTO thread (thread_group) VALUES ('Direct Message')");
                        $stmt->execute();
                        $threadId = $conn->lastInsertId();
                        
                        // Add participants
                        $stmt = $conn->prepare("INSERT INTO thread_participants (thread_id, actor_id) VALUES (:threadId, :actorId)");
                        $stmt->execute([':threadId' => $threadId, ':actorId' => $currentActorId]);
                        $stmt->execute([':threadId' => $threadId, ':actorId' => $receiverActorId]);
                        
                        $conn->commit();
                    } catch (Exception $e) {
                        $conn->rollBack();
                        $response = ['status' => 'error', 'message' => 'Failed to create thread'];
                        break;
                    }
                }
            }
        
            $stmt = $conn->prepare("INSERT INTO message (content, sender_id, receiver_id, thread_id) VALUES (:content, :senderId, :receiverId, :threadId)");
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':senderId', $currentActorId, PDO::PARAM_INT);
            $stmt->bindParam(':receiverId', $receiverActorId, PDO::PARAM_INT);
            $stmt->bindParam(':threadId', $threadId, PDO::PARAM_INT);
        
            if ($stmt->execute()) {

                $pusher->trigger('thread_' . $threadId, 'new_message', [
                    'content' => $content,
                    'sender_id' => $currentActorId,
                    'thread_id' => $threadId,
                ]);
                $response = ['status' => 'success', 'thread_id' => $threadId];
            } else {
                $response['message'] = 'Failed to send message';
            }
            break;

        case 'get_threads':
            $query = "
                SELECT 
                    t.thread_id, 
                    t.thread_group, 
                    t.created_at, 
                    MAX(m.sent_at) as last_message_time,
                    (
                        SELECT content 
                        FROM message 
                        WHERE thread_id = t.thread_id 
                        ORDER BY sent_at DESC 
                        LIMIT 1
                    ) AS last_message_content,
                    (
                        SELECT COUNT(*) 
                        FROM message 
                        WHERE thread_id = t.thread_id 
                          AND receiver_id = :actorId 
                          AND is_read = FALSE
                    ) AS unread_count,
                    (
                        SELECT a.entity_type
                        FROM thread_participants tp
                        JOIN actor a ON tp.actor_id = a.actor_id
                        WHERE tp.thread_id = t.thread_id AND tp.actor_id != :actorId
                        LIMIT 1
                    ) AS entity_type,
                    (
                        SELECT a.entity_id
                        FROM thread_participants tp
                        JOIN actor a ON tp.actor_id = a.actor_id
                        WHERE tp.thread_id = t.thread_id AND tp.actor_id != :actorId
                        LIMIT 1
                    ) AS entity_id
                FROM thread t
                JOIN thread_participants tp ON t.thread_id = tp.thread_id
                JOIN message m ON t.thread_id = m.thread_id
                WHERE tp.actor_id = :actorId
                GROUP BY t.thread_id
                ORDER BY last_message_time DESC
            ";
        
            $stmt = $conn->prepare($query);
            $stmt->execute([':actorId' => $currentActorId]);
            $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
            foreach ($threads as &$thread) {
                $entityType = $thread['entity_type'];
                $entityId = $thread['entity_id'];
        
                if ($entityType === 'student') {
                    $infoStmt = $conn->prepare("SELECT CONCAT(stud_first_name, ' ', stud_last_name) AS name, profile_picture AS picture FROM student WHERE stud_id = :id");
                } else {
                    $infoStmt = $conn->prepare("SELECT CONCAT(user_first_name, ' ', user_last_name) AS name, picture_file AS picture FROM user WHERE user_id = :id");
                }
        
                $infoStmt->execute([':id' => $entityId]);
                $info = $infoStmt->fetch(PDO::FETCH_ASSOC);
        
                $thread['participant_name'] = $info['name'] ?? 'Unknown';
                $thread['participant_picture'] = isset($info['picture']) ? '../uploads/' . $info['picture'] : null;
            }
        
            $response = ['status' => 'success', 'threads' => $threads];
            break;
        

            case 'get_messages':
                $threadId = (int)$_POST['thread_id'];
            
                // Mark messages as read
                $stmt = $conn->prepare("UPDATE message SET is_read = TRUE WHERE thread_id = :threadId AND receiver_id = :actorId AND is_read = FALSE");
                $stmt->execute([':threadId' => $threadId, ':actorId' => $currentActorId]);
            
                $query = "
                    SELECT m.message_id, m.content, m.sent_at, m.is_read,
                           a.entity_type as sender_type, a.entity_id as sender_id
                    FROM message m
                    JOIN actor a ON m.sender_id = a.actor_id
                    WHERE m.thread_id = :threadId AND m.deleted_at IS NULL
                    ORDER BY m.sent_at ASC
                ";
            
                $stmt = $conn->prepare($query);
                $stmt->execute([':threadId' => $threadId]);
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
                // Get participant info (the other person in the conversation)
                $participantQuery = "
                    SELECT a.entity_type, a.entity_id
                    FROM thread_participants tp
                    JOIN actor a ON tp.actor_id = a.actor_id
                    WHERE tp.thread_id = :threadId AND tp.actor_id != :actorId
                    LIMIT 1
                ";
                $stmt = $conn->prepare($participantQuery);
                $stmt->execute([':threadId' => $threadId, ':actorId' => $currentActorId]);
                $participant = $stmt->fetch(PDO::FETCH_ASSOC);
            
                $response = [
                    'status' => 'success', 
                    'messages' => $messages,
                    'participant' => $participant
                ];
                break;

    case 'get_user_info':
        $entityType = $_POST['entity_type'];
        $entityId = (int)$_POST['entity_id'];

        if ($entityType === 'user') {
            $stmt = $conn->prepare("SELECT user_first_name as name, user_email as email, picture_file as picture FROM user WHERE user_id = :entityId");
        } else {
            $stmt = $conn->prepare("SELECT stud_first_name as name, stud_email as email, profile_picture as picture FROM student WHERE stud_id = :entityId");
        }
        $stmt->execute([':entityId' => $entityId]);
        $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        $response = ['status' => 'success', 'user' => $userInfo];
        break;

        // Add this to your existing switch-case structure
        case 'search_users':
            $term = $_POST['term'] ?? '';
            $current_user_type = $_POST['current_user_type'] ?? '';
            $current_user_id = $_POST['current_user_id'] ?? 0;
            
            // Sanitize input
            $term = '%' . $term . '%';
            
            $results = [];
            
            // Search students (if current user is not a student)
            // Allow searching for students and users by everyone except Admin/Moderator
            $stmt = $conn->prepare("SELECT 
                'student' as entity_type, 
                stud_id as entity_id, 
                CONCAT(stud_first_name, ' ', stud_last_name) as name, 
                'Student' as role,
                profile_picture as picture
                FROM student 
                WHERE (stud_first_name LIKE ? OR stud_last_name LIKE ? OR stud_email LIKE ?)
                AND stud_id != ? 
                AND status = 'Active'
                LIMIT 10");
            $stmt->execute([$term, $term, $term, $current_user_id]);
            $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
        
            // Search users (exclude Admins and Moderators)
            $stmt = $conn->prepare("SELECT 
                'user' as entity_type, 
                user_id as entity_id, 
                CONCAT(user_first_name, ' ', user_last_name) as name, 
                user_type as role,
                picture_file as picture
                FROM user 
                WHERE (user_first_name LIKE ? OR user_last_name LIKE ? OR user_email LIKE ?)
                AND user_id != ? 
                AND status = 'Active'
                AND user_type NOT IN ('Admin', 'Moderator')  -- Exclude Admins and Moderators
                LIMIT 10");
            $stmt->execute([$term, $term, $term, $current_user_id]);
            $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
        
            $response = ['status' => 'success', 'users' => $results];
            break;
            
            
    
}

echo json_encode($response);




