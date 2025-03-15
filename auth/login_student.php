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

// Initialize session values if not set
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if (!isset($_SESSION['lockout_time'])) {
    $_SESSION['lockout_time'] = 0;
}

// Check if user is locked out
$current_time = time();
$remaining_time = max(0, $_SESSION['lockout_time'] - $current_time);

if ($_SESSION['login_attempts'] >= $max_attempts && $remaining_time > 0) {
    $error = "Too many failed login attempts. Try again in <span id='countdown'>{$remaining_time}</span> seconds.";
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($remaining_time > 0) {
        exit(); // Stop processing if locked out
    }

    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if (!$email) {
        $error = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("
            SELECT student.*, actor.actor_id 
            FROM student 
            JOIN actor ON student.stud_id = actor.entity_id
            WHERE student.stud_email = :email
            AND student.stud_status = 'active'
        ");        
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            if (password_verify($password, $student['stud_password'])) {
                // Successful login: Reset attempts and lockout
                $_SESSION['stud_id'] = $student['stud_id'];
                $_SESSION['actor_id'] = $student['actor_id']; // Fixed variable name
                $_SESSION['entity'] = 'student';
                $_SESSION['login_attempts'] = 0;
                $_SESSION['lockout_time'] = 0;

                require '../controllers/auth_redirect.php';
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email, password, or inactive account.";
        }

        // Failed login attempt
        $_SESSION['login_attempts']++;

        if ($_SESSION['login_attempts'] >= $max_attempts) {
            $_SESSION['lockout_time'] = time() + $lockout_time;
            $error = "Too many failed login attempts. Try again in <span id='countdown'>{$lockout_time}</span> seconds.";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            font-family: 'Arial', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background: #fff;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
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
            text-align: center;
        }
        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .form-control {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
        .form-label {
            position: absolute;
            top: 50%;
            left: 0.75rem;
            transform: translateY(-50%);
            color: #999;
            font-size: 14px;
            transition: all 0.3s ease;
            pointer-events: none;
        }
        .form-control:focus + .form-label,
        .form-control:not(:placeholder-shown) + .form-label {
            top: 0;
            font-size: 12px;
            color: #007bff;
            background: #fff;
            padding: 0 4px;
        }
        .btn-primary {
            background: linear-gradient(45deg, #0A2647, #2C7865);
            border: none;
            border-radius: 8px;
            padding: 0.75rem;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s ease, transform 0.3s ease;
            width: 100%;
        }
        .btn-primary:hover {
            background: linear-gradient(45deg, #2C7865, #0A2647);
            transform: translateY(-2px);
        }
        .alert-danger {
            background: #ffebee;
            border: none;
            color: #c62828;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .social-login {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
        }
        .social-login .btn {
            flex: 1;
            margin: 0 0.5rem;
            border-radius: 8px;
            padding: 0.5rem;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .social-login .btn:hover {
            transform: translateY(-2px);
        }
        .social-login .btn-google {
            background: #db4437;
            color: #fff;
        }
        .social-login .btn-facebook {
            background: #3b5998;
            color: #fff;
        }
        .text-center {
            text-align: center;
        }
        .text-muted {
            color: #777;
        }
        a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        a:hover {
            text-decoration: underline;
        }
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .remember-me input {
            margin-right: 0.5rem;
        }
        .remember-me label {
            margin: 0;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h3>Student Login</h3>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST" action="login_student.php">
            <div class="form-group">
                <input type="email" name="email" class="form-control" placeholder=" " required>
                <label class="form-label">Email</label>
            </div>
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder=" " required>
                <label class="form-label">Password</label>
            </div>
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember Me</label>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <div class="social-login">
            <button type="button" class="btn btn-google">
                <i class="fab fa-google"></i> Google
            </button>
            <button type="button" class="btn btn-facebook">
                <i class="fab fa-facebook"></i> Facebook
            </button>
        </div>
        <p class="text-center mt-3 text-muted">Don't have an account? <a href="../views/register_student.php">Sign up here</a></p>
        <p class="text-center mt-2"><a href="../index.php">Back to Home</a></p>
        <p class="text-center mt-2"><a href="login_user.php">Not a Student? Click Here.</a></p>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let countdownElement = document.getElementById("countdown");

            if (countdownElement) {
                let timeLeft = parseInt(countdownElement.innerText.replace(/\D/g, '')); // Extract number

                function updateCountdown() {
                    if (timeLeft > 0) {
                        timeLeft--;
                        
                        let minutes = Math.floor(timeLeft / 60);
                        let seconds = timeLeft % 60;
                        countdownElement.innerText = `${minutes}m ${seconds}s`;

                        setTimeout(updateCountdown, 1000);
                    } else {
                        location.reload(); 
                    }
                }

                updateCountdown();
            }
        });

    </script>
</body>
</html>