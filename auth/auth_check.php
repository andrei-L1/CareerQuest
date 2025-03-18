<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../config/dbcon.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

// Fetch user role ID
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role_id FROM user WHERE user_id = :user_id LIMIT 1");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$role_id = (int) ($user['role_id'] ?? 0);

// Role-based access control
$allowed_roles = [
    'admin' => [4],
    'employer' => [1],
    'professional' => [2],
    'moderator' => [3],
];

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);

// Define page access permissions
$page_permissions = [
    'admin.php' => $allowed_roles['admin'],
    'admin_user_management.php' => $allowed_roles['admin'],
    'employer.php' => $allowed_roles['employer'],
    'professional.php' => $allowed_roles['professional'],
    'moderator.php' => $allowed_roles['moderator'],
];

// Check if user is allowed on this page
if (isset($page_permissions[$current_page]) && !in_array($role_id, $page_permissions[$current_page])) {
    header("Location: ../unauthorized.php"); // Redirect to unauthorized page
    exit();
}
?>
