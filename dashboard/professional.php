<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require "../config/dbcon.php";
/** @var PDO $conn */
require "../auth/auth_check.php"; 

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch user details including role_title
    $stmt = $conn->prepare("
        SELECT user.user_first_name, user.user_last_name, role.role_title
        FROM user
        JOIN role ON user.role_id = role.role_id
        WHERE user.user_id = :user_id
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found.");
    }

    $full_name = htmlspecialchars($user['user_first_name'] . " " . $user['user_last_name']);
    $role_title = htmlspecialchars($user['role_title']);

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    header("Location: ../auth/logout.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Dashboard</title>
</head>
<body>
    <h1>Welcome, <?php echo $full_name; ?>!</h1>
    <p>Your Role: <?php echo $role_title; ?></p>
    <a href="../auth/logout.php">Logout</a>
</body>
</html>
