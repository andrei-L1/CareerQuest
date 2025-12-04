<?php
require_once '../config/dbcon.php';
/** @var PDO $conn */
require '../vendor/autoload.php';
require '../helpers/generate_otp.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

header('Content-Type: application/json');

try {
    // Validate CSRF token
    if (!isset($_POST['csrf_token'])) {
        throw new Exception('CSRF token is required');
    }
    
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        throw new Exception('Invalid CSRF token');
    }

    // Validate email
    if (empty($_POST['email'])) {
        throw new Exception('Email is required');
    }

    // Validate entity type
    $entity = $_POST['entity'] ?? null;
    if (!in_array($entity, ['student', 'employer'])) {
        throw new Exception('Invalid entity type');
    }

    $email = strtolower(trim($_POST['email']));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Check if email already exists in the appropriate table
    if ($entity === 'student') {
        $checkEmail = $conn->prepare("SELECT stud_id FROM student WHERE stud_email = ?");
    } else { // employer
        $checkEmail = $conn->prepare("SELECT user_id FROM user WHERE user_email = ?");
    }
    
    $checkEmail->execute([$email]);
    
    if ($checkEmail->rowCount() > 0) {
        throw new Exception('Email already registered');
    }

    // Rate limiting: Prevent rapid resend
    if (isset($_SESSION['otp_data']['last_sent']) && (time() - $_SESSION['otp_data']['last_sent']) < 60) {
        throw new Exception('Please wait before requesting another OTP.');
    }

    // Generate OTP
    $otp = generateOTP(6);
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);
    $expiryTime = time() + 300; // 5 minutes from now

    // Store OTP in session
    $_SESSION['otp_data'] = [
        'otp_hash' => $otpHash,
        'email' => $email,
        'entity' => $entity,
        'expiry' => $expiryTime,
        'attempts' => 0,
        'last_sent' => time()
    ];

    // Send OTP via email
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'careerquest93@gmail.com'; 
    $mail->Password = 'bofq qhdz bdzp ixzo';     
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    try {
        $mail->setFrom('no-reply@careerquest.com', 'Career Quest');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Your Verification Code';
        $mail->Body = "
            <h2>Email Verification</h2>
            <p>Thank you for registering with Career Quest as an " . htmlspecialchars($entity, ENT_QUOTES, 'UTF-8') . ". Please use the following verification code to complete your registration:</p>
            <div style='font-size: 24px; font-weight: bold; letter-spacing: 2px; margin: 20px 0;'>$otp</div>
            <p>This code will expire in 5 minutes. If you didn't request this code, please ignore this email.</p>
            <p>Best regards,<br><strong>Career Quest Team</strong></p>
        ";
        $mail->AltBody = "Your verification code is: $otp\n\nThis code will expire in 5 minutes.";
        $mail->send();
        
        echo json_encode([
            'success' => true,
            'message' => 'OTP sent successfully',
            'timestamp' => time()
        ]);
    } catch (Exception $e) {
        error_log("Email error for $email at " . date('Y-m-d H:i:s') . ": " . $e->getMessage());
        throw new Exception('Failed to send OTP. Please try again.');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>