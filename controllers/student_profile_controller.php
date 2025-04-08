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
    

    // Fetch skills with proficiency level
    $skillsStmt = $conn->prepare("
        SELECT sm.skill_name, ss.proficiency
        FROM stud_skill ss
        JOIN skill_masterlist sm ON ss.skill_id = sm.skill_id
        WHERE ss.stud_id = :stud_id AND ss.deleted_at IS NULL
    ");
    $skillsStmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
    $skillsStmt->execute();
    $skills = $skillsStmt->fetchAll(PDO::FETCH_ASSOC);

    

} catch (Exception $e) {
    error_log("Student Profile Error: " . $e->getMessage());
    header("Location: ../auth/logout.php");
    exit();
}

try {
    // Fetch recent applications (limit to 3 most recent)
    $applicationsStmt = $conn->prepare("
        SELECT 
            jp.title AS job_title,
            e.company_name,
            at.application_status AS status,
            at.applied_at AS applied_date
        FROM 
            application_tracking at
        JOIN 
            job_posting jp ON at.job_id = jp.job_id
        JOIN 
            employer e ON jp.employer_id = e.employer_id
        WHERE 
            at.stud_id = :stud_id 
            AND at.deleted_at IS NULL
            AND jp.deleted_at IS NULL
        ORDER BY 
            at.applied_at DESC
        LIMIT 3
    ");
    $applicationsStmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
    $applicationsStmt->execute();
    $applications = $applicationsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the status and dates for display
    foreach ($applications as &$app) {
        $app['status'] = ucfirst(strtolower($app['status']));
        $app['applied_date'] = $app['applied_date'];
    }
    unset($app); // Break the reference

} catch (Exception $e) {
    error_log("Error fetching applications: " . $e->getMessage());
    $applications = []; // Return empty array if there's an error
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
