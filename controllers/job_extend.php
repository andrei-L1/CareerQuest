<?php
require "../config/dbcon.php";  // Ensure the DB connection is included
require "../auth/auth_check.php";  // Optional: Check user permissions if needed

header('Content-Type: application/json'); // Set the response type to JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['action']) && $_POST['action'] === 'extend_job') {
        $job_id = $_POST['job_id'] ?? null;
        $new_expiry = $_POST['new_expiry'] ?? null;

        if (!$job_id || !$new_expiry) {
            echo json_encode(['error' => 'Missing job_id or new_expiry']);
            exit;
        }

        // Ensure the date format is correct
        $new_expiry = date('Y-m-d H:i:s', strtotime($new_expiry));

        try {
            // First, check if the employer of the job is active
            $stmt = $conn->prepare("
                SELECT e.status 
                FROM job_posting jp 
                JOIN employer e ON jp.employer_id = e.employer_id 
                WHERE jp.job_id = :job_id AND jp.deleted_at IS NULL
            ");
            $stmt->execute([':job_id' => $job_id]);
            $employerStatus = $stmt->fetchColumn();

            if ($employerStatus !== 'Active') {
                echo json_encode(['error' => 'Job cannot be extended because the employer is ' . $employerStatus]);
                exit;
            }

            // Now, update the expiry date if the employer is active
            $stmt = $conn->prepare("
                UPDATE job_posting 
                SET expires_at = :new_expiry
                WHERE job_id = :job_id AND deleted_at IS NULL
            ");
            $stmt->execute([':job_id' => $job_id, ':new_expiry' => $new_expiry]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'new_expiry' => $new_expiry]);
            } else {
                echo json_encode(['error' => 'Job not found or already deleted']);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
}

?>