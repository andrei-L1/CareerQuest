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

try {
    // Fetch student details
    $stmt = $conn->prepare("
        SELECT stud_first_name, stud_last_name, stud_email, institution, graduation_yr, course.course_title, bio, profile_picture, resume_file 
        FROM student 
        LEFT JOIN course ON student.course_id = course.course_id
        WHERE stud_id = :stud_id
    ");
    $stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        throw new Exception("Student not found.");
    }

} catch (Exception $e) {
    error_log("Student Profile Error: " . $e->getMessage());
    header("Location: ../auth/logout.php");
    exit();
}

// Determine Profile Picture URL
$profilePicPath = "../uploads/" . ($student['profile_picture'] ?? '');
if (!empty($student['profile_picture']) && file_exists($profilePicPath)) {
    $profile_pic = htmlspecialchars($profilePicPath) . "?t=" . time(); // Forces refresh
} else {
    $full_name = trim(($student['stud_first_name'] ?? '') . ' ' . ($student['stud_last_name'] ?? ''));
    $profile_pic = "https://ui-avatars.com/api/?name=" . urlencode($full_name) . "&background=3A7BD5&color=fff&rounded=true&size=128";
}
?>
