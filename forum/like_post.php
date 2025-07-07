<?php
require_once '../config/dbcon.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$postId = $_POST['post_id'] ?? null;
if (!$postId) {
    echo json_encode(['success' => false, 'message' => 'Missing post ID']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE forum_post SET up_count = up_count + 1 WHERE post_id = ?");
    $stmt->execute([$postId]);

    $stmt = $conn->prepare("SELECT up_count FROM forum_post WHERE post_id = ?");
    $stmt->execute([$postId]);
    $count = $stmt->fetchColumn();

    echo json_encode(['success' => true, 'like_count' => $count]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
