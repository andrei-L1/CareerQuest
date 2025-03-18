<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../config/dbcon.php';

// Check if the student is logged in
if (!isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit();
}

$stud_id = $_SESSION['stud_id'];

// Check if the student is inactive or deleted
$stmt = $conn->prepare("SELECT status FROM student WHERE stud_id = :stud_id LIMIT 1");
$stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Define inactive statuses
$inactive_statuses = ['Deleted']; 

if ($student && in_array($student['status'], $inactive_statuses)) {
    session_unset();
    session_destroy();
    header("Location: ../auth/login_user.php?account_deleted=1");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
<a href="../auth/logout.php">Logout</a>
</body>
</html>