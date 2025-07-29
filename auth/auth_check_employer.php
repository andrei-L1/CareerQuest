<?php
// auth_check_employer.php - Employer authentication and authorization

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../config/dbcon.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['employer_id']) || !is_int($_SESSION['employer_id'])) {
    error_log("Invalid or missing employer_id in session: " . var_export($_SESSION['employer_id'] ?? 'not set', true));
    session_unset();
    session_destroy();
    header("Location: ../auth/login_employer.php?invalid_session=1");
    exit();
}

// Fetch employer details including status
$stmt = $conn->prepare("
    SELECT e.*, u.user_first_name, u.user_last_name, u.user_email, e.status 
    FROM employer e
    LEFT JOIN user u ON e.user_id = u.user_id
    WHERE e.employer_id = :employer_id
    AND e.deleted_at IS NULL
    LIMIT 1
");
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

// Fetch or create actor ID for features like forum/messages/notifications
if (!isset($_SESSION['actor_id'])) {
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

    if (!$actor_id) {
        // Check for soft-deleted record
        $check_deleted = $conn->prepare("
            SELECT actor_id
            FROM actor
            WHERE entity_type = 'user' AND entity_id = :employer_id
            LIMIT 1
        ");
        $check_deleted->bindParam(':employer_id', $_SESSION['employer_id'], PDO::PARAM_INT);
        $check_deleted->execute();
        $deleted_actor = $check_deleted->fetch(PDO::FETCH_ASSOC);

        if ($deleted_actor) {
            // Restore soft-deleted record
            error_log("Restoring soft-deleted actor record for employer_id: {$_SESSION['employer_id']}");
            $restore_actor = $conn->prepare("
                UPDATE actor
                SET deleted_at = NULL
                WHERE actor_id = :actor_id
            ");
            $restore_actor->bindParam(':actor_id', $deleted_actor['actor_id'], PDO::PARAM_INT);
            $restore_actor->execute();
            $actor_id = $deleted_actor['actor_id'];
        } else {
            // Create new actor record
            error_log("Creating new actor record for employer_id: {$_SESSION['employer_id']}");
            try {
                $insert_actor = $conn->prepare("
                    INSERT IGNORE INTO actor (entity_type, entity_id)
                    VALUES ('employer', :employer_id)
                ");
                $insert_actor->bindParam(':employer_id', $_SESSION['employer_id'], PDO::PARAM_INT);
                $insert_actor->execute();
                $actor_id = $conn->lastInsertId();

                if (!$actor_id) {
                    // If no new record was inserted, fetch existing one
                    $actor_stmt->execute();
                    $actor = $actor_stmt->fetch(PDO::FETCH_ASSOC);
                    $actor_id = $actor['actor_id'] ?? null;
                } else {
                    error_log("Created actor_id: $actor_id for employer_id: {$_SESSION['employer_id']}");
                }
            } catch (PDOException $e) {
                error_log("Error inserting actor record: " . $e->getMessage());
                // Fetch existing record in case of duplicate key
                $actor_stmt->execute();
                $actor = $actor_stmt->fetch(PDO::FETCH_ASSOC);
                $actor_id = $actor['actor_id'] ?? null;
            }
        }
    }
    $_SESSION['actor_id'] = $actor_id;
} else {
    $actor_id = $_SESSION['actor_id'];
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