<?php
session_start(); 
if (isset($_SESSION['user_id']) && isset($_SESSION['stud_id'])) {
  header("Location: ../views/dashboard.php");
  exit();
}
require '../config/dbcon.php';
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM student WHERE stud_email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student && password_verify($password, $student['stud_password'])) {
        $_SESSION['stud_id'] = $student['stud_id'];
        $_SESSION['entity'] = 'student';  
        header("Location: ../views/dashboard.php");
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Arial', sans-serif;
        }
        .login-container {
            background: #fff;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-container h3 {
            color: #333;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        .form-control {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: none;
        }
        .form-label {
            color: #555;
            font-weight: 500;
        }
        .btn-primary {
            background: #007bff;
            border: none;
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 16px;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .alert-danger {
            background: #ffebee;
            border: none;
            color: #c62828;
            border-radius: 8px;
            padding: 0.75rem;
        }
        a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        a:hover {
            text-decoration: underline;
        }
        .text-muted {
            color: #777;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">
    <div class="login-container">
        <h3 class="text-center mb-4">Student Login</h3>
        <?php if ($error): ?>
            <div class="alert alert-danger mb-3"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST" action="login_student.php">
            <div class="mb-3">
                <label class="form-label">Email:</label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password:</label>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <p class="text-center mt-3 text-muted">Don't have an account? <a href="../views/register_student.php">Sign up here</a></p>
        <p class="text-center mt-2"><a href="../index.php">Back to Home</a></p>
        <p class="text-center mt-2"><a href="login_user.php">Not a Student? Click Here.</a></p>
    </div>
</body>
</html>
