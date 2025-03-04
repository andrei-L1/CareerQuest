<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        .card {
            width: 420px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 2rem;
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }

        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
        }

        .progress-bar {
            background: linear-gradient(45deg, #0A2647, #2C7865);
            border-radius: 4px;
            transition: width 0.5s ease-in-out;
        }

        .form-control {
            border-radius: 6px;
            border: 1px solid #ced4da;
            padding: 10px;
            font-size: 14px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-control:focus {
            border-color: #0A2647;
            box-shadow: 0 0 0 3px rgba(10, 38, 71, 0.1);
        }

        .btn {
            border-radius: 6px;
            font-size: 16px;
            padding: 10px 20px;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(45deg, #0A2647, #2C7865);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #2C7865, #0A2647);
            transform: translateY(-2px);
        }

        .btn-outline-secondary {
            border-color: #0A2647;
            color: #0A2647;
        }

        .btn-outline-secondary:hover {
            background-color: #0A2647;
            color: white;
        }

        .btn-success {
            background: linear-gradient(45deg, #28a745, #218838);
            border: none;
        }

        .btn-success:hover {
            background: linear-gradient(45deg, #218838, #28a745);
            transform: translateY(-2px);
        }

        a {
            color: #0A2647;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #2C7865;
            text-decoration: underline;
        }

        .alert {
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="card p-4 shadow-lg" style="width: 400px;" data-aos="fade-up" data-aos-duration="1000">
        <h3 class="text-center">Create an Account</h3>
        <h6 class="text-center">Register as an Employer/Professional</h6>
        <div class="progress mb-3">
            <div id="progressBar" class="progress-bar" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <form method="POST" action="../controllers/signup_handler.php" id="signupForm">
            <?php
            if (isset($_GET['message'])) {
                $error_message = htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8');
                echo '<div class="alert alert-danger" role="alert">' . $error_message . '</div>';
            }
            if (isset($_GET['success'])) {
                $success_message = htmlspecialchars($_GET['success'], ENT_QUOTES, 'UTF-8');
                echo '<div class="alert alert-success" role="alert">' . $success_message . '</div>';
            }
            ?>
            <!-- Step 1: Personal Information -->
            <div id="step-1">
                <h5>Step 1: Personal Information</h5>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="first_name" class="form-control" placeholder="First Name" required autofocus>
                </div>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="last_name" class="form-control" placeholder="Last Name" required>
                </div>
                <input type="hidden" id="entity" name="entity" value="user">
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-primary w-100 mt-3" onclick="nextStep(2)">Next</button>
                </div>
            </div>
            <!-- Step 2: Password and Email -->
            <div id="step-2" class="d-none">
                <h5>Step 2: Set Your E-mail and Password</h5>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                </div>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                </div>
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
        <p class="text-center mt-3">Are you a Student? <a href="register_student.php">Create an Account here</a></p>
        <p class="text-center mt-2"><a href="../index.php">Back to Home</a></p>
        <p class="text-center mt-3">Already have an account? <a href="../auth/login_user.php">Login here</a></p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
    <script src="../assests/createaccount.js"></script>
</body>
</html>