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

// Helper function to execute SQL statements with error logging
function executeStmt($stmt, $params) {
    try {
        $stmt->execute($params);
        error_log('SQL executed successfully: ' . $stmt->queryString);
    } catch (PDOException $e) {
        error_log('SQL error: ' . $e->getMessage() . ' Query: ' . $stmt->queryString);
        throw $e;
    }
}

// Get actor ID from session or create if doesn't exist
function getCurrentActorId($conn) {
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
        return null;
    }

    $entityType = isset($_SESSION['user_id']) ? 'user' : 'student';
    $entityId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['stud_id'];

    $stmt = $conn->prepare("SELECT actor_id FROM actor WHERE entity_type = :entityType AND entity_id = :entityId");
    executeStmt($stmt, [':entityType' => $entityType, ':entityId' => $entityId]);
    
    if ($stmt->rowCount() > 0) {
        return $stmt->fetch(PDO::FETCH_ASSOC)['actor_id'];
    }

    $stmt = $conn->prepare("INSERT INTO actor (entity_type, entity_id) VALUES (:entityType, :entityId)");
    executeStmt($stmt, [':entityType' => $entityType, ':entityId' => $entityId]);
    return $conn->lastInsertId();
}

// Get actor ID for any entity
function getActorId($entityType, $entityId, $conn) {
    $stmt = $conn->prepare("SELECT actor_id FROM actor WHERE entity_type = :entityType AND entity_id = :entityId");
    executeStmt($stmt, [':entityType' => $entityType, ':entityId' => $entityId]);
    
    if ($stmt->rowCount() > 0) {
        return $stmt->fetch(PDO::FETCH_ASSOC)['actor_id'];
    }

    $stmt = $conn->prepare("INSERT INTO actor (entity_type, entity_id) VALUES (:entityType, :entityId)");
    executeStmt($stmt, [':entityType' => $entityType, ':entityId' => $entityId]);
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
            $stmt = $conn->prepare("
                SELECT tp1.thread_id
                FROM thread_participants tp1
                JOIN thread_participants tp2 ON tp1.thread_id = tp2.thread_id
                WHERE tp1.actor_id = :actorId1 AND tp2.actor_id = :actorId2
                LIMIT 1
            ");
            executeStmt($stmt, [
                ':actorId1' => $currentActorId,
                ':actorId2' => $receiverActorId
            ]);
            
            if ($stmt->rowCount() > 0) {
                $threadId = $stmt->fetch(PDO::FETCH_ASSOC)['thread_id'];
            } else {
                $conn->beginTransaction();
                try {
                    $stmt = $conn->prepare("INSERT INTO thread (thread_group) VALUES ('Direct Message')");
                    executeStmt($stmt, []);
                    $threadId = $conn->lastInsertId();
                    
                    $stmt = $conn->prepare("INSERT INTO thread_participants (thread_id, actor_id) VALUES (:threadId, :actorId)");
                    executeStmt($stmt, [':threadId' => $threadId, ':actorId' => $currentActorId]);
                    executeStmt($stmt, [':threadId' => $threadId, ':actorId' => $receiverActorId]);
                    
                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollBack();
                    $response = ['status' => 'error', 'message' => 'Failed to create thread'];
                    break;
                }
            }
        }
    
        $stmt = $conn->prepare("INSERT INTO message (content, sender_id, receiver_id, thread_id) VALUES (:content, :senderId, :receiverId, :threadId)");
        executeStmt($stmt, [
            ':content' => $content,
            ':senderId' => $currentActorId,
            ':receiverId' => $receiverActorId,
            ':threadId' => $threadId
        ]);
        
        $pusher->trigger('thread_' . $threadId, 'new_message', [
            'content' => $content,
            'sender_id' => $currentActorId,
            'sender_type' => isset($_SESSION['user_id']) ? 'user' : 'student',
            'thread_id' => $threadId,
            'sent_at' => date('Y-m-d H:i:s')
        ]);
        
        $pusher->trigger('user_' . $currentActorId, 'update', [
            'type' => 'message',
            'thread_id' => $threadId,
            'action' => 'sent'
        ]);

        $pusher->trigger('user_' . $receiverActorId, 'update', [
            'type' => 'message',
            'thread_id' => $threadId,
            'action' => 'received'
        ]);
        
        $response = ['status' => 'success', 'thread_id' => $threadId];
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
        executeStmt($stmt, [':actorId' => $currentActorId]);
        $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        foreach ($threads as &$thread) {
            $entityType = $thread['entity_type'];
            $entityId = $thread['entity_id'];
    
            if ($entityType === 'student') {
                $infoStmt = $conn->prepare("SELECT CONCAT(stud_first_name, ' ', stud_last_name) AS name, profile_picture AS picture FROM student WHERE stud_id = :id");
            } else {
                $infoStmt = $conn->prepare("SELECT CONCAT(user_first_name, ' ', user_last_name) AS name, picture_file AS picture FROM user WHERE user_id = :id");
            }
    
            executeStmt($infoStmt, [':id' => $entityId]);
            $info = $infoStmt->fetch(PDO::FETCH_ASSOC);
    
            $thread['participant_name'] = $info['name'] ?? 'Unknown';
            $thread['participant_picture'] = isset($info['picture']) ? '../Uploads/' . $info['picture'] : null;
        }
    
        $response = ['status' => 'success', 'threads' => $threads];
        break;
    
    case 'mark_as_read':
        $threadId = (int)$_POST['thread_id'];
        $stmt = $conn->prepare("UPDATE message SET is_read = TRUE WHERE thread_id = :threadId AND receiver_id = :actorId AND is_read = FALSE");
        executeStmt($stmt, [':threadId' => $threadId, ':actorId' => $currentActorId]);
        
        $pusher->trigger('thread_' . $threadId, 'thread_update', [
            'thread_id' => $threadId,
            'action' => 'messages_read',
            'reader_id' => $currentActorId
        ]);
        $response = ['status' => 'success'];
        break;
        
    case 'get_messages':
        $threadId = (int)$_POST['thread_id'];
    
        $stmt = $conn->prepare("UPDATE message SET is_read = TRUE WHERE thread_id = :threadId AND receiver_id = :actorId AND is_read = FALSE");
        executeStmt($stmt, [':threadId' => $threadId, ':actorId' => $currentActorId]);
    
        $query = "
            SELECT m.message_id, m.content, m.sent_at, m.is_read,
                   a.entity_type as sender_type, a.entity_id as sender_id
            FROM message m
            JOIN actor a ON m.sender_id = a.actor_id
            WHERE m.thread_id = :threadId AND m.deleted_at IS NULL
            ORDER BY m.sent_at ASC
        ";
    
        $stmt = $conn->prepare($query);
        executeStmt($stmt, [':threadId' => $threadId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        $participantQuery = "
            SELECT a.entity_type, a.entity_id
            FROM thread_participants tp
            JOIN actor a ON tp.actor_id = a.actor_id
            WHERE tp.thread_id = :threadId AND tp.actor_id != :actorId
            LIMIT 1
        ";
        $stmt = $conn->prepare($participantQuery);
        executeStmt($stmt, [':threadId' => $threadId, ':actorId' => $currentActorId]);
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
        executeStmt($stmt, [':entityId' => $entityId]);
        $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        $response = ['status' => 'success', 'user' => $userInfo];
        break;

    case 'search_users':
        $term = $_POST['term'] ?? '';
        $current_user_type = $_POST['current_user_type'] ?? '';
        $current_user_id = $_POST['current_user_id'] ?? 0;
        
        $term = '%' . $term . '%';
        
        $results = [];
        
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
        executeStmt($stmt, [$term, $term, $term, $current_user_id]);
        $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
    
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
            AND user_type NOT IN ('Admin', 'Moderator')
            LIMIT 10");
        executeStmt($stmt, [$term, $term, $term, $current_user_id]);
        $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
    
        $response = ['status' => 'success', 'users' => $results];
        break;

    case 'initiate_video_call':
        $threadId = (int)$_POST['thread_id'];
        $receiverType = $_POST['receiver_type'];
        $receiverId = (int)$_POST['receiver_id'];
        $offer = $_POST['offer'];

        error_log('Received offer SDP for call initiation: ' . $offer);
        $receiverActorId = getActorId($receiverType, $receiverId, $conn);

        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT actor_id) as participant_count
            FROM thread_participants 
            WHERE thread_id = :threadId AND actor_id IN (:currentActorId, :receiverActorId)
        ");
        executeStmt($stmt, [
            ':threadId' => $threadId,
            ':currentActorId' => $currentActorId,
            ':receiverActorId' => $receiverActorId
        ]);

        $count = (int)$stmt->fetchColumn();
        if ($count < 2) {
            $response = ['status' => 'error', 'message' => 'Invalid thread or participants'];
            break;
        }

        $stmt = $conn->prepare("
            INSERT INTO video_calls (thread_id, initiator_actor_id, receiver_actor_id, status, offer_sdp)
            VALUES (:threadId, :initiatorId, :receiverId, 'initiated', :offer)
        ");
        executeStmt($stmt, [
            ':threadId' => $threadId,
            ':initiatorId' => $currentActorId,
            ':receiverId' => $receiverActorId,
            ':offer' => $offer
        ]);
        $callId = (int)$conn->lastInsertId();
        error_log('Created video call with call_id: ' . $callId . ', thread_id: ' . $threadId);

        $callerName = '';
        if (isset($_SESSION['user_id'])) {
            $stmt = $conn->prepare("SELECT CONCAT(user_first_name, ' ', user_last_name) as name FROM user WHERE user_id = :id");
            executeStmt($stmt, [':id' => $_SESSION['user_id']]);
            $callerName = $stmt->fetch(PDO::FETCH_ASSOC)['name'];
        } else {
            $stmt = $conn->prepare("SELECT CONCAT(stud_first_name, ' ', stud_last_name) as name FROM student WHERE stud_id = :id");
            executeStmt($stmt, [':id' => $_SESSION['stud_id']]);
            $callerName = $stmt->fetch(PDO::FETCH_ASSOC)['name'];
        }

        $videoCallPayload = [
            'call_id' => $callId,
            'thread_id' => $threadId,
            'caller_id' => $currentActorId,
            'caller_name' => $callerName
        ];
        $pusher->trigger('thread_' . $threadId, 'video_call', $videoCallPayload);
        $pusher->trigger('user_' . $receiverActorId, 'update', array_merge($videoCallPayload, [
            'type' => 'incoming_video_call'
        ]));

        $response = ['status' => 'success', 'call_id' => $callId];
        break;

    case 'accept_video_call':
        $callId = (int)$_POST['call_id'];
        $threadId = (int)$_POST['thread_id'];
        $answer = isset($_POST['answer']) ? $_POST['answer'] : null;

        if ($answer === null) {
            $stmt = $conn->prepare("
                SELECT initiator_actor_id, offer_sdp 
                FROM video_calls 
                WHERE call_id = :callId 
                  AND thread_id = :threadId 
                  AND receiver_actor_id = :actorId 
                  AND status = 'initiated'
            ");
            executeStmt($stmt, [
                ':callId' => $callId,
                ':threadId' => $threadId,
                ':actorId' => $currentActorId
            ]);

            if ($stmt->rowCount() === 0) {
                $response = ['status' => 'error', 'message' => 'Invalid call or not authorized'];
                break;
            }

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log('Retrieved offer SDP for call_id ' . $callId . ': ' . $row['offer_sdp']);
            $response = [
                'status' => 'success',
                'call_id' => $callId,
                'offer' => json_decode($row['offer_sdp'], true)
            ];
            break;
        }

        error_log('Received answer SDP for call_id ' . $callId . ': ' . $answer);
        $stmt = $conn->prepare("
            UPDATE video_calls 
            SET status = 'accepted', answer_sdp = :answer 
            WHERE call_id = :callId
              AND thread_id = :threadId
              AND receiver_actor_id = :actorId
              AND status IN ('initiated', 'accepted')
        ");
        executeStmt($stmt, [
            ':callId' => $callId,
            ':threadId' => $threadId,
            ':actorId' => $currentActorId,
            ':answer' => $answer
        ]);

        if ($stmt->rowCount() === 0) {
            $response = ['status' => 'error', 'message' => 'Invalid call or not authorized'];
            break;
        }

        $stmt = $conn->prepare("SELECT initiator_actor_id FROM video_calls WHERE call_id = :callId");
        executeStmt($stmt, [':callId' => $callId]);
        $initiator = $stmt->fetchColumn();

        $pusher->trigger('user_' . $initiator, 'update', [
            'type' => 'video_call_response',
            'call_id' => $callId,
            'thread_id' => $threadId,
            'status' => 'accepted',
            'answer' => json_decode($answer, true)
        ]);

        $response = ['status' => 'success'];
        break;

    case 'reject_video_call':
        $callId = (int)$_POST['call_id'];
        $threadId = (int)$_POST['thread_id'];

        $stmt = $conn->prepare("
            SELECT initiator_actor_id 
            FROM video_calls 
            WHERE call_id = :callId AND thread_id = :threadId AND receiver_actor_id = :actorId AND status = 'initiated'
        ");
        executeStmt($stmt, [
            ':callId' => $callId,
            ':threadId' => $threadId,
            ':actorId' => $currentActorId
        ]);

        if ($stmt->rowCount() === 0) {
            $response = ['status' => 'error', 'message' => 'Invalid call or not authorized'];
            break;
        }

        $initiator = $stmt->fetch(PDO::FETCH_ASSOC)['initiator_actor_id'];

        $stmt = $conn->prepare("
            UPDATE video_calls 
            SET status = 'rejected', ended_at = CURRENT_TIMESTAMP 
            WHERE call_id = :callId
        ");
        executeStmt($stmt, [':callId' => $callId]);

        $pusher->trigger('user_' . $initiator, 'update', [
            'type' => 'video_call_response',
            'call_id' => $callId,
            'thread_id' => $threadId,
            'status' => 'rejected'
        ]);

        $response = ['status' => 'success'];
        break;

    case 'end_video_call':
        $callId = (int)$_POST['call_id'];
        $threadId = (int)$_POST['thread_id'];

        $stmt = $conn->prepare("
            SELECT initiator_actor_id, receiver_actor_id 
            FROM video_calls 
            WHERE call_id = :callId AND thread_id = :threadId 
            AND (initiator_actor_id = :actorId OR receiver_actor_id = :actorId)
        ");
        executeStmt($stmt, [
            ':callId' => $callId,
            ':threadId' => $threadId,
            ':actorId' => $currentActorId
        ]);

        if ($stmt->rowCount() === 0) {
            $response = ['status' => 'error', 'message' => 'Invalid call or not authorized'];
            break;
        }

        $call = $stmt->fetch(PDO::FETCH_ASSOC);
        $otherParty = ($call['initiator_actor_id'] == $currentActorId) ? 
                      $call['receiver_actor_id'] : $call['initiator_actor_id'];

        $stmt = $conn->prepare("
            UPDATE video_calls 
            SET status = 'ended', ended_at = CURRENT_TIMESTAMP 
            WHERE call_id = :callId
        ");
        executeStmt($stmt, [':callId' => $callId]);

        $pusher->trigger('user_' . $otherParty, 'update', [
            'type' => 'video_call_response',
            'call_id' => $callId,
            'thread_id' => $threadId,
            'status' => 'ended'
        ]);

        $response = ['status' => 'success'];
        break;

    case 'send_signaling_message':
        $callId = (int)$_POST['call_id'];
        $threadId = (int)$_POST['thread_id'];
        $type = $_POST['type'];
        $data = $_POST['data'];

        error_log('Sending signaling message: type=' . $type . ', data=' . $data);
        $stmt = $conn->prepare("
            SELECT initiator_actor_id, receiver_actor_id 
            FROM video_calls 
            WHERE call_id = :callId AND thread_id = :threadId 
            AND (initiator_actor_id = :actorId OR receiver_actor_id = :actorId)
            AND status IN ('initiated', 'accepted')
        ");
        executeStmt($stmt, [
            ':callId' => $callId,
            ':threadId' => $threadId,
            ':actorId' => $currentActorId
        ]);

        if ($stmt->rowCount() === 0) {
            $response = ['status' => 'error', 'message' => 'Invalid call or not authorized'];
            break;
        }

        $call = $stmt->fetch(PDO::FETCH_ASSOC);
        $otherActorId = ($call['initiator_actor_id'] == $currentActorId) ? 
                         $call['receiver_actor_id'] : $call['initiator_actor_id'];

        $pusher->trigger('user_' . $otherActorId, 'update', [
            'type' => 'signaling_message',
            'call_id' => $callId,
            'thread_id' => $threadId,
            'signal_type' => $type,
            'data' => json_decode($data)
        ]);

        $response = ['status' => 'success'];
        break;

    case 'get_actor_id':
        $entityType = $_POST['entity_type'];
        $entityId = (int)$_POST['entity_id'];
        $actorId = getActorId($entityType, $entityId, $conn);
        $response = ['status' => 'success', 'actor_id' => $actorId];
        break;
}

echo json_encode($response);
?>