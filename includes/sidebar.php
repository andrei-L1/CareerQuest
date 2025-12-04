<?php
// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => true, // Enable in production with HTTPS
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true
    ]);
}

// Validate the session before proceeding
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Include files with proper path validation
require realpath(__DIR__ . '/../config/dbcon.php');
/** @var PDO $conn */
require realpath(__DIR__ . '/../auth/auth_check.php');

// Sanitize current page
$current_page = basename(filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_STRING));
$user_id = (int)$_SESSION['user_id'];
$role_id = null;

try {
    // Prepared statement with proper parameter binding
    $stmt = $conn->prepare("SELECT role_id FROM user WHERE user_id = :user_id LIMIT 1");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Validate role_id
    $role_id = (int)$stmt->fetchColumn();
    if (!in_array($role_id, [3, 4])) { // Only allow moderator (3) and admin (4)
        header("Location: ../auth/login.php");
        exit;
    }
} catch (PDOException $e) {
    // Log error securely (don't expose details to user)
    error_log("Database error: " . $e->getMessage());
    die("A system error occurred. Please try again later.");
}

// Define menu items
$sidebar_menu = [];

// Admin menu
if ($role_id == 4) {
    $sidebar_menu = [
        ["Dashboard", "fas fa-tachometer-alt", htmlspecialchars("../dashboard/admin.php")],
        ["User Management", "fas fa-users", htmlspecialchars("../dashboard/admin_user_management.php")],
        ["Data Management", "fas fa-database", htmlspecialchars("../dashboard/admin_data_management.php")],
        ["Job Postings", "fas fa-briefcase", htmlspecialchars("../dashboard/admin_job_management.php")],
        ["Forum Moderation", "fas fa-comments", htmlspecialchars("../dashboard/admin_forum_moderation.php")],
        ["Analytics", "fas fa-chart-line", htmlspecialchars("../dashboard/admin_analytics.php")],
        //["Settings", "fas fa-cog", htmlspecialchars("../dashboard/settings.php")],
        ["Logout", "fas fa-sign-out-alt", htmlspecialchars("../auth/logout.php")]
    ];
} 
// Moderator menu
elseif ($role_id == 3) {
    $sidebar_menu = [
        ["Dashboard", "fas fa-tachometer-alt", htmlspecialchars("../dashboard/admin.php")],
        ["Job Postings", "fas fa-briefcase", htmlspecialchars("../dashboard/admin_job_management.php")],
        ["Forum Moderation", "fas fa-comments", htmlspecialchars("../dashboard/admin_forum_moderation.php")],
        ["Logout", "fas fa-sign-out-alt", htmlspecialchars("../auth/logout.php")]
    ];
}

// Final fallback in case role validation fails
if (empty($sidebar_menu)) {
    header("Location: ../auth/login.php");
    exit;
}
?>