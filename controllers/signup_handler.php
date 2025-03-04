<?php
require '../config/dbcon.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate and sanitize input
        $entity = $_POST['entity'];
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);

        if (!$email || empty($password) || empty($first_name) || empty($last_name)) {
            throw new Exception("Invalid input data.");
        }

        // Use Argon2id for better security
        $hashed_password = password_hash($password, PASSWORD_ARGON2ID);

        $conn->beginTransaction(); // Start transaction

        if ($entity === 'user') {
            $role_id = intval($_POST['role_id']); // Ensure it's an integer

            $stmt = $conn->prepare("INSERT INTO user (user_email, user_password, role_id, user_first_name, user_last_name) 
                                    VALUES (:email, :password, :role_id, :first_name, :last_name)");
            $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
        } else if ($entity === 'student') {
            $institution = trim($_POST['institution']);
            if (empty($institution)) {
                throw new Exception("Institution field is required.");
            }

            $stmt = $conn->prepare("INSERT INTO student (stud_email, stud_password, stud_first_name, stud_last_name, institution) 
                                    VALUES (:email, :password, :first_name, :last_name, :institution)");
            $stmt->bindParam(':institution', $institution, PDO::PARAM_STR);
        }

        // Bind common parameters securely
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
        $stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
        $stmt->bindParam(':last_name', $last_name, PDO::PARAM_STR);
        $stmt->execute();

        $entity_id = $conn->lastInsertId(); // Get the last inserted ID

        // Insert into actor table
        $stmt = $conn->prepare("INSERT INTO actor (entity_type, entity_id) VALUES (:entity, :entity_id)");
        $stmt->bindParam(':entity', $entity, PDO::PARAM_STR);
        $stmt->bindParam(':entity_id', $entity_id, PDO::PARAM_INT);
        $stmt->execute();

        $conn->commit(); // Commit transaction

        header("Location: ../index.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack(); // Rollback if something goes wrong
        error_log("Registration Error: " . $e->getMessage()); // Log the error
        echo "An error occurred. Please try again later.";
    }
}
?>
