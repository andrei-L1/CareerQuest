<?php
require_once '../config/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Authentication check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized. Please log in."]);
    exit();
}

// Validate input
$data = json_decode(file_get_contents('php://input'), true);
$applicationId = $data['applicationId'] ?? null;
$newStatus = $data['newStatus'] ?? null;

if (!$applicationId || !$newStatus) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required parameters"]);
    exit();
}

try {
    // First verify the employer owns this application
    $stmt = $conn->prepare("
        SELECT at.application_id 
        FROM application_tracking at
        JOIN job_posting jp ON at.job_id = jp.job_id
        JOIN employer e ON jp.employer_id = e.employer_id
        JOIN user u ON e.user_id = u.user_id
        WHERE at.application_id = :application_id
        AND u.user_id = :user_id
    ");
    $stmt->bindParam(':application_id', $applicationId, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(["error" => "You don't have permission to update this application"]);
        exit();
    }

    // Update the status
    $updateStmt = $conn->prepare("
        UPDATE application_tracking 
        SET application_status = :status 
        WHERE application_id = :application_id
    ");
    $updateStmt->bindParam(':status', $newStatus, PDO::PARAM_STR);
    $updateStmt->bindParam(':application_id', $applicationId, PDO::PARAM_INT);
    $updateStmt->execute();

    echo json_encode(["success" => true, "message" => "Application status updated"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>