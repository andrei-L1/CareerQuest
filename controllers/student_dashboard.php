<?php
require '../config/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Check if the student is logged in
if (!isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit();
}

$stud_id = $_SESSION['stud_id'];

try {
    // Fetch student details
    $stmt = $conn->prepare("
        SELECT stud_first_name, stud_last_name, institution, status
        FROM student
        WHERE stud_id = :stud_id
    ");
    $stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        throw new Exception("Student not found.");
    }

    // Define inactive statuses
    $inactive_statuses = ['Deleted'];

    if (in_array($student['status'], $inactive_statuses)) {
        session_unset();
        session_destroy();
        header("Location: ../auth/login.php?account_deleted=1");
        exit();
    }

    $full_name = htmlspecialchars($student['stud_first_name'] . " " . $student['stud_last_name']);
    $institution = htmlspecialchars($student['institution']);

} catch (Exception $e) {
    error_log("Student Dashboard Error: " . $e->getMessage());
    header("Location: ../auth/logout.php");
    exit();
}

?>