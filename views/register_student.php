<?php 
if(session_status() === PHP_SESSION_NONE) {
session_start();
}
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

        /* OTP Input Styles */
        .otp-container {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
            justify-content: center;
        }
        
        .otp-input {
            width: 3rem;
            height: 3.5rem;
            text-align: center;
            font-size: 1.5rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: var(--transition);
        }
        
        .otp-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .resend-otp {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.875rem;
        }
        
        .resend-otp a {
            color: var(--primary-color);
            cursor: pointer;
            text-decoration: none;
        }
        
        .resend-otp a:hover {
            text-decoration: underline;
        }
        
        .resend-otp .countdown {
            color: var(--light-text);
        }
        
        .timer-text {
            color: var(--light-text);
            font-size: 0.875rem;
            text-align: center;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="signup-container" data-aos="fade-up" data-aos-duration="1000">
        <div class="signup-header">
            <h3>Create an Account</h3>
            <p>Register as an Applicant</p>
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
                        history.replaceState(null, null, location.pathname);
                    }, 5000);
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
                        history.replaceState(null, null, location.pathname);
                    }, 5000);
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
                    <input type="email" name="email" id="email" class="form-control" placeholder=" " required>
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
                    <button type="button" id="nextStep3" class="btn btn-primary flex-grow-1" onclick="verifyEmailBeforeProceeding()" disabled>
                        <span>Continue</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- Step 3: OTP Verification -->
            <div id="step-3" class="d-none">
                <h5 class="step-title">Verify Your Email</h5>
                
                <div class="alert-message alert-success">
                    <i class="fas fa-info-circle"></i>
                    <span>We've sent a 6-digit verification code to <span id="displayEmail"></span>. Please check your inbox.</span>
                </div>
                
                <div class="otp-container">
                    <input type="text" class="otp-input" maxlength="1" data-index="1" autocomplete="off">
                    <input type="text" class="otp-input" maxlength="1" data-index="2" autocomplete="off">
                    <input type="text" class="otp-input" maxlength="1" data-index="3" autocomplete="off">
                    <input type="text" class="otp-input" maxlength="1" data-index="4" autocomplete="off">
                    <input type="text" class="otp-input" maxlength="1" data-index="5" autocomplete="off">
                    <input type="text" class="otp-input" maxlength="1" data-index="6" autocomplete="off">
                </div>
                
                <input type="hidden" id="otp" name="otp">
                <input type="hidden" id="otp_timestamp" name="otp_timestamp">
                
                <div class="timer-text" id="timer">Code expires in <span id="countdown">300</span> seconds</div>
                
                <div class="resend-otp">
                    Didn't receive the code? <a href="#" id="resendOtp">Resend OTP</a>
                    <span class="countdown d-none">in <span id="resendCountdown">60</span>s</span>
                </div>
                
                <div class="d-flex justify-content-between gap-3 mt-3">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" onclick="prevStep(2)">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn btn-primary flex-grow-1" onclick="validateOtp()">
                        <span>Verify</span>
                        <i class="fas fa-check"></i>
                    </button>
                </div>
            </div>

            <!-- Step 4: Additional Information -->
            <div id="step-4" class="d-none">
                <h5 class="step-title">Education Details</h5>
                
                <div class="form-group">
                     <i class="fas fa-user form-icon"></i>
                     <select name="edu_background" id="edu_background" class="form-control" placeholder=" " required>
                        <option value="">Select</option>
                        <option value="College Student">College Student</option>
                        <option value="Graduate Student">Graduate Student</option>
                        <option value="Professional">Professional</option>
                        <option value="Not a Student">Not a Student</option>
                     </select>
                     <label for="edu_background" class="form-label">Educational Background</label>
                </div>
                
                <div class="form-group" id="institutionGroup" style="display: none;">
                    <i class="fas fa-school form-icon"></i>
                    <input type="text" name="institution" class="form-control" placeholder=" " id="institution">
                    <label for="institution" class="form-label">Institution</label>
                </div>
                <div class="form-group" id="graduationGroup" style="display: none;">
                    <i class="fas fa-calendar-alt form-icon"></i>
                    <input type="number" name="graduation_yr" class="form-control" placeholder=" " id="graduation_yr" min="2025" max="2100" step="1">
                    <label for="graduation_yr" class="form-label">Expected Graduation Year</label>
                </div>
                
                <div class="d-flex justify-content-between gap-3 mt-3">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" onclick="prevStep(3)">
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
        
        // OTP Verification Functions
        let otpResendTimer;
        let otpExpiryTimer;
        let canResendOtp = true;
        
        function verifyEmailBeforeProceeding() {
            if (!validateStep(2)) return;
            
            const email = document.getElementById('email').value.trim();
            const entity = document.getElementById('entity').value; // Get entity value
            
            // Show loading state
            const nextButton = document.getElementById('nextStep3');
            const originalText = nextButton.innerHTML;
            nextButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending OTP...';
            nextButton.disabled = true;
            
            // Send email and entity to server for OTP generation
            fetch('../controllers/send_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `email=${encodeURIComponent(email)}&entity=${encodeURIComponent(entity)}&csrf_token=${encodeURIComponent('<?= $_SESSION['csrf_token'] ?>')}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show OTP step
                    document.getElementById('displayEmail').textContent = email;
                    nextStep(3);
                    
                    // Start OTP expiry timer
                    startOtpTimer();
                } else {
                    alert('Failed to send OTP: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while sending OTP');
            })
            .finally(() => {
                nextButton.innerHTML = originalText;
                nextButton.disabled = false;
            });
        }
        
        function startOtpTimer() {
            let seconds = 300; // 5 minutes
            const timerElement = document.getElementById('countdown');
            
            clearInterval(otpExpiryTimer);
            otpExpiryTimer = setInterval(() => {
                seconds--;
                timerElement.textContent = seconds;
                
                if (seconds <= 0) {
                    clearInterval(otpExpiryTimer);
                    document.getElementById('timer').innerHTML = 'The verification code has expired.';
                    document.getElementById('timer').classList.add('text-danger');
                }
            }, 1000);
        }
        
        function startResendTimer() {
            let seconds = 60;
            const resendLink = document.getElementById('resendOtp');
            const countdownElement = document.getElementById('resendCountdown');
            const countdownContainer = document.querySelector('.resend-otp .countdown');
            
            resendLink.style.display = 'none';
            countdownContainer.classList.remove('d-none');
            countdownElement.textContent = seconds;
            
            clearInterval(otpResendTimer);
            otpResendTimer = setInterval(() => {
                seconds--;
                countdownElement.textContent = seconds;
                
                if (seconds <= 0) {
                    clearInterval(otpResendTimer);
                    resendLink.style.display = 'inline';
                    countdownContainer.classList.add('d-none');
                    canResendOtp = true;
                }
            }, 1000);
        }
        
        function validateOtp() {
            const otpInputs = document.querySelectorAll('.otp-input');
            let otp = '';
            
            otpInputs.forEach(input => {
                otp += input.value;
            });
            
            if (otp.length !== 6) {
                alert('Please enter the complete 6-digit OTP code');
                return;
            }
            
            // Verify OTP with server
            fetch('../controllers/verify_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `otp=${encodeURIComponent(otp)}&csrf_token=${encodeURIComponent('<?= $_SESSION['csrf_token'] ?>')}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Store verified status and proceed to next step
                    document.getElementById('otp').value = otp;
                    document.getElementById('otp_timestamp').value = data.timestamp;
                    nextStep(4);
                } else {
                    alert('OTP verification failed: ' + data.message);
                    // Clear OTP inputs
                    otpInputs.forEach(input => {
                        input.value = '';
                    });
                    // Focus on first OTP input
                    otpInputs[0].focus();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while verifying OTP');
            });
        }
        
        // OTP input auto-focus and navigation
        document.addEventListener('DOMContentLoaded', () => {
            const otpInputs = document.querySelectorAll('.otp-input');
            
            otpInputs.forEach((input, index) => {
                // Handle paste event
                input.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const pasteData = e.clipboardData.getData('text');
                    if (/^\d{6}$/.test(pasteData)) {
                        for (let i = 0; i < 6; i++) {
                            if (otpInputs[i]) {
                                otpInputs[i].value = pasteData[i];
                            }
                        }
                        otpInputs[5].focus();
                    }
                });
                
                // Handle input event
                input.addEventListener('input', (e) => {
                    if (input.value.length === 1) {
                        if (index < otpInputs.length - 1) {
                            otpInputs[index + 1].focus();
                        } else {
                            input.blur();
                        }
                    }
                });
                
                // Handle backspace
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && input.value.length === 0 && index > 0) {
                        otpInputs[index - 1].focus();
                    }
                });
            });
            
            // Resend OTP handler
            document.getElementById('resendOtp').addEventListener('click', (e) => {
                e.preventDefault();
                
                if (!canResendOtp) return;
                
                canResendOtp = false;
                startResendTimer();
                
                // Resend OTP request
                const email = document.getElementById('email').value.trim();
                
                fetch('../controllers/send_otp.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `email=${encodeURIComponent(email)}&resend=true&csrf_token=${encodeURIComponent('<?= $_SESSION['csrf_token'] ?>')}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reset OTP expiry timer
                        clearInterval(otpExpiryTimer);
                        startOtpTimer();
                        
                        // Update UI
                        document.getElementById('timer').classList.remove('text-danger');
                        document.getElementById('timer').innerHTML = 'Code expires in <span id="countdown">300</span> seconds';
                        
                        // Clear OTP inputs
                        document.querySelectorAll('.otp-input').forEach(input => {
                            input.value = '';
                        });
                        document.querySelector('.otp-input').focus();
                    } else {
                        alert('Failed to resend OTP: ' + data.message);
                        canResendOtp = true;
                        clearInterval(otpResendTimer);
                        document.getElementById('resendOtp').style.display = 'inline';
                        document.querySelector('.resend-otp .countdown').classList.add('d-none');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while resending OTP');
                    canResendOtp = true;
                    clearInterval(otpResendTimer);
                    document.getElementById('resendOtp').style.display = 'inline';
                    document.querySelector('.resend-otp .countdown').classList.add('d-none');
                });
            });
        });
        
        // Previous functions remain the same with updated step numbers
        function nextStep(nextStepNumber) {
            const currentStepNumber = nextStepNumber - 1;
            if (currentStepNumber > 0 && !validateStep(currentStepNumber)) {
                return;
            }
            
            document.querySelectorAll('[id^="step-"]').forEach(el => {
                el.classList.add('d-none');
            });
            document.getElementById(`step-${nextStepNumber}`).classList.remove('d-none');
            
            // Update progress bar (now 4 steps, each 25%)
            const progressPercentage = nextStepNumber * 25;
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
            const progressPercentage = step * 25;
            document.getElementById('progressBar').style.width = `${progressPercentage}%`;
            document.getElementById('progressBar').setAttribute('aria-valuenow', progressPercentage);
            
            // Clear OTP timers if going back from OTP step
            if (step < 3) {
                clearInterval(otpExpiryTimer);
                clearInterval(otpResendTimer);
            }
        }

        function validateStep(currentStep) {
            let isValid = true;
            
            if (currentStep == 1) {
                const required = ['first_name', 'last_name'];
                
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
            else if (currentStep == 3) {
                // OTP validation is handled separately in validateOtp()
                return true;
            }
            else if (currentStep == 4) {
                const edu = document.querySelector('[name="edu_background"]');
                if (!edu.value) {
                    edu.classList.add('is-invalid');
                    isValid = false;
                } else {
                    edu.classList.remove('is-invalid');
                }
            }
            
            return isValid;
        }

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
            
            // Education background change handler
            const eduSelect = document.getElementById("edu_background");
            const institutionGroup = document.getElementById("institutionGroup");
            const graduationGroup = document.getElementById("graduationGroup");
            
            function toggleInstitutionField() {
                if (eduSelect.value === "College Student" || eduSelect.value === "Graduate Student") {
                    institutionGroup.style.display = "block";
                    document.getElementById("institution").setAttribute("required", "required");
                    graduationGroup.style.display = "block";
                    document.getElementById("graduation_yr").setAttribute("required", "required");
                } else {
                    institutionGroup.style.display = "none";
                    document.getElementById("institution").removeAttribute("required");
                    graduationGroup.style.display = "none";
                    document.getElementById("graduation_yr").removeAttribute("required");
                }
            }
            
            eduSelect.addEventListener("change", toggleInstitutionField);
            toggleInstitutionField();
        });
    </script>
</body>
</html>