<?php
require "../config/dbcon.php";
require "../auth/auth_check.php";

header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("
        SELECT jp.job_id, jp.title, jp.expires_at
        FROM job_posting jp
        JOIN employer e ON jp.employer_id = e.employer_id
        WHERE jp.deleted_at IS NULL 
        AND jp.expires_at IS NOT NULL 
        AND jp.expires_at <= DATE_ADD(NOW(), INTERVAL 5 DAY)
        AND e.status = 'Active'  -- Ensure employer status is 'Active'
        ORDER BY jp.expires_at ASC
    ");
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['jobs' => $jobs]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
    