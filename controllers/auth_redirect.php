<?php
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
session_start();
session_regenerate_id(true); // Prevent session fixation attacks

if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit();
}

require '../config/dbcon.php';

$entity = $_SESSION['entity'] ?? null;
$role_name = "User"; // Default role

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

        $role_name = $user['role_title'] ?? 'User';

        // Redirect users based on their role
        switch ($role_name) {
            case 'Admin':
                header("Location: ../dashboard/admin.php");
                break;
            case 'Employer':
                header("Location: ../dashboard/employer.php");
                break;
            case 'Professional':
                header("Location: ../dashboard/professional.php");
                break;
            case 'Moderator':
                header("Location: ../dashboard/moderator.php");
                break;
        }
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("Something went wrong. Please try again later.");
}
?>
