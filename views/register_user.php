<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .progress-bar {
            transition: width 0.5s ease-in-out;
        }
        .card {
            width: 420px;
            border-radius: 12px;
        }
        .progress {
            height: 8px;
            border-radius: 4px;
        }
        .btn {
            border-radius: 6px;
            font-size: 16px;
            padding: 5px;
            margin: 5px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
            margin-bottom: 15px;
        }

    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">
    <div class="card p-4 shadow-lg" style="width: 400px;">
        <h3 class="text-center">Create an Account</h3>
        <div class="progress mb-3">
            <div id="progressBar" class="progress-bar" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <form method="POST" action="../controllers/signup_handler.php" id="signupForm">
            <?php
            if (isset($_GET['message'])) {
                // Sanitize the message to prevent XSS attacks
                $error_message = htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8');
                echo '<div class="alert alert-danger" role="alert">' . $error_message . '</div>';
            }
            ?>
            <?php
            if (isset($_GET['success'])) {
                $success_message = htmlspecialchars($_GET['success'], ENT_QUOTES, 'UTF-8');
                echo '<div class="alert alert-success" role="alert">' . $success_message . '</div>';
            }
            ?>

            <!-- Step 1: Personal Information -->
            <div id="step-1">
                <h5>Step 1: Personal Information</h5>
                <label class="form-label">First Name:</label>
                <input type="text" name="first_name" class="form-control" required autofocus>
                <label class="form-label">Last Name:</label>
                <input type="text" name="last_name" class="form-control" required>
                <input type="hidden" id="entity" name="entity" value="user">
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-primary w-100 mt-3" onclick="nextStep(2)">Next</button>
                </div>
            </div>
            
            <!-- Step 2: Password and Email -->
            <div id="step-2" class="d-none">
                <h5>Step 2: Set Your E-mail and Password</h5>
                <label class="form-label">Email:</label>
                <input type="email" name="email" class="form-control" required pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}">
                <label class="form-label">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required autofocus>
                <label class="form-label">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                <small id="passwordHelp" class="text-danger d-none">Password must be at least 6 characters long, contain 1 uppercase letter, and 1 special character.</small>
                <small id="confirmPasswordHelp" class="text-danger d-none">Passwords do not match.</small>
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary w-50" onclick="prevStep(1)">Back</button>
                    <button type="button" id="nextStep3" class="btn btn-primary w-50" onclick="nextStep(3)" disabled>Next</button>
                </div>
            </div>

            <!-- Step 3: Additional Information -->
            <div id="step-3" class="d-none">
                <h5>Step 3: Additional Information</h5>
                <div id="user-fields">
                    <label class="form-label">Role:</label>
                    <select name="role_id" class="form-select">
                        <option value="1">Employer</option>
                        <option value="2">Professional</option>
                        <option value="3">Moderator</option>
                        <option value="4">Admin</option>
                    </select>
                </div>
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary w-50" onclick="prevStep(2)">Back</button>
                    <button type="submit" class="btn btn-success w-50">Sign Up</button>
                </div>
            </div>
        </form>
        <p class="text-center mt-3">Already have an account? <a href="login.php">Login here</a></p>
    </div>
<script src="../assests/createaccount.js"></script>
</body>
</html>