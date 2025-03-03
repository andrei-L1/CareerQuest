<?php
require '../dbcon.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $entity = htmlspecialchars($_POST['entity']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = htmlspecialchars($_POST['first_name']);
    $last_name = htmlspecialchars($_POST['last_name']);

    // Validate inputs
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }
    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }
    if (strlen($password) < 6 || !preg_match('/[A-Z]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
        die("Password must be at least 6 characters long, contain 1 uppercase letter, and 1 special character.");
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    try {
        $conn->beginTransaction();

        // Check if email already exists
        $stmt = $conn->prepare("SELECT * FROM user WHERE user_email = :email UNION SELECT * FROM student WHERE stud_email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            die("Email already exists.");
        }

        // Insert into user or student table
        if ($entity === 'user') {
            $role_id = $_POST['role_id'];
            $stmt = $conn->prepare("INSERT INTO user (user_email, user_password, role_id, user_first_name, user_last_name) 
                                    VALUES (:email, :password, :role_id, :first_name, :last_name)");
            $stmt->bindParam(':role_id', $role_id);
        } else {
            $institution = htmlspecialchars($_POST['institution']);
            $stmt = $conn->prepare("INSERT INTO student (stud_email, stud_password, stud_first_name, stud_last_name, institution) 
                                    VALUES (:email, :password, :first_name, :last_name, :institution)");
            $stmt->bindParam(':institution', $institution);
        }

        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->execute();

        $entity_id = $conn->lastInsertId();

        // Insert into actor table
        $stmt = $conn->prepare("INSERT INTO actor (entity_type, entity_id) VALUES (:entity, :entity_id)");
        $stmt->bindParam(':entity', $entity);
        $stmt->bindParam(':entity_id', $entity_id);
        $stmt->execute();

        $conn->commit();
        header("Location: login.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error: " . $e->getMessage()); // Log error
        die("An error occurred. Please try again later.");
    }
}
?>