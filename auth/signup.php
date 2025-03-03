<?php
require '../dbcon.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entity = $_POST['entity'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];

    try {
        $conn->beginTransaction(); // Start transaction

        if ($entity === 'user') {
            $role_id = $_POST['role_id'];

            // Insert into user table
            $stmt = $conn->prepare("INSERT INTO user (user_email, user_password, role_id, user_first_name, user_last_name) 
                                    VALUES (:email, :password, :role_id, :first_name, :last_name)");
            $stmt->bindParam(':role_id', $role_id);
        } else {
            $institution = $_POST['institution'];

            // Insert into student table
            $stmt = $conn->prepare("INSERT INTO student (stud_email, stud_password, stud_first_name, stud_last_name, institution) 
                                    VALUES (:email, :password, :first_name, :last_name, :institution)");
            $stmt->bindParam(':institution', $institution);
        }

        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->execute();

        $entity_id = $conn->lastInsertId(); // Get the last inserted ID

        // Insert into actor table
        $stmt = $conn->prepare("INSERT INTO actor (entity_type, entity_id) VALUES (:entity, :entity_id)");
        $stmt->bindParam(':entity', $entity);
        $stmt->bindParam(':entity_id', $entity_id);
        $stmt->execute();

        $conn->commit(); // Commit transaction

        header("Location: login.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack(); // Rollback if something goes wrong
        echo "Error: " . $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">
    <div class="card p-4 shadow-lg" style="width: 400px;">
        <h3 class="text-center">Sign Up</h3>
        <form method="POST" action="signup.php">
            <div class="mb-3">
                <label class="form-label">Sign Up As:</label>
                <select name="entity" id="entity" class="form-select" required>
                    <option value="user">User</option>
                    <option value="student">Student</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">First Name:</label>
                <input type="text" name="first_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Last Name:</label>
                <input type="text" name="last_name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email:</label>
                <input type="email" name="email" class="form-control" required>
            </div>
  
            <div class="mb-3">
                <label class="form-label">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
                <small id="passwordHelp" class="text-danger d-none">Password must be at least 6 characters long, contain 1 uppercase letter, and 1 special character.</small>
            </div>
            
            <!-- User Fields -->
            <div id="user-fields" class="mb-3 d-none">
                <label class="form-label">Role:</label>
                <select name="role_id" class="form-select">
                    <option value="1">Employer</option>
                    <option value="2">Professional</option>
                    <option value="3">Moderator</option>
                    <option value="4">Admin</option>
                </select>
            </div>

            <!-- Student Fields -->
            <div id="student-fields" class="mb-3 d-none">
                <label class="form-label">Institution:</label>
                <input type="text" name="institution" class="form-control">
            </div>

            <button type="submit" class="btn btn-success w-100">Sign Up</button>
        </form>
        <p class="text-center mt-3">Already have an account? <a href="login.php">Login here</a></p>
    </div>

    <script>
    function toggleFields() {
        const entity = document.getElementById('entity').value;
        document.getElementById('user-fields').classList.toggle('d-none', entity !== 'user');
        document.getElementById('student-fields').classList.toggle('d-none', entity !== 'student');
    }

        // Trigger on page load
        document.addEventListener("DOMContentLoaded", toggleFields);

        // Trigger on dropdown change
        document.getElementById('entity').addEventListener('change', toggleFields);
    </script>

    <script>
    document.querySelector("form").addEventListener("submit", function(event) {
        const password = document.getElementById("password").value;
        const passwordHelp = document.getElementById("passwordHelp");

        const passwordRegex = /^(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{6,}$/;

        if (!passwordRegex.test(password)) {
            passwordHelp.classList.remove("d-none");
            event.preventDefault(); // Prevent form submission
        } else {
            passwordHelp.classList.add("d-none");
        }
    });
    </script>


</body>
</html>
