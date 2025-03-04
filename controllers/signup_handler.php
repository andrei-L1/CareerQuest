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
        $last_name = trim($_POST['last_name'] ?? '');



        if (!$email || empty($password) || empty($first_name) || empty($last_name)) {
            throw new Exception("Invalid input data.");
        }

        if (empty($password)) {
            die("Invalid input: Password is missing.");
        }
        if (empty($first_name)) {
            die("Invalid input: First name is missing.");
        }
        if (empty($last_name)) {
            die("Invalid input: Last name is missing.");
        }
        
        // Hash the password using Argon2id
        $hashed_password = password_hash($password, PASSWORD_ARGON2ID);

        // **Start transaction only if entity is valid**
        if (in_array($entity, ['user', 'student'])) {
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
            
                $user_type = $role['role_title']; // Only assign after checking existence
            
                // Insert user into database
                $stmt = $conn->prepare("INSERT INTO user (user_type, user_email, user_password, role_id, user_first_name, user_last_name, `status`) 
                                        VALUES (:user_type, :email, :password, :role_id, :first_name, :last_name, :active)");
                $stmt->bindParam(':user_type', $user_type, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
                $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
                $stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
                $stmt->bindParam(':last_name', $last_name, PDO::PARAM_STR);
                $stmt->bindParam(':active', $status, PDO::PARAM_STR);
            
                if (!$stmt->execute()) {
                    throw new Exception("Error inserting user.");
                }
            } elseif ($entity === 'student') {
                $institution = trim($_POST['institution'] ?? '');
                if (empty($institution)) {
                    throw new Exception("Institution field is required.");
                }
            
                // Insert student into database
                $stmt = $conn->prepare("INSERT INTO student (stud_no, stud_email, stud_password, stud_first_name, stud_last_name, institution) 
                                        VALUES (:studno, :email, :password, :first_name, :last_name, :institution)");
                $stmt->bindParam(':studno', $studno, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
                $stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
                $stmt->bindParam(':last_name', $last_name, PDO::PARAM_STR);
                $stmt->bindParam(':institution', $institution, PDO::PARAM_STR);
            
                if (!$stmt->execute()) {
                    throw new Exception("Error inserting student.");
                }
            }

            
            $entity_id = $conn->lastInsertId(); // Get the last inserted ID
            if (!$entity_id) {
                throw new Exception("Failed to retrieve last inserted ID.");
            }            

            // Insert into actor table
            $stmt = $conn->prepare("INSERT INTO actor (entity_type, entity_id) VALUES (:entity, :entity_id)");
            $stmt->bindParam(':entity', $entity, PDO::PARAM_STR);
            $stmt->bindParam(':entity_id', $entity_id, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                throw new Exception("Error inserting into actor table.");
            }

            $conn->commit(); // Commit transaction

            if ($entity === 'student') {
                header("Location: ../views/register_student.php?success=Registration successful! You can now log in.");
            } else {
                header("Location: ../views/register_user.php?success=Registration successful! You can now log in.");
            }
            exit();
            
        } else {
            throw new Exception("Invalid entity type.");
        }
    } catch (Exception $e) {
        // **Rollback only if a transaction is active**
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        // Log the error securely (hidden from users)
        error_log("Registration Error: " . $e->getMessage());
    
        // Determine the correct redirect path
        $redirect_page = ($entity === 'student') ? '../register_student.php' : '../register_user.php';
    
        // Redirect with a generic error message (safely encoded)
        header("Location: " . $redirect_page . "?message=" . urlencode("Registration failed. Please try again."));
        exit();
    }
    
}
?>
