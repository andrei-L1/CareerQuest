<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_regenerate_id(true); // Prevent session fixation attacks

require '../config/dbcon.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

// Fetch user role ID & status
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT u.role_id, u.status, r.role_title FROM user u 
                        LEFT JOIN role r ON u.role_id = r.role_id 
                        WHERE u.user_id = :user_id LIMIT 1");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$role_id = (int) ($user['role_id'] ?? 0);
$status = strtolower(trim($user['status'] ?? '')); // Convert status to lowercase for consistency
$role_name = strtolower(trim($user['role_title'] ?? 'user')); // Get role name from DB

// If user is marked as 'deleted', log them out and redirect
if ($status === 'deleted') {
    session_unset();
    session_destroy();
    header("Location: ../auth/login_user.php?account_deleted=1");
    exit();
}

// Dynamically fetch allowed roles from database
$role_stmt = $conn->prepare("SELECT role_title, role_id FROM role");
$role_stmt->execute();
$roles = $role_stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Fetch as [role_title => role_id]

// Define page permissions dynamically
$page_permissions = [
    'admin.php' => [$roles['Admin'] ?? 0],  // Uses role ID from DB
    'admin_user_management.php' => [$roles['Admin'] ?? 0],
    'admin_data_management.php' => [$roles['Admin'] ?? 0],
    'employer.php' => [$roles['Employer'] ?? 0],
    'professional.php' => [$roles['Professional'] ?? 0],
    'moderator.php' => [$roles['Moderator'] ?? 0],
];

// Get current page safely
$current_page = filter_var(basename($_SERVER['PHP_SELF']), FILTER_SANITIZE_STRING);

// Check if user is allowed on this page
if (isset($page_permissions[$current_page]) && !in_array($role_id, $page_permissions[$current_page])) {
    session_unset();
    session_destroy();
    header("Location: ../auth/login_user.php?unauthorized_access=1");
    exit();
}
?>
