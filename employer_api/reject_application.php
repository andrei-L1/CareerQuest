<?php
header('Content-Type: application/json');
require_once '../config/dbcon.php';

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['application_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing application ID']);
    exit;
}

$applicationId = $input['application_id'];

try {
    $conn->beginTransaction();

    // Step 1: Reject the application
    $stmt = $conn->prepare("
        UPDATE application_tracking 
        SET application_status = 'Rejected', deleted_at = NOW()
        WHERE application_id = :application_id
    ");
    $stmt->execute([':application_id' => $applicationId]);

    if ($stmt->rowCount() === 0) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Application not found or already rejected']);
        exit;
    }

    // Step 2: Cancel any scheduled interview(s) for that application
    $stmt2 = $conn->prepare("
        UPDATE interviews 
        SET status = 'Cancelled'
        WHERE application_id = :application_id AND status = 'Scheduled'
    ");
    $stmt2->execute([':application_id' => $applicationId]);

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Application rejected and interview(s) cancelled if any.']);
} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
