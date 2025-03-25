<?php
require '../config/dbcon.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Enable error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        // Validate and sanitize input
        $entity = $_POST['entity'] ?? null;
        $studno = $_POST['student_id'] ?? null;
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        if (!$email || !strpos($_POST['email'], '.')) {
            throw new Exception("Invalid email format. Please include a valid domain (e.g., user@example.com).");
        }        
        $password = $_POST['password'] ?? null;
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = !empty(trim($_POST['middle_name'] ?? '')) ? trim($_POST['middle_name']) : null;
        $last_name = trim($_POST['last_name'] ?? '');

        if (!$email || empty($password) || empty($first_name) || empty($last_name)) {
            throw new Exception("Invalid input data.");
        }

        // ✅ **Step 1: Check if Email Exists**
        $checkEmailStmt = $conn->prepare("
            SELECT user_email FROM user WHERE user_email = :email 
            UNION 
            SELECT stud_email FROM student WHERE stud_email = :email
        ");
        $checkEmailStmt->bindParam(':email', $email, PDO::PARAM_STR);
        $checkEmailStmt->execute();

        if ($checkEmailStmt->rowCount() > 0) {
            $redirect_page = ($entity === "student") ? "../views/register_student.php" : "../views/register_user.php";
            header("Location: $redirect_page?message=" . urlencode("Email already exists. Please use a different email.") . "&email=" . urlencode($email));
            exit();
        }

        // Hash the password using Argon2id
        $hashed_password = password_hash($password, PASSWORD_ARGON2ID);
        $conn->beginTransaction();  

        if ($entity === 'user') {
            $role_id = intval($_POST['role_id'] ?? 0);
            $status = 'active';

            // Fetch role_title (user_type) from role table
            $roleStmt = $conn->prepare("SELECT role_title FROM role WHERE role_id = :role_id");
            $roleStmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
            $roleStmt->execute();
            $role = $roleStmt->fetch(PDO::FETCH_ASSOC);

            if (!$role) {
                throw new Exception("Invalid role_id provided.");
            }

            $user_type = $role['role_title']; 

            // Insert user into database
            $stmt = $conn->prepare("
                INSERT INTO user (user_type, user_email, user_password, role_id, user_first_name, user_middle_name, user_last_name, `status`) 
                VALUES (:user_type, :email, :password, :role_id, :first_name, :middle_name, :last_name, :active)
            ");
            $stmt->bindParam(':user_type', $user_type, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
            $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
            $stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
            $stmt->bindValue(':middle_name', $middle_name, $middle_name ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindParam(':last_name', $last_name, PDO::PARAM_STR);
            $stmt->bindParam(':active', $status, PDO::PARAM_STR);

            if (!$stmt->execute()) {
                throw new Exception("Error inserting user.");
            }

            // ✅ Fetch user_id instead of lastInsertId()
            $stmt = $conn->prepare("SELECT user_id FROM user WHERE user_email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                throw new Exception("Failed to retrieve newly inserted user ID.");
            }
            $user_id = $user['user_id'];

            // Insert into employer or professional based on role
            if ($user_type === 'Employer') {
                $stmt = $conn->prepare("INSERT INTO employer (user_id) VALUES (:user_id)");
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                if (!$stmt->execute()) {
                    throw new Exception("Error inserting employer.");
                }
            } elseif ($user_type === 'Professional') {
                $stmt = $conn->prepare("INSERT INTO professional (user_id) VALUES (:user_id)");
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                if (!$stmt->execute()) {
                    throw new Exception("Error inserting professional.");
                }
            }

            $entity_id = $user_id;
        } elseif ($entity === 'student') {
            $institution = trim($_POST['institution'] ?? '');
            $status = 'active';
            if (empty($institution)) {
                throw new Exception("Institution field is required.");
            }

            // Insert student into database
            $stmt = $conn->prepare("
                INSERT INTO student (stud_no, stud_email, stud_password, stud_first_name, stud_middle_name, stud_last_name, institution, `status`) 
                VALUES (:studno, :email, :password, :first_name, :middle_name, :last_name, :institution, :active)
            ");
            $stmt->bindParam(':studno', $studno, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
            $stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
            $stmt->bindValue(':middle_name', $middle_name, $middle_name ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindParam(':last_name', $last_name, PDO::PARAM_STR);
            $stmt->bindParam(':institution', $institution, PDO::PARAM_STR);
            $stmt->bindParam(':active', $status, PDO::PARAM_STR);

            if (!$stmt->execute()) {
                throw new Exception("Error inserting student.");
            }

            // ✅ Fetch student_id instead of lastInsertId()
            $stmt = $conn->prepare("SELECT stud_id FROM student WHERE stud_email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$student) {
                throw new Exception("Failed to retrieve newly inserted student ID.");
            }
            $entity_id = $student['stud_id'];
        } else {
            throw new Exception("Invalid entity type.");
        }

        // Insert into actor table
        $stmt = $conn->prepare("INSERT INTO actor (entity_type, entity_id) VALUES (:entity, :entity_id)");
        $stmt->bindParam(':entity', $entity, PDO::PARAM_STR);
        $stmt->bindParam(':entity_id', $entity_id, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            throw new Exception("Error inserting into actor table.");
        }

        $conn->commit(); 

        $redirect_page = ($entity === 'student') ? "../views/register_student.php" : "../views/register_user.php";
        header("Location: $redirect_page?success=" . urlencode("Registration successful! You can now log in."));
        exit();
        
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        error_log("Registration Error: " . $e->getMessage());
        $redirect_page = ($entity === 'student') ? '../views/register_student.php' : '../views/register_user.php';
        header("Location: " . $redirect_page . "?message=" . urlencode($e->getMessage()));
        exit();
    }
}
?>
