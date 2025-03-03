<?php
session_start();
require '../dbcon.php';  // Ensure database connection is included

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$entity = $_SESSION['entity'] ?? null;
$name = ""; // Define name variable to avoid undefined errors

if ($entity === 'student') {
    $stud_id = $_SESSION['stud_id'];

    // Fetch student details
    $stmt = $conn->prepare("SELECT * FROM student WHERE stud_id = :stud_id");
    $stmt->bindParam(':stud_id', $stud_id);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        $name = $student['stud_first_name'] . ' ' . $student['stud_last_name'];
    }
} elseif ($entity === 'user') {
    $user_id = $_SESSION['user_id'];
    $role_id = $_SESSION['role_id'] ?? 0;  // Default to 0 if not set

    // Fetch user details
    $stmt = $conn->prepare("SELECT * FROM user WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $name = $user['user_first_name'] . ' ' . $user['user_last_name'];
    }

    // Determine role name
    switch ($role_id) {
        case 1:
            $role_name = 'Employer';
            break;
        case 2:
            $role_name = 'Professional';
            break;
        case 3:
            $role_name = 'Moderator';
            break;
        case 4:
            $role_name = 'Admin';
            break;
        default:
            $role_name = 'User';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($name); ?>!</h1>

    <?php if ($entity === 'student' && isset($student)): ?>
        <!-- Student-specific content -->
        <h2>Student Dashboard</h2>
        <p>You are a student. Here are your details:</p>
        <ul>
            <li>Email: <?php echo htmlspecialchars($student['stud_email']); ?></li>
            <li>Institution: <?php echo htmlspecialchars($student['institution']); ?></li>
        </ul>
        <a href="student_profile.php">Edit Profile</a><br>
        <a href="view_jobs.php">View Job Postings</a><br>

    <?php elseif ($entity === 'user' && isset($user)): ?>
        <!-- User-specific content -->
        <h2><?php echo htmlspecialchars($role_name); ?> Dashboard</h2>
        <p>You are logged in as a <?php echo htmlspecialchars($role_name); ?>.</p>

        <?php if ($role_id == 1): ?>
            <a href="post_job.php">Post a Job</a><br>
            <a href="view_applications.php">View Applications</a><br>
        <?php elseif ($role_id == 2): ?>
            <a href="update_profile.php">Update Profile</a><br>
            <a href="view_jobs.php">View Job Postings</a><br>
        <?php elseif ($role_id == 3): ?>
            <a href="manage_forum.php">Manage Forum</a><br>
        <?php elseif ($role_id == 4): ?>
            <a href="manage_users.php">Manage Users</a><br>
            <a href="manage_jobs.php">Manage Job Postings</a><br>
        <?php endif; ?>
    <?php endif; ?>

    <a href="../auth/logout.php">Logout</a>
</body>
</html>
