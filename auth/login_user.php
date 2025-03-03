<?php
session_start(); 
require '../config/dbcon.php';
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM user WHERE user_email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['user_password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['entity'] = 'user';  
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
    <title>User Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="d-flex align-items-center justify-content-center vh-100">
    <div class="login-container">
        <h3 class="text-center mb-3">User Login</h3>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST" action="login_user.php">
            <div class="mb-3">
                <label class="form-label">Email:</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password:</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <p class="text-center mt-3">Don't have an account? <a href="../views/signup.php">Sign up here</a></p>
        <p class="text-center mt-3"><a href="../index.php">Back</a></p>
    </div>
</body>
</html>
