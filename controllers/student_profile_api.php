<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../config/dbcon.php';
/** @var PDO $conn */
// Check if the student is logged in
if (!isset($_SESSION['stud_id'])) {
    echo json_encode(['error' => 'User is not logged in.']);
    exit();
}

$stud_id = $_SESSION['stud_id'];

// Function to fetch student details
function getStudentDetails($conn, $stud_id) {
    $stmt = $conn->prepare("SELECT stud_first_name, stud_last_name, stud_email, institution, graduation_yr, course.course_title, bio, profile_picture, resume_file FROM student LEFT JOIN course ON student.course_id = course.course_id WHERE stud_id = :stud_id");
    $stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        throw new Exception("Student not found.");
    }

    return $student;
}

// Function to fetch total applications
function getTotalApplications($conn, $stud_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_applications FROM application_tracking WHERE stud_id = :stud_id AND deleted_at IS NULL");
    $stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
    $stmt->execute();
    $applications = $stmt->fetch(PDO::FETCH_ASSOC);

    return $applications['total_applications'];
}

// Function to fetch total interviews (Accepted applications)
function getTotalInterviews($conn, $stud_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_interviews FROM application_tracking WHERE stud_id = :stud_id AND application_status = 'Accepted' AND deleted_at IS NULL");
    $stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
    $stmt->execute();
    $interviews = $stmt->fetch(PDO::FETCH_ASSOC);

    return $interviews['total_interviews'];
}

// Function to fetch total skills
function getTotalSkills($conn, $stud_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_skills FROM stud_skill WHERE stud_id = :stud_id AND deleted_at IS NULL");
    $stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
    $stmt->execute();
    $skillsCount = $stmt->fetch(PDO::FETCH_ASSOC);

    return $skillsCount['total_skills'];
}

// Function to calculate profile strength
function calculateProfileStrength($student, $totalSkills) {
    $profileCompleteness = 0;
    if (!empty($student['bio'])) {
        $profileCompleteness += 33;
    }
    if (!empty($student['resume_file'])) {
        $profileCompleteness += 33;
    }
    if (!empty($student['profile_picture'])) {
        $profileCompleteness += 34;
    }
    
    $profileStrength = $profileCompleteness + ($totalSkills * 2); // Add skill weightage
    $profileStrength = min(100, $profileStrength); // Ensure it's capped at 100%

    return $profileStrength;
}

try {
    // Fetch student details
    $student = getStudentDetails($conn, $stud_id);

    // Fetch total applications
    $totalApplications = getTotalApplications($conn, $stud_id);

    // Fetch total interviews
    $totalInterviews = getTotalInterviews($conn, $stud_id);

    // Fetch total skills
    $totalSkills = getTotalSkills($conn, $stud_id);

    // Calculate profile strength
    $profileStrength = calculateProfileStrength($student, $totalSkills);

    // Output data for front-end
    echo json_encode([
        'totalApplications' => $totalApplications,
        'totalInterviews' => $totalInterviews,
        'profileStrength' => $profileStrength . '%',
    ]);

} catch (Exception $e) {
    error_log("Student Profile Error: " . $e->getMessage());
    echo json_encode(['error' => 'Something went wrong. Please try again later.']);
}
?>
