<?php 
require "../config/dbcon.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
$user_id = $_SESSION['user_id'];
 
try {
    // Fetch user role from the database
    $stmt = $conn->prepare("
        SELECT u.user_first_name, r.role_title 
        FROM user u
        LEFT JOIN role r ON u.role_id = r.role_id
        WHERE u.user_id = :user_id
        LIMIT 1
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no role is found, log the user out
    if (!$user) {
        header("Location: ../index.php");
        exit();
    }

    // Store role in session
    $_SESSION['role'] = $user['role_title'] ?? 'User';
    $_SESSION['user_first_name'] = $user['user_first_name'] ?? 'Admin';

} catch (PDOException $e) {
    // Log the error and redirect
    error_log("Database error: " . $e->getMessage());
    header("Location: ../index.php");
    exit();
}

try {
    $stmt = $conn->prepare("
    SELECT actor_id 
    FROM actor 
    WHERE entity_type = 'user' AND entity_id = :user_id
    LIMIT 1
");

$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$actor = $stmt->fetch(PDO::FETCH_ASSOC);


} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $_SESSION['actor_id'] = null;
}



try {
    // Query to count users
    $query = "SELECT COUNT(*) as total_users FROM actor"; 
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get the total users count
    $total_users = $row['total_users'] ?? 0;
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $total_users = 0; 
}

    // LIST ALL JOB POSTING
try {
    $query = "SELECT COUNT(*) as total_jobs FROM job_posting"; 
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $total_jobs = $row['total_jobs'] ?? 0;
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $total_jobs = 0; 
}

    // LIST ALL APPLICATIONS
try {
    $query = "SELECT COUNT(*) as total_application FROM application_tracking"; 
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $total_application = $row['total_application'] ?? 0;
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $total_application = 0; 
}

    // LIST ALL FORUMS
try {
    $query = "SELECT COUNT(*) as total_forum FROM forum"; 
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $total_forum = $row['total_forum'] ?? 0;
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $total_forum = 0; 
}

$conn = null;

?>