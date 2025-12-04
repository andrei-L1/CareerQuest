<?php
require_once '../config/dbcon.php';
/** @var PDO $conn */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_GET['id'])) {
    header("Location: ../dashboard/employer_jobs.php");
    exit;
}

$job_id = $_GET['id'];
$employer_id = $_SESSION['employer_id'];

try {
    // Verify the job belongs to this employer and is approved
    $verify_query = "SELECT job_id FROM job_posting 
                     WHERE job_id = :job_id AND employer_id = :employer_id AND moderation_status = 'Approved'";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
    $verify_stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
    $verify_stmt->execute();

    if ($verify_stmt->rowCount() == 0) {
        $_SESSION['error'] = "Job not found, not approved, or you don't have permission to pause it.";
        header("Location: ../dashboard/employer_jobs.php");
        exit;
    }

    // Pause the job
    $pause_query = "UPDATE job_posting SET moderation_status = 'Paused' WHERE job_id = :job_id";
    $pause_stmt = $conn->prepare($pause_query);
    $pause_stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);

    if ($pause_stmt->execute()) {
        $_SESSION['success'] = "Job posting paused successfully.";
    } else {
        $_SESSION['error'] = "Error pausing job posting.";
    }

} catch (PDOException $e) {
    error_log("Error pausing job: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while pausing the job posting.";
}

header("Location: ../dashboard/employer_jobs.php");
exit;
?>