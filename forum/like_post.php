<?php
require_once '../config/dbcon.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// Check if user is authenticated
if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get actor_id and liker's name
$actor_id = null;
$liker_name = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT actor_id, CONCAT(user_first_name, ' ', user_last_name) AS name 
                            FROM actor 
                            JOIN user ON actor.entity_id = user.user_id 
                            WHERE entity_type = 'user' AND entity_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $actor_id = $result['actor_id'];
    $liker_name = $result['name'];
} elseif (isset($_SESSION['stud_id'])) {
    $stmt = $conn->prepare("SELECT actor_id, CONCAT(stud_first_name, ' ', stud_last_name) AS name 
                            FROM actor 
                            JOIN student ON actor.entity_id = student.stud_id 
                            WHERE entity_type = 'student' AND entity_id = ?");
    $stmt->execute([$_SESSION['stud_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $actor_id = $result['actor_id'];
    $liker_name = $result['name'];
}

if (!$actor_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid user']);
    exit;
}

$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
if ($post_id === false || $post_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit;
}

try {
    // Check if the user has already liked the post
    $stmt = $conn->prepare("SELECT like_id FROM post_like WHERE post_id = ? AND actor_id = ?");
    $stmt->execute([$post_id, $actor_id]);
    $existing_like = $stmt->fetch(PDO::FETCH_ASSOC);

    $action = '';
    if ($existing_like) {
        // Unlike: Remove the like
        $stmt = $conn->prepare("DELETE FROM post_like WHERE like_id = ?");
        $stmt->execute([$existing_like['like_id']]);
        $action = 'unliked';
    } else {
        // Like: Add the like
        $stmt = $conn->prepare("INSERT INTO post_like (post_id, actor_id) VALUES (?, ?)");
        $stmt->execute([$post_id, $actor_id]);
        $action = 'liked';

        // Fetch post details for notification
        $stmt = $conn->prepare("SELECT fp.poster_id, fp.post_title 
                                FROM forum_post fp 
                                WHERE fp.post_id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($post && $post['poster_id'] != $actor_id) {
            // Check for existing notification
            $stmt = $conn->prepare("SELECT notification_id FROM notification 
                                    WHERE actor_id = ? 
                                    AND reference_type = 'post' 
                                    AND reference_id = ? 
                                    AND notification_type = 'like' 
                                    AND deleted_at IS NULL");
            $stmt->execute([$post['poster_id'], $post_id]);
            $existing_notification = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing_notification) {
                // Create new notification
                $message = htmlspecialchars("$liker_name liked your post: " . substr($post['post_title'], 0, 50) . (strlen($post['post_title']) > 50 ? '...' : ''));
                $action_url = "../forum/post.php?post_id=$post_id";
                $stmt = $conn->prepare("INSERT INTO notification (actor_id, message, notification_type, action_url, reference_type, reference_id) 
                                        VALUES (?, ?, 'like', ?, 'post', ?)");
                $stmt->execute([$post['poster_id'], $message, $action_url, $post_id]);
            } else {
                // Update existing notification's timestamp
                $stmt = $conn->prepare("UPDATE notification 
                                        SET created_at = CURRENT_TIMESTAMP, is_read = FALSE 
                                        WHERE notification_id = ?");
                $stmt->execute([$existing_notification['notification_id']]);
            }
        }
    }

    // Get the updated like count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM post_like WHERE post_id = ?");
    $stmt->execute([$post_id]);
    $like_count = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'action' => $action,
        'like_count' => $like_count
    ]);
} catch (PDOException $e) {
    error_log('DB Error in like_post.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
?>