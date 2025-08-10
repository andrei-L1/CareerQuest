<?php
require_once '../config/dbcon.php';
header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("SELECT forum_id, title FROM forum");
    $stmt->execute();
    $forums = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $forums]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>