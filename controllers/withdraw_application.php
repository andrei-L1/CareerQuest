<?php
// Start session and include necessary files
session_start();
include_once '../config/dbcon.php';  // Your PDO connection file

// Check for CSRF token validity
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token');
}

// Get application ID from POST request
$application_id = (int)$_POST['application_id'];

// Check if application exists and is still pending
$query = "SELECT * FROM application_tracking WHERE application_id = :application_id AND application_status = 'Pending' AND deleted_at IS NULL";
$stmt = $conn->prepare($query);
$stmt->bindParam(':application_id', $application_id, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    die('Application not found or already processed');
}

// Application found, proceed to withdraw it
$query = "UPDATE application_tracking SET application_status = 'Withdrawn', deleted_at = NOW() WHERE application_id = :application_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':application_id', $application_id, PDO::PARAM_INT);

if ($stmt->execute()) {
    // Optionally, you can notify the employer about the withdrawal
    $query = "SELECT job_id, stud_id FROM application_tracking WHERE application_id = :application_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':application_id', $application_id, PDO::PARAM_INT);
    $stmt->execute();
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    // Send notification to employer (optional)
    $notification_message = "The application for job ID " . $application['job_id'] . " by student ID " . $application['stud_id'] . " has been withdrawn.";
    $query = "INSERT INTO notification (actor_id, message, notification_type, reference_type, reference_id) VALUES (:actor_id, :message, 'Application Withdrawal', 'Job', :reference_id)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':actor_id', $application['stud_id'], PDO::PARAM_INT);
    $stmt->bindParam(':message', $notification_message, PDO::PARAM_STR);
    $stmt->bindParam(':reference_id', $application['job_id'], PDO::PARAM_INT);
    $stmt->execute();

    // Redirect back or show a success message
    $_SESSION['message'] = 'Application withdrawn successfully';
    header("Location: ../dashboard/student_applications.php");
    exit;
} else {
    die('Error occurred while withdrawing application');
}
?>
