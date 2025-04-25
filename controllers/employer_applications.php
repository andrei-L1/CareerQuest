<?php

require '../config/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the employer is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get employer data
    $employer_stmt = $conn->prepare("SELECT employer_id, company_name, company_logo FROM employer WHERE user_id = :user_id AND deleted_at IS NULL");
    $employer_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $employer_stmt->execute();
    $employer_row = $employer_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employer_row) {
        $_SESSION['error'] = "Employer account not found or has been deleted.";
        header("Location: ../unauthorized.php");
        exit();
    }

    $employer_id = $employer_row['employer_id'];

    // Application counts by status
    $statuses = ['Pending', 'Accepted', 'Interview', 'Offered', 'Rejected'];
    $results = [];

    // Total applications
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total 
        FROM application_tracking at
        JOIN job_posting jp ON at.job_id = jp.job_id
        WHERE jp.employer_id = :employer_id AND at.deleted_at IS NULL
    ");
    $stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
    $stmt->execute();
    $results['total_applications'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Count by individual statuses
    foreach ($statuses as $status) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total 
            FROM application_tracking at
            JOIN job_posting jp ON at.job_id = jp.job_id
            WHERE jp.employer_id = :employer_id AND at.application_status = :status AND at.deleted_at IS NULL
        ");
        $stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->execute();
        $results[strtolower($status)] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    echo json_encode($results);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => "Error fetching application data: " . $e->getMessage()]);
}
?>