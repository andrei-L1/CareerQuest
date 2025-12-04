<?php
// Secure session settings (must be before session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_regenerate_id(true);

// Redirect if already logged in
if (isset($_SESSION['user_id']) || isset($_SESSION['stud_id'])) {
    require '../controllers/auth_redirect.php';
    exit();
}

require '../config/dbcon.php';
/** @var PDO $conn */
// Enforce security settings on PDO
$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

$error = "";

// Brute force protection settings
$max_attempts = 5;
$lockout_time = 60; 

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
            SELECT user.*, actor.actor_id, role.role_title
            FROM user 
            JOIN actor ON user.user_id = actor.entity_id
            JOIN role ON user.role_id = role.role_id
            WHERE user.user_email = :email AND actor.entity_type = 'user'
        ");
        
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            if ($user['status'] === 'Deleted') {
                $error = "Your account has been deactivated. Please contact support.";
            } elseif (password_verify($password, $user['user_password'])) {
                // ✅ Check if the user has the "Employer" role
                if ($user['role_title'] !== 'Employer') {
                    $error = "You must have an Employer role to log in.";
                } else {
                    // ✅ Destroy previous session to prevent login conflicts
                    session_unset();
                    session_destroy();
                    session_start();
                    session_regenerate_id(true);

                    // ✅ Store actor_id in session
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['actor_id'] = $user['actor_id']; 
                    $_SESSION['role_id'] = $user['role_id'];
                    $_SESSION['entity'] = 'user';
                    $_SESSION['login_attempts'] = 0;
                    $_SESSION['lockout_time'] = 0;

                    require '../controllers/auth_redirect.php';
                    exit();
                }
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


// Check for 'unauthorized_access' query parameter
if (isset($_GET['unauthorized_access']) && $_GET['unauthorized_access'] == 1) {
    $error = "You do not have permission to access this page.";
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --secondary-color: #f8fafc;
            --text-color: #1e293b;
            --light-text: #64748b;
            --border-color: #e2e8f0;
            --error-color: #dc2626;
            --success-color: #16a34a;
            --google-red: #db4437;
            --facebook-blue: #1877f2;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            line-height: 1.5;
            color: var(--text-color);
        }
        
        .login-container {
            background: #fff;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 420px;
            animation: fadeInUp 0.5s ease-out;
            position: relative;
            overflow: hidden;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h3 {
            color: var(--text-color);
            margin-bottom: 0.5rem;
            font-weight: 700;
            font-size: 1.75rem;
        }
        
        .login-header p {
            color: var(--light-text);
            font-size: 0.9375rem;
            margin-bottom: 0;
            font-weight: 500;
        }
        
        .alert-message {
            padding: 0.875rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
        }
        
        .alert-danger {
            background-color: #fef2f2;
            color: var(--error-color);
            border: 1px solid #fee2e2;
        }
        
        .alert-danger i {
            font-size: 1.25rem;
        }
        
        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9375rem;
            transition: var(--transition);
            background-color: var(--secondary-color);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background-color: #fff;
        }
        
        .form-label {
            position: absolute;
            left: 3rem;
            top: 1rem;
            color: var(--light-text);
            font-size: 0.9375rem;
            transition: var(--transition);
            pointer-events: none;
            background-color: var(--secondary-color);
            padding: 0 0.25rem;
        }
        
        .form-control:focus + .form-label,
        .form-control:not(:placeholder-shown) + .form-label {
            transform: translateY(-1.5rem) translateX(-1.5rem) scale(0.85);
            color: var(--primary-color);
            background-color: #fff;
        }
        
        .form-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-text);
            font-size: 1.125rem;
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--light-text);
            cursor: pointer;
            padding: 0.25rem;
            font-size: 1.125rem;
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .remember-me input {
            width: 1rem;
            height: 1rem;
            accent-color: var(--primary-color);
        }
        
        .remember-me label {
            font-size: 0.875rem;
            color: var(--light-text);
            cursor: pointer;
            margin: 0;
        }
        
        .forgot-password {
            font-size: 0.875rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .forgot-password:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
        
        .btn-primary i {
            transition: transform 0.3s ease;
        }
        
        .btn-primary:hover i {
            transform: translateX(4px);
        }
        
        .social-divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.5rem 0;
            color: var(--light-text);
            font-size: 0.875rem;
        }
        
        .social-divider::before,
        .social-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background-color: var(--border-color);
        }
        
        .social-login {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .btn-google, .btn-facebook {
            flex: 1;
            background-color: white;
            border: 1px solid var(--border-color);
            color: var(--text-color);
            font-weight: 500;
        }
        
        .btn-google:hover {
            background-color: #f8fafc;
            border-color: #cbd5e1;
            color: var(--google-red);
        }
        
        .btn-facebook:hover {
            background-color: #f8fafc;
            border-color: #cbd5e1;
            color: var(--facebook-blue);
        }
        
        .login-footer {
            text-align: center;
            font-size: 0.875rem;
            color: var(--light-text);
            margin-top: 1.5rem;
        }
        
        .login-footer p {
            margin: 0.5rem 0;
        }
        
        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .login-footer a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }
        
        .switch-user-type {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        
        /* Responsive adjustments */
        @media (max-width: 480px) {
            .login-container {
                padding: 1.75rem;
                margin: 0 1rem;
            }
            
            .social-login {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .btn-google, .btn-facebook {
                width: 100%;
            }
        }
        .text-success {
            color: var(--success-color) !important;
        }
        .alert-success {
            background-color: #f0fdf4;
            color: var(--success-color);
            border: 1px solid #dcfce7;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h3>Welcome Back</h3>
            <p>Employer Login</p>
        </div>
        
        <?php if (isset($_GET['account_deleted'])): ?>
            <div class="alert-message alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span>Your account has been deleted. Please contact support.</span>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert-message alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
                <div class="alert-message alert-success" id="success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= htmlspecialchars($_GET['success'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <script>
                    setTimeout(function() {
                        document.getElementById('success').style.display = 'none';
                        // Remove the success from the URL
                        history.replaceState(null, null, location.pathname);
                    }, 5000); // 5000 milliseconds = 5 seconds
                </script>
            <?php endif; ?>
        
        <form method="POST" action="login_employer.php">
            <div class="form-group">
                <i class="fas fa-envelope form-icon"></i>
                <input type="email" name="email" id="email" class="form-control" placeholder=" " required
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <label for="email" class="form-label">Email Address</label>
            </div>
            
            <div class="form-group">
                <i class="fas fa-lock form-icon"></i>
                <input type="password" name="password" id="password" class="form-control" placeholder=" " required>
                <label for="password" class="form-label">Password</label>
                <button type="button" class="password-toggle" aria-label="Show password">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            
            <div class="form-options">
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                <a href="forgot-password.php" class="forgot-password">Forgot password?</a>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <span>Login</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>
        

        <div class="login-footer">
            <p class="switch-user-type">
                Don't have an account?
                <a href="../index.php?opensignupModal=true">Sign up here</a>
            </p>
            <p><a href="../index.php">Back to Home</a></p>
            <p class="switch-user-type">
                Not an Employer? 
                <a href="../index.php?openloginModal=true">Click here</a>
            </p>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Password toggle functionality
            const passwordToggle = document.querySelector('.password-toggle');
            if (passwordToggle) {
                passwordToggle.addEventListener('click', function() {
                    const passwordInput = document.getElementById('password');
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.setAttribute('aria-label', type === 'password' ? 'Show password' : 'Hide password');
                    this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                });
            }
            
            // Countdown timer (if present on page)
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
            
            // Add animation to form elements when page loads
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach((group, index) => {
                group.style.opacity = '0';
                group.style.transform = 'translateY(10px)';
                group.style.animation = `fadeInUp 0.3s ease-out ${index * 0.1}s forwards`;
            });
        });
    </script>
</body>
</html>