<?php
// student_auth.php - Student authentication and authorization system

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../config/dbcon.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['stud_id'])) {
    error_log("Student session not found, redirecting to login.");
    header("Location: ../auth/login_student.php");
    exit();
}

// Fetch student details including role and status
$stmt = $conn->prepare("SELECT s.*, c.course_title, s.status 
                       FROM student s 
                       LEFT JOIN course c ON s.course_id = c.course_id 
                       WHERE s.stud_id = :stud_id 
                       AND s.deleted_at IS NULL
                       LIMIT 1");
$stmt->bindParam(':stud_id', $_SESSION['stud_id'], PDO::PARAM_INT);
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if student exists
if (!$student) {
    error_log("Student ID {$_SESSION['stud_id']} not found in the database.");
    session_unset();
    session_destroy();
    header("Location: ../auth/login_student.php?account_not_found=1");
    exit();
}

$status = strtolower(trim($student['status'] ?? ''));

// If student is marked as 'deleted', log them out and redirect
if ($status === 'deleted') {
    session_unset();
    session_destroy();
    header("Location: ../auth/login_student.php?account_deleted=1");
    exit();
}

// Fetch actor ID for features like forum/messages/notifications
$actor_stmt = $conn->prepare("
    SELECT actor_id
    FROM actor
    WHERE entity_type = 'student' AND entity_id = :stud_id AND deleted_at IS NULL
    LIMIT 1
");
$actor_stmt->bindParam(':stud_id', $_SESSION['stud_id'], PDO::PARAM_INT);
$actor_stmt->execute();
$actor = $actor_stmt->fetch(PDO::FETCH_ASSOC);

$actor_id = $actor['actor_id'] ?? null;

// Create actor record if it doesn't exist
if (!$actor_id) {
    $insert_actor = $conn->prepare("
        INSERT INTO actor (entity_type, entity_id)
        VALUES ('student', :stud_id)
    ");
    $insert_actor->bindParam(':stud_id', $_SESSION['stud_id'], PDO::PARAM_INT);
    $insert_actor->execute();
    $actor_id = $conn->lastInsertId();
}

// Define student-specific page permissions
$page_permissions = [
    // Student pages
    'student.php' => ['active'],
    'student_profile.php' => ['active'],
    'student_job.php' => ['active'],
    'student_account_settings.php' => ['active'],
    'student_skills.php' => ['active'],
    'messages.php' => ['active'],
    'student_forum.php' => ['active'],
    
    // Shared pages
    'forum.php' => ['active'],
    'resources.php' => ['active'],
    
    // Admin pages (for reference - students shouldn't access these)
    'admin_dashboard.php' => ['admin'],
];

// Get current page safely
$current_page = htmlspecialchars(basename($_SERVER['PHP_SELF']), ENT_QUOTES, 'UTF-8');

// Check if student is allowed on this page based on status
if (isset($page_permissions[$current_page])) {
    $allowed_statuses = $page_permissions[$current_page] ?? [];
    
    if (!in_array($status, $allowed_statuses)) {
        error_log("Unauthorized access attempt by student ID: {$_SESSION['stud_id']} on $current_page.");
        
        // Different redirect for admin pages vs regular student pages
        if (in_array('admin', $allowed_statuses)) {
            header("Location: ../auth/login_student.php?unauthorized_access=1");
        } else {
            header("Location: student_dashboard.php?access_restricted=1");
        }
        exit();
    }
}

// Store student data in session for easy access
$_SESSION['student_data'] = [
    'stud_id' => $student['stud_id'],
    'student_no' => $student['stud_no'],
    'first_name' => $student['stud_first_name'],
    'last_name' => $student['stud_last_name'],
    'email' => $student['stud_email'],
    'course' => $student['course_title'],
    'graduation_year' => $student['graduation_yr'],
    'profile_pic' => $student['profile_picture'],
    'status' => $status,
    'actor_id' => $actor_id  // Added actor_id to session
];

// Function to check if student has access to a specific feature
function student_can($action) {
    global $student;
    
    $permissions = [
        'apply_jobs' => $student['status'] === 'active',
        'post_forum' => $student['status'] === 'active',
        'upload_resume' => $student['status'] === 'active',
        'message_others' => $student['status'] === 'active',
        'receive_notifications' => $student['status'] === 'active',
    ];
    
    return $permissions[$action] ?? false;
}
?>
