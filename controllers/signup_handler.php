<?php
require '../config/dbcon.php';

// Start session for CSRF and rate-limiting
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Enable error reporting (disable in production)
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        // === CSRF Protection ===
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Invalid CSRF token.");
        }

        // === Rate Limiting (10 seconds between registrations) ===
        if (isset($_SESSION['last_registration_time']) && (time() - $_SESSION['last_registration_time'] < 10)) {
            throw new Exception("Please wait 10 seconds before trying again.");
        }
        $_SESSION['last_registration_time'] = time();

        // === Input Validation ===
        $entity = $_POST['entity'] ?? null;
        $studno = $_POST['student_id'] ?? null;

        // Validate entity type
        if (!in_array($entity, ['student', 'professional', 'employer'])) {
            throw new Exception("Invalid entity type.");
        }

        // Email (case-insensitive)
        $email = strtolower(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL));
        if (!$email || !strpos($_POST['email'], '.')) {
            throw new Exception("Invalid email format. Use a valid email (e.g., user@example.com).");
        }

        // Password validation
        $password = $_POST['password'] ?? null;
        $confirm_password = $_POST['confirm_password'] ?? null;
        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match.");
        }
        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters.");
        }
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            throw new Exception("Password must contain at least one uppercase letter and one number.");
        }

        // Name validation (letters and spaces only)
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = !empty(trim($_POST['middle_name'] ?? '')) ? trim($_POST['middle_name']) : null;
        $last_name = trim($_POST['last_name'] ?? '');

        if (!preg_match('/^[a-zA-Z\s]+$/', $first_name) || !preg_match('/^[a-zA-Z\s]*$/', $middle_name) || !preg_match('/^[a-zA-Z\s]+$/', $last_name)) {
            throw new Exception("Names can only contain letters and spaces.");
        }

        if (!$email || empty($password) || empty($first_name) || empty($last_name)) {
            throw new Exception("All required fields must be filled.");
        }
        
        // Entity-specific validations
        if ($entity === 'student') {
            if (empty($studno)) {
                throw new Exception("Student ID is required.");
            }
            if (!preg_match('/^[a-zA-Z0-9]+$/', $studno)) {
                throw new Exception("Student ID can only contain letters and numbers.");
            }
        }

        // === Check for Existing Email ===
        if ($entity === "student") {
            $checkEmailStmt = $conn->prepare("SELECT stud_email FROM student WHERE stud_email = :email");
            $checkStudnoStmt = $conn->prepare("SELECT stud_no FROM student WHERE stud_no = :studno");

        } elseif ($entity === "professional" || $entity === "employer") {
            $checkEmailStmt = $conn->prepare("SELECT user_email FROM user WHERE user_email = :email");
        } else {
            throw new Exception("Invalid entity type.");
        }

        $checkStudnoStmt->bindParam(':studno', $studno, PDO::PARAM_STR);
        $checkEmailStmt->bindParam(':email', $email, PDO::PARAM_STR);
        $checkEmailStmt->execute();
        $checkStudnoStmt->execute();
        
        if ($checkEmailStmt->rowCount() > 0) {
            throw new Exception("Email already exists. Use a different email.");
        }
 
        if ($checkStudnoStmt->rowCount() > 0) {
            throw new Exception("Student ID already exists. Please use a different one.");
        }


        // === Hash Password ===
        $hashed_password = password_hash($password, PASSWORD_ARGON2ID);
        $conn->beginTransaction();  

        // === Insert Data Based on Entity ===
        if ($entity === 'employer' || $entity === 'professional') {
            $role_id = intval($_POST['role_id'] ?? 0);
            $status = 'active';

            // Fetch role_title (user_type)
            $roleStmt = $conn->prepare("SELECT role_title FROM role WHERE role_id = :role_id");
            $roleStmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
            $roleStmt->execute();
            $role = $roleStmt->fetch(PDO::FETCH_ASSOC);

            if (!$role) {
                throw new Exception("Invalid role_id provided.");
            }

            $user_type = $role['role_title']; 

            // Insert into `user` table
            $stmt = $conn->prepare("
                INSERT INTO user (user_type, user_email, user_password, role_id, user_first_name, user_middle_name, user_last_name, `status`) 
                VALUES (:user_type, :email, :password, :role_id, :first_name, :middle_name, :last_name, :active)
            ");
            $stmt->execute([
                ':user_type' => $user_type,
                ':email' => $email,
                ':password' => $hashed_password,
                ':role_id' => $role_id,
                ':first_name' => $first_name,
                ':middle_name' => $middle_name,
                ':last_name' => $last_name,
                ':active' => $status
            ]);

            // Fetch user_id
            $stmt = $conn->prepare("SELECT user_id FROM user WHERE user_email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                throw new Exception("Failed to retrieve user ID.");
            }
            $user_id = $user['user_id'];

            // Insert into specialized table (employer/professional)
            $table = ($user_type === 'Employer') ? 'employer' : 'professional';
            $stmt = $conn->prepare("INSERT INTO $table (user_id) VALUES (:user_id)");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();

            $entity_id = $user_id;
        } elseif ($entity === 'student') {
            $institution = trim($_POST['institution'] ?? '');
            $status = 'active';
            if (empty($institution)) {
                throw new Exception("Institution field is required.");
            }

            // Insert into `student` table
            $stmt = $conn->prepare("
                INSERT INTO student (stud_no, stud_email, stud_password, stud_first_name, stud_middle_name, stud_last_name, institution, `status`) 
                VALUES (:studno, :email, :password, :first_name, :middle_name, :last_name, :institution, :active)
            ");
            $stmt->execute([
                ':studno' => $studno,
                ':email' => $email,
                ':password' => $hashed_password,
                ':first_name' => $first_name,
                ':middle_name' => $middle_name,
                ':last_name' => $last_name,
                ':institution' => $institution,
                ':active' => $status
            ]);

            // Fetch stud_id
            $stmt = $conn->prepare("SELECT stud_id FROM student WHERE stud_email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$student) {
                throw new Exception("Failed to retrieve student ID.");
            }
            $entity_id = $student['stud_id'];
        } else {
            throw new Exception("Invalid entity type.");
        }

        // === Insert into Actor Table ===
        $actor_entity = ($entity === 'employer' || $entity === 'professional') ? 'user' : $entity;
        $stmt = $conn->prepare("INSERT INTO actor (entity_type, entity_id) VALUES (:entity, :entity_id)");
        $stmt->execute([
            ':entity' => $actor_entity,
            ':entity_id' => $entity_id
        ]);

        $conn->commit(); 

        // Log successful registration
        error_log("New registration: $email ($entity)");
        
        switch ($entity) {
            case 'student':
                $redirect_page = "../auth/login_student.php";
                break;
            case 'professional':
                $redirect_page = "../auth/login_user.php";
                break;
            case 'employer': 
                $redirect_page = "../auth/login_user.php";
                break;
            default:
                $redirect_page = "../index.php"; // Fallback case
        }
        
        header("Location: $redirect_page?success=" . urlencode("Registration successful! You can now log in."));

        exit();
        
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        error_log("Registration Error: " . $e->getMessage());
        
        // Determine redirect page dynamically
        $redirect_page = match($entity) {
            'student' => '../views/register_student.php',
            'professional' => '../views/register_professional.php',
            'employer' => '../views/register_employer.php',
            default => '../index.php'
        };
        
        header("Location: $redirect_page?message=" . urlencode("Error: " . $e->getMessage()));
        exit();
    }
}