<?php
// auth_check_employer.php - Employer authentication and authorization

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../config/dbcon.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['employer_id'])) {
    error_log("Employer session not found, redirecting to login.");
    header("Location: ../auth/login_employer.php");
    exit();
}

// Fetch employer details including status
$stmt = $conn->prepare("SELECT e.*, u.user_first_name, u.user_last_name, u.user_email, e.status 
                        FROM employer e
                        LEFT JOIN user u ON e.user_id = u.user_id
                        WHERE e.employer_id = :employer_id
                        AND e.deleted_at IS NULL
                        LIMIT 1");
$stmt->bindParam(':employer_id', $_SESSION['employer_id'], PDO::PARAM_INT);
$stmt->execute();
$employer = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if employer exists
if (!$employer) {
    error_log("Employer ID {$_SESSION['employer_id']} not found in the database.");
    session_unset();
    session_destroy();
    header("Location: ../auth/login_employer.php?account_not_found=1");
    exit();
}

$status = strtolower(trim($employer['status'] ?? ''));

// If employer is marked as 'deleted' or 'blocked', log them out and redirect
if ($status === 'deleted' || $status === 'blocked') {
    session_unset();
    session_destroy();
    header("Location: ../auth/login_employer.php?account_deleted=1");
    exit();
}

// Fetch actor ID for features like forum/messages/notifications
$actor_stmt = $conn->prepare("
    SELECT actor_id
    FROM actor
    WHERE entity_type = 'employer' AND entity_id = :employer_id AND deleted_at IS NULL
    LIMIT 1
");
$actor_stmt->bindParam(':employer_id', $_SESSION['employer_id'], PDO::PARAM_INT);
$actor_stmt->execute();
$actor = $actor_stmt->fetch(PDO::FETCH_ASSOC);

$actor_id = $actor['actor_id'] ?? null;

// Create actor record if it doesn't exist
if (!$actor_id) {
    $insert_actor = $conn->prepare("
        INSERT INTO actor (entity_type, entity_id)
        VALUES ('employer', :employer_id)
    ");
    $insert_actor->bindParam(':employer_id', $_SESSION['employer_id'], PDO::PARAM_INT);
    $insert_actor->execute();
    $actor_id = $conn->lastInsertId();
}

// Define employer-specific page permissions
$page_permissions = [
    // Pages accessible to verified employers only
    'employer.php' => ['active'],
    'post_job.php' => ['active'],
    'manage_jobs.php' => ['active'],
    'edit_job.php' => ['active'],
    'schedule_interview.php' => ['active'],
    'manage_applications.php' => ['active'],
    'view_application.php' => ['active'],

    // Pages accessible to employers in verification stage
    'employer_profile.php' => ['active', 'verification'],
    'employer_account_settings.php' => ['active', 'verification'],

    // Shared pages
    'forum.php' => ['active', 'verification'],
    'resources.php' => ['active', 'verification'],

    // Admin pages (for reference only)
    'admin_dashboard.php' => ['admin']
];


// Get current page safely
$current_page = htmlspecialchars(basename($_SERVER['PHP_SELF']), ENT_QUOTES, 'UTF-8');

// Check if employer is allowed on this page based on status
if (isset($page_permissions[$current_page])) {
    $allowed_statuses = $page_permissions[$current_page] ?? [];
    if (!in_array($status, $allowed_statuses)) {
        error_log("Unauthorized access attempt by employer ID: {$_SESSION['employer_id']} on $current_page.");
        // Different redirect for admin pages vs regular employer pages
        if (in_array('admin', $allowed_statuses)) {
            header("Location: ../auth/login_employer.php?unauthorized_access=1");
        } else {
            header("Location: employer.php?access_restricted=1");
        }
        exit();
    }
}

// Store employer data in session for easy access
$_SESSION['employer_data'] = [
    'employer_id' => $employer['employer_id'],
    'company_name' => $employer['company_name'],
    'user_first_name' => $employer['user_first_name'],
    'user_last_name' => $employer['user_last_name'],
    'user_email' => $employer['user_email'],
    'contact_number' => $employer['contact_number'],
    'company_website' => $employer['company_website'],
    'company_logo' => $employer['company_logo'],
    'status' => $status,
    'actor_id' => $actor_id
];

// Function to check if employer has access to a specific feature
function employer_can($action) {
    global $employer;
    $permissions = [
        'post_jobs' => $employer['status'] === 'active',
        'view_applications' => $employer['status'] === 'active',
        'message_others' => $employer['status'] === 'active',
        'receive_notifications' => $employer['status'] === 'active',
    ];
    return $permissions[$action] ?? false;
}
?>