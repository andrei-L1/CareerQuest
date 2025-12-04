<?php
require_once '../config/dbcon.php';
/** @var PDO $conn */
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

    // Check if OTP data exists in session
    if (!isset($_SESSION['otp_data'])) {
        throw new Exception('OTP session expired. Please request a new OTP.');
    }

    $otpData = $_SESSION['otp_data'];

    // Validate OTP input
    if (empty($_POST['otp']) || strlen($_POST['otp']) !== 6) {
        throw new Exception('Please enter a valid 6-digit OTP');
    }

    // Check OTP expiry
    if (time() > $otpData['expiry']) {
        unset($_SESSION['otp_data']);
        throw new Exception('OTP has expired. Please request a new one.');
    }

    // Check attempts
    if ($otpData['attempts'] >= 3) {
        unset($_SESSION['otp_data']);
        throw new Exception('Too many attempts. Please request a new OTP.');
    }

    // Verify OTP
    if (!password_verify($_POST['otp'], $otpData['otp_hash'])) {
        $_SESSION['otp_data']['attempts']++;
        $attemptsLeft = 3 - $_SESSION['otp_data']['attempts'];
        
        if ($attemptsLeft <= 0) {
            unset($_SESSION['otp_data']);
            throw new Exception('Too many incorrect attempts. Please request a new OTP.');
        }
        
        throw new Exception("Incorrect OTP. $attemptsLeft attempts remaining.");
    }

    // OTP verified successfully
    $_SESSION['email_verified'] = true;
    $_SESSION['verified_email'] = $otpData['email'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Email verified successfully',
        'timestamp' => time()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}