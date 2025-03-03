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

    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100 bg-light">
    <div class="card p-4 shadow-lg" style="width: 400px;">
        <h3 class="text-center">Create an Account</h3>
        <div class="progress mb-3">
            <div id="progressBar" class="progress-bar" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <form method="POST" action="signup_handler.php" id="signupForm">
            <!-- Step 1: Select Entity -->
            <div id="step-1">
                <h5>Step 1: Choose Your Role</h5>
                <label class="form-label">Sign Up As:</label>
                <select name="entity" id="entity" class="form-select" required>
                    <option value="user">User</option>
                    <option value="student">Student</option>
                </select>
                <button type="button" class="btn btn-primary w-100 mt-3" onclick="nextStep(2)">Next</button>
            </div>
            
            <!-- Step 2: Personal Information -->
            <div id="step-2" class="d-none">
                <h5>Step 2: Personal Information</h5>
                <label class="form-label">First Name:</label>
                <input type="text" name="first_name" class="form-control" required autofocus>
                <label class="form-label">Last Name:</label>
                <input type="text" name="last_name" class="form-control" required>
                <label class="form-label">Email:</label>
                <input type="email" name="email" class="form-control" required>
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary w-50" onclick="prevStep(1)">Back</button>
                    <button type="button" class="btn btn-primary w-50" onclick="nextStep(3)">Next</button>
                </div>

            </div>
            
            <!-- Step 3: Password -->
            <div id="step-3" class="d-none">
                <h5>Step 3: Set Your Password</h5>
                <label class="form-label">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required autofocus>
                <label class="form-label">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                <small id="passwordHelp" class="text-danger d-none">Password must be at least 6 characters long, contain 1 uppercase letter, and 1 special character.</small>
                <small id="confirmPasswordHelp" class="text-danger d-none">Passwords do not match.</small>
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary w-50" onclick="prevStep(2)">Back</button>
                    <button type="button" id="nextStep3" class="btn btn-primary w-50" onclick="nextStep(4)" disabled>Next</button>
                </div>
            </div>

            
            <!-- Step 4: Additional Fields -->
            <div id="step-4" class="d-none">
                <h5>Step 4: Additional Information</h5>
                <div id="user-fields" class="d-none">
                    <label class="form-label">Role:</label>
                    <select name="role_id" class="form-select">
                        <option value="1">Employer</option>
                        <option value="2">Professional</option>
                        <option value="3">Moderator</option>
                        <option value="4">Admin</option>
                    </select>
                </div>
                <div id="student-fields" class="d-none">
                    <label class="form-label">Institution:</label>
                    <input type="text" name="institution" class="form-control">
                </div>
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary w-50" onclick="prevStep(3)">Back</button>
                    <button type="submit" class="btn btn-success w-50">Sign Up</button>
                </div>
            </div>
        </form>
        <p class="text-center mt-3">Already have an account? <a href="login.php">Login here</a></p>
    </div>
    <script src="../assests/signup.js"></script>
</body>
</html>