<?php
// Secure session settings
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
session_start();
session_regenerate_id(true); // Prevent session fixation attacks

require '../config/dbcon.php';

// Session Timeout (15 min)
$session_timeout = 900; // 900 seconds = 15 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: ../auth/login.php?session_expired=1");
    exit();
}
$_SESSION['last_activity'] = time();

// Prevent Session Hijacking
if (!isset($_SESSION['user_agent'])) {
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
}
if (!isset($_SESSION['user_ip'])) {
    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
}
if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT'] || $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
    session_unset();
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit();
}

$entity = $_SESSION['entity'] ?? null;

// Redirect if session data is inconsistent
if (($entity === 'student' && !isset($_SESSION['stud_id'])) || ($entity === 'user' && !isset($_SESSION['user_id']))) {
    header("Location: ../auth/login.php");
    exit();
}

try {
    if ($entity === 'student') {
        $stud_id = $_SESSION['stud_id'];
        $stmt = $conn->prepare("SELECT stud_first_name, stud_last_name FROM student WHERE stud_id = :stud_id LIMIT 1");
        $stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
        $stmt->execute();
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            header("Location: ../auth/login.php");
            exit();
        }
        header("Location: ../dashboard/student.php");
        exit();
    } elseif ($entity === 'user') {
        $user_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("
            SELECT r.role_title 
            FROM user u
            LEFT JOIN role r ON u.role_id = r.role_id
            WHERE u.user_id = :user_id
            LIMIT 1
        ");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            header("Location: ../auth/login.php");
            exit();
        }

        $role_name = htmlspecialchars($user['role_title'] ?? 'User', ENT_QUOTES, 'UTF-8');

        // Allowed redirect pages
        $allowed_redirects = [
            'Admin' => '../dashboard/admin.php',
            'Employer' => '../dashboard/employer.php',
            'Professional' => '../dashboard/professional.php',
            'Moderator' => '../dashboard/moderator.php',
        ];

        if (isset($allowed_redirects[$role_name])) {
            header("Location: " . $allowed_redirects[$role_name]);
        } else {
            header("Location: ../dashboard/user.php"); // Default redirect
        }
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("Something went wrong. Please try again later.");
}
?>
