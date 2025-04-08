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
    // Fetch student details and join with course title
    $stmt = $conn->prepare("
        SELECT s.stud_first_name, s.stud_middle_name, s.stud_last_name,
               s.stud_no, s.stud_email, s.profile_picture, s.bio,
               s.institution, s.status, c.course_title
        FROM student s
        LEFT JOIN course c ON s.course_id = c.course_id
        WHERE s.stud_id = :stud_id AND s.deleted_at IS NULL
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

    // Fetch actor ID for features like forum/messages/notifications
    $actor_stmt = $conn->prepare("
        SELECT actor_id
        FROM actor
        WHERE entity_type = 'student' AND entity_id = :stud_id AND deleted_at IS NULL
        LIMIT 1
    ");
    $actor_stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
    $actor_stmt->execute();
    $actor = $actor_stmt->fetch(PDO::FETCH_ASSOC);

    $actor_id = $actor['actor_id'] ?? null;
    $_SESSION['actor_id'] = $actor_id;

    // Assign data for UI usage
    $full_name = htmlspecialchars($student['stud_first_name'] . ' ' . ($student['stud_middle_name'] ?? '') . ' ' . $student['stud_last_name']);
    $stud_no = htmlspecialchars($student['stud_no']);
    $email = htmlspecialchars($student['stud_email']);
    $bio = htmlspecialchars($student['bio'] ?? 'No bio added yet.');
    $course_title = htmlspecialchars($student['course_title'] ?? 'No course assigned');
    $institution = htmlspecialchars($student['institution']);
    $profile_picture = !empty($student['profile_picture']) ? $student['profile_picture'] : 'default.png';

    // These values can now be echoed in the HTML
} catch (Exception $e) {
    error_log("Student Dashboard Error: " . $e->getMessage());
    header("Location: ../auth/logout.php");
    exit();
}
?>
