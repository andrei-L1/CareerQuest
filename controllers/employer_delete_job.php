<?php
require_once '../config/dbcon.php';
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
    // Verify the job belongs to this employer
    $verify_query = "SELECT job_id FROM job_posting WHERE job_id = :job_id AND employer_id = :employer_id";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
    $verify_stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
    $verify_stmt->execute();

    if ($verify_stmt->rowCount() == 0) {
        $_SESSION['error'] = "Job not found or you don't have permission to delete it.";
        header("Location: ../dashboard/employer_jobs.php");
        exit;
    }

    // Soft delete the job
    $delete_query = "UPDATE job_posting SET deleted_at = NOW() WHERE job_id = :job_id";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);

    if ($delete_stmt->execute()) {
        $_SESSION['success'] = "Job posting deleted successfully.";
    } else {
        $_SESSION['error'] = "Error deleting job posting.";
    }

} catch (PDOException $e) {
    error_log("Error deleting job: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while deleting the job posting.";
}

header("Location: ../dashboard/employer_jobs.php");
exit;
?>