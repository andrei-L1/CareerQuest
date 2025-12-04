<?php
require_once '../config/dbcon.php';
/** @var PDO $conn */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_GET['id'])) {
    header("Location: employer_jobs.php");
    exit;
}

$job_id = $_GET['id'];
$employer_id = $_SESSION['employer_id'];

try {
    // Verify the job belongs to this employer and is paused
    $verify_query = "SELECT job_id FROM job_posting 
                     WHERE job_id = :job_id AND employer_id = :employer_id AND moderation_status = 'Paused'";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
    $verify_stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
    $verify_stmt->execute();

    if ($verify_stmt->rowCount() == 0) {
        $_SESSION['error'] = "Job not found, not paused, or you don't have permission to activate it.";
        header("Location: ../dashboard/employer_jobs.php");
        exit;
    }

    // Activate the job
    $activate_query = "UPDATE job_posting SET moderation_status = 'Approved' WHERE job_id = :job_id";
    $activate_stmt = $conn->prepare($activate_query);
    $activate_stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);

    if ($activate_stmt->execute()) {
        $_SESSION['success'] = "Job posting activated successfully.";
    } else {
        $_SESSION['error'] = "Error activating job posting.";
    }

} catch (PDOException $e) {
    error_log("Error activating job: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while activating the job posting.";
}

header("Location: ../dashboard/employer_jobs.php");
exit;
?>