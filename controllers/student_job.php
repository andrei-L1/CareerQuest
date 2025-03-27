<?php
require '../config/dbcon.php';

header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("
        SELECT jp.job_id, jp.description AS title, 
               e.company_name AS company, 
               jp.location, jt.job_type_title AS category 
        FROM job_posting jp
        JOIN employer e ON jp.employer_id = e.employer_id
        JOIN job_type jt ON jp.job_type_id = jt.job_type_id
        WHERE jp.deleted_at IS NULL AND jp.moderation_status = 'Approved'
    ");
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    echo json_encode($jobs);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error. Please try again later."]);
}
?>
