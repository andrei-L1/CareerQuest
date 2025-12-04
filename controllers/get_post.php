<?php
session_start();
require "../config/dbcon.php";
/** @var PDO $conn */
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['post_id'])) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['success' => false, 'message' => 'Post ID required']);
    exit();
}

$post_id = filter_var($_GET['post_id'], FILTER_VALIDATE_INT);
if ($post_id === false) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['success' => false, 'message' => 'Invalid Post ID']);
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT 
            fp.post_id,
            fp.content,
            fp.post_title,
            f.title AS forum_name,
            f.forum_id,
            a.entity_type,
            CASE 
                WHEN a.entity_type = 'user' THEN u.user_first_name
                WHEN a.entity_type = 'student' THEN s.stud_first_name
            END AS first_name,
            CASE 
                WHEN a.entity_type = 'user' THEN u.user_last_name
                WHEN a.entity_type = 'student' THEN s.stud_last_name
            END AS last_name,
            fp.posted_at,
            fp.is_pinned,
            fp.is_announcement
        FROM forum_post fp
        JOIN forum f ON fp.forum_id = f.forum_id
        JOIN actor a ON fp.poster_id = a.actor_id
        LEFT JOIN user u ON a.entity_type = 'user' AND a.entity_id = u.user_id
        LEFT JOIN student s ON a.entity_type = 'student' AND a.entity_id = s.stud_id
        WHERE fp.post_id = :post_id
    ");
    $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        header("HTTP/1.1 404 Not Found");
        echo json_encode(['success' => false, 'message' => 'Post not found']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'post' => $post
    ]);
    
} catch (PDOException $e) {
    error_log("Error fetching post: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(['success' => false, 'message' => 'Error fetching post']);
}
?>