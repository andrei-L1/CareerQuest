<?php
session_start();
require '../config/dbcon.php';

header('Content-Type: application/json');

if (!isset($_SESSION['stud_id'])) {
    echo json_encode(["error" => "Authentication required"]);
    exit();
}


$stud_id = isset($_GET['stud_id']) ? $_GET['stud_id'] : $_SESSION['stud_id'];

try {
    $stmt = $conn->prepare("
        SELECT ss.skill_id, sm.skill_name, ss.proficiency, ss.group_no 
        FROM stud_skill ss
        JOIN skill_masterlist sm ON ss.skill_id = sm.skill_id
        WHERE ss.stud_id = :stud_id AND ss.deleted_at IS NULL
    ");
    $stmt->execute([':stud_id' => $stud_id]);
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($skills);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}