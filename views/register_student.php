<?php 
session_start();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Sign Up | Professional Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
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
            --progress-start: #0A2647;
            --progress-end: #2C7865;
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
        
        .signup-container {
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
        
        .signup-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .signup-header h3 {
            color: var(--text-color);
            margin-bottom: 0.5rem;
            font-weight: 700;
            font-size: 1.75rem;
        }
        
        .signup-header p {
            color: var(--light-text);
            font-size: 0.9375rem;
            margin-bottom: 0;
            font-weight: 500;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            background-color: var(--border-color);
            margin-bottom: 1.5rem;
        }
        
        .progress-bar {
            background: linear-gradient(45deg, var(--progress-start), var(--progress-end));
            border-radius: 4px;
            transition: width 0.5s ease-in-out;
        }
        
        .step-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 1.5rem;
            position: relative;
            padding-left: 1.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .step-title::before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        #step-1 .step-title::before { content: "1"; }
        #step-2 .step-title::before { content: "2"; }
        #step-3 .step-title::before { content: "3"; }
        
        .form-group {
            position: relative;
            margin-bottom: 1.25rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
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
            top: 0.875rem;
            color: var(--light-text);
            font-size: 0.9375rem;
            transition: var(--transition);
            pointer-events: none;
            background-color: var(--secondary-color);
            padding: 0 0.25rem;
        }
        
        .form-control:focus + .form-label,
        .form-control:not(:placeholder-shown) + .form-label {
            transform: translateY(-1.25rem) translateX(-1.5rem) scale(0.85);
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
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.875rem 1.25rem;
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
        
        .btn-outline-secondary {
            border: 1px solid var(--border-color);
            color: var(--text-color);
            background-color: white;
        }
        
        .btn-outline-secondary:hover {
            background-color: var(--secondary-color);
            border-color: var(--border-color);
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #15803d;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(21, 128, 61, 0.2);
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
        
        .alert-success {
            background-color: #f0fdf4;
            color: var(--success-color);
            border: 1px solid #dcfce7;
        }
        
        .alert-message i {
            font-size: 1.25rem;
        }
        
        #passwordChecklist {
            margin: 0.5rem 0 1rem;
            padding-left: 0;
            list-style: none;
        }
        
        #passwordChecklist li {
            margin-bottom: 0.375rem;
            font-size: 0.8125rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .text-danger {
            color: var(--error-color) !important;
        }
        
        .text-success {
            color: var(--success-color) !important;
        }
        
        .signup-footer {
            text-align: center;
            font-size: 0.875rem;
            color: var(--light-text);
            margin-top: 1.5rem;
        }
        
        .signup-footer p {
            margin: 0.5rem 0;
        }
        
        .signup-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .signup-footer a:hover {
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
            .signup-container {
                padding: 1.75rem;
                margin: 0 1rem;
            }
            
            .btn {
                padding: 0.75rem 1rem;
                font-size: 0.875rem;
            }
        }
        .is-invalid {
            border-color: var(--error-color) !important;
        }

        .is-invalid + .form-label {
            color: var(--error-color) !important;
        }
    </style>
</head>
<body>
    <div class="signup-container" data-aos="fade-up" data-aos-duration="1000">
        <div class="signup-header">
            <h3>Create an Account</h3>
            <p>Register as a Student</p>
        </div>
        
        <div class="progress">
            <div id="progressBar" class="progress-bar" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        
        <form method="POST" action="../controllers/signup_handler.php" id="signupForm">
            <?php if (isset($_GET['message'])): ?>
                <div class="alert-message alert-danger" id="message">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <script>
                    setTimeout(function() {
                        document.getElementById('message').style.display = 'none';
                        // Remove the message from the URL
                        history.replaceState(null, null, location.pathname);
                    }, 5000); // 5000 milliseconds = 5 seconds
                </script>
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
            
            <!-- Step 1: Personal Information -->
            <div id="step-1">
                <h5 class="step-title">Personal Information</h5>
                
                <div class="form-group">
                    <i class="fas fa-user form-icon"></i>
                    <input type="text" name="first_name" class="form-control" placeholder=" " required autofocus>
                    <label for="first_name" class="form-label">First Name</label>
                </div>
                
                <div class="form-group">
                    <i class="fas fa-user form-icon"></i>
                    <input type="text" name="middle_name" class="form-control" placeholder=" ">
                    <label for="middle_name" class="form-label">Middle Name (optional)</label>
                </div>
                
                <div class="form-group">
                    <i class="fas fa-user form-icon"></i>
                    <input type="text" name="last_name" class="form-control" placeholder=" " required>
                    <label for="last_name" class="form-label">Last Name</label>
                </div>
                
                <div class="form-group">
                    <i class="fas fa-id-card form-icon"></i>
                    <input type="text" name="student_id" class="form-control" placeholder=" " required>
                    <label for="student_id" class="form-label">Student ID</label>
                </div>
                
                <input type="hidden" id="entity" name="entity" value="student">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <button type="button" class="btn btn-primary w-100 mt-2" onclick="nextStep(2)">
                    <span>Continue</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>

            <!-- Step 2: Password and Email -->
            <div id="step-2" class="d-none">
                <h5 class="step-title">Account Credentials</h5>
                
                <div class="form-group">
                    <i class="fas fa-envelope form-icon"></i>
                    <input type="email" name="email" class="form-control" placeholder=" " required>
                    <label for="email" class="form-label">Email Address</label>
                </div>
                
                <div class="form-group">
                    <i class="fas fa-lock form-icon"></i>
                    <input type="password" id="password" name="password" class="form-control" placeholder=" " required>
                    <label for="password" class="form-label">Password</label>
                    <button type="button" class="password-toggle" aria-label="Show password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <ul class="list-unstyled" id="passwordChecklist">
                    <li id="lengthCheck" class="text-danger" data-text="At least 6 characters">
                        <i class="fas fa-times"></i> At least 6 characters
                    </li>
                    <li id="uppercaseCheck" class="text-danger" data-text="At least 1 uppercase letter">
                        <i class="fas fa-times"></i> At least 1 uppercase letter
                    </li>
                    <li id="specialCharCheck" class="text-danger" data-text="At least 1 special character">
                        <i class="fas fa-times"></i> At least 1 special character
                    </li>
                </ul>
                
                <div class="form-group">
                    <i class="fas fa-lock form-icon"></i>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder=" " required>
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                </div>
                
                <small id="confirmPasswordHelp" class="text-danger d-none">Passwords do not match.</small>
                
                <div class="d-flex justify-content-between gap-3 mt-3">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" onclick="prevStep(1)">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button type="button" id="nextStep3" class="btn btn-primary flex-grow-1" onclick="nextStep(3)" disabled>
                        <span>Continue</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Step 3: Additional Information -->
            <div id="step-3" class="d-none">
                <h5 class="step-title">Education Details</h5>
                
                <div class="form-group">
                    <i class="fas fa-school form-icon"></i>
                    <input type="text" name="institution" class="form-control" placeholder=" ">
                    <label for="institution" class="form-label">Institution</label>
                </div>
                
                <div class="d-flex justify-content-between gap-3 mt-3">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" onclick="prevStep(2)">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button type="submit" class="btn btn-success flex-grow-1">
                        <span>Complete Registration</span>
                        <i class="fas fa-check"></i>
                    </button>
                </div>
            </div>
        </form>
        
        <div class="signup-footer">
            <p>Already have an account? <a href="../auth/login_student.php">Login here</a></p>
            <p><a href="../index.php">Back to Home</a></p>
            <p class="switch-user-type">
                Not a Student? 
                <a href="../index.php?opensignupModal=true">Click here</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
        
        function validatePassword() {
            const passwordField = document.getElementById("password");
            const confirmPasswordField = document.getElementById("confirm_password");
            const confirmPasswordHelp = document.getElementById("confirmPasswordHelp");
            const nextButton = document.getElementById("nextStep3");

            const passwordValue = passwordField.value.trim();
            const confirmPasswordValue = confirmPasswordField.value.trim();

            // Validation Rules
            const lengthCheck = document.getElementById("lengthCheck");
            const uppercaseCheck = document.getElementById("uppercaseCheck");
            const specialCharCheck = document.getElementById("specialCharCheck");

            const hasLength = passwordValue.length >= 6;
            const hasUppercase = /[A-Z]/.test(passwordValue);
            const hasSpecialChar = /[^a-zA-Z0-9]/.test(passwordValue);

            // Update Checklist
            updateChecklist(lengthCheck, hasLength);
            updateChecklist(uppercaseCheck, hasUppercase);
            updateChecklist(specialCharCheck, hasSpecialChar);

            // Validate password match
            let isValid = hasLength && hasUppercase && hasSpecialChar;
            if (confirmPasswordValue.length > 0 && passwordValue !== confirmPasswordValue) {
                confirmPasswordHelp.classList.remove("d-none");
                confirmPasswordField.classList.add("is-invalid");
                isValid = false;
            } else {
                confirmPasswordHelp.classList.add("d-none");
                confirmPasswordField.classList.remove("is-invalid");
            }

            nextButton.disabled = !isValid;
            return isValid;
        }

        function updateChecklist(element, isValid) {
            const text = element.getAttribute("data-text");

            if (isValid) {
                element.classList.remove("text-danger");
                element.classList.add("text-success");
                element.innerHTML = `<i class="fas fa-check"></i> ${text}`;
            } else {
                element.classList.remove("text-success");
                element.classList.add("text-danger");
                element.innerHTML = `<i class="fas fa-times"></i> ${text}`;
            }
        }

        document.addEventListener("DOMContentLoaded", () => {
            const passwordField = document.getElementById("password");
            const confirmPasswordField = document.getElementById("confirm_password");
            const passwordChecklist = document.getElementById("passwordChecklist");

            // Validate password on input change
            passwordField.addEventListener("input", validatePassword);
            confirmPasswordField.addEventListener("input", validatePassword);

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

            // Hide checklist when confirm password is focused
            confirmPasswordField.addEventListener("focus", () => {
                passwordChecklist.style.display = "none";
            });

            // Show checklist when password is focused
            passwordField.addEventListener("focus", () => {
                passwordChecklist.style.display = "block";
            });
        });

        function nextStep(nextStepNumber) {
            const currentStepNumber = nextStepNumber - 1;
            if (!validateStep(currentStepNumber)) {
                return; // Don't proceed if validation fails
            }
            
            document.querySelectorAll('[id^="step-"]').forEach(el => {
                el.classList.add('d-none');
            });
            document.getElementById(`step-${nextStepNumber}`).classList.remove('d-none');
            
            // Update progress bar (since you have 3 steps, each step is 33%)
            const progressPercentage = nextStepNumber * 33.33;
            document.getElementById('progressBar').style.width = `${progressPercentage}%`;
            document.getElementById('progressBar').setAttribute('aria-valuenow', progressPercentage);
            
            // Focus on first input in the new step
            document.querySelector(`#step-${nextStepNumber} input`)?.focus();
        }

        function prevStep(step) {
            document.querySelectorAll('[id^="step-"]').forEach(el => {
                el.classList.add('d-none');
            });
            document.getElementById(`step-${step}`).classList.remove('d-none');
            
            // Update progress bar
            const progressPercentage = step * 33.33;
            document.getElementById('progressBar').style.width = `${progressPercentage}%`;
            document.getElementById('progressBar').setAttribute('aria-valuenow', progressPercentage);
        }

        function validateStep(currentStep) {
            let isValid = true;
            
            if (currentStep == 1) {
                const required = ['first_name', 'last_name', 'student_id'];
                
                required.forEach(field => {
                    const input = document.querySelector(`[name="${field}"]`);
                    if (!input.value.trim()) {
                        input.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });
            }
            else if (currentStep == 2) {
                // Validate email
                const emailInput = document.querySelector('[name="email"]');
                if (!emailInput.value.trim()) {
                    emailInput.classList.add('is-invalid');
                    isValid = false;
                } else if (!/^\S+@\S+\.\S+$/.test(emailInput.value.trim())) {
                    emailInput.classList.add('is-invalid');
                    isValid = false;
                } else {
                    emailInput.classList.remove('is-invalid');
                }
                
                // Validate password
                if (!validatePassword()) {
                    isValid = false;
                }
                
                // Validate password confirmation
                const confirmPassword = document.getElementById("confirm_password");
                if (confirmPassword.value.trim() !== document.getElementById("password").value.trim()) {
                    confirmPassword.classList.add('is-invalid');
                    document.getElementById("confirmPasswordHelp").classList.remove('d-none');
                    isValid = false;
                } else {
                    confirmPassword.classList.remove('is-invalid');
                    document.getElementById("confirmPasswordHelp").classList.add('d-none');
                }
            }
            
            return isValid;
        }
    </script>
</body>
</html>