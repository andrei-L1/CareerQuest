<?php
// Secure session settings (must be before session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);

session_start(); 
session_regenerate_id(true);

// Redirect if already logged in
if (isset($_SESSION['user_id']) || isset($_SESSION['stud_id'])) {
    header("Location: ../views/dashboard.php");
    exit();
}

require '../config/dbcon.php';

// Enforce security settings on PDO
$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

$error = "";

// Brute force protection settings
$max_attempts = 5;
$lockout_time = 300; // 5 minutes (300 seconds)

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if (!isset($_SESSION['lockout_time'])) {
    $_SESSION['lockout_time'] = 0;
}

// Check if the user is locked out
$remaining_time = max(0, $_SESSION['lockout_time'] - time());

if ($_SESSION['login_attempts'] >= $max_attempts && $remaining_time > 0) {
    $error = "Too many failed login attempts. Try again in <span id='countdown'>{$remaining_time}</span> seconds.";
} elseif ($remaining_time <= 0) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['lockout_time'] = 0;
}

// Stop processing if locked out
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($error)) {
        exit(); // Stop processing if locked out
    }

    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if (!$email) {
        $error = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM student WHERE stud_email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student && password_verify($password, $student['stud_password'])) {
            $_SESSION['stud_id'] = $student['stud_id'];
            $_SESSION['entity'] = 'student';
            $_SESSION['login_attempts'] = 0; // Reset on successful login
            $_SESSION['lockout_time'] = 0;
            header("Location: ../views/dashboard.php");
            exit();
        } else {
            $_SESSION['login_attempts']++; // Increment failed attempt counter
            if ($_SESSION['login_attempts'] >= $max_attempts) {
                $_SESSION['lockout_time'] = time() + $lockout_time; // Set lockout expiration
                $error = "Too many failed login attempts. Try again in 5m 0s.";
            } else {
                $error = "Invalid email or password.";
            }
        }
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
<script>
document.addEventListener("DOMContentLoaded", function () {
    let countdownElement = document.getElementById("countdown");

    if (countdownElement) {
        let timeLeft = parseInt(countdownElement.innerText);

        function updateCountdown() {
            if (timeLeft > 0) {
                timeLeft--;
                countdownElement.innerText = timeLeft;
                setTimeout(updateCountdown, 1000);
            } else {
                location.reload(); // Refresh page when countdown reaches 0
            }
        }

        updateCountdown();
    }
});
</script>

</html>
