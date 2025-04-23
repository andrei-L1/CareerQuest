<?php
require '../config/dbcon.php';

header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("SELECT skill_id, skill_name FROM skill_masterlist WHERE deleted_at IS NULL");
    $stmt->execute();
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($skills);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>