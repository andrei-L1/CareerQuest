<?php
// Turn off error reporting to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 1);
error_reporting(0);
header('Content-Type: application/json');
session_start();

// Authentication check
if (!isset($_SESSION['employer_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once '../config/dbcon.php';
/** @var PDO $conn */
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    // Get JSON input
    $json = file_get_contents('php://input');
    $input = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    // Validate input
    if (empty($input['application_id'])) {
        throw new Exception("Missing required field: application_id");
    }

    // Verify the employer owns this job application
    $checkOwnership = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM application_tracking at
        JOIN job_posting j ON at.job_id = j.job_id
        WHERE at.application_id = :application_id
        AND j.employer_id = :employer_id
    ");
    $checkOwnership->bindParam(':application_id', $input['application_id'], PDO::PARAM_INT);
    $checkOwnership->bindParam(':employer_id', $_SESSION['employer_id'], PDO::PARAM_INT);
    $checkOwnership->execute();
    $result = $checkOwnership->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] == 0) {
        throw new Exception('Unauthorized action');
    }

    // Get student and job details for email before making changes
    $getDetails = $conn->prepare("
        SELECT 
            s.stud_first_name, 
            s.stud_last_name, 
            s.stud_email, 
            j.title as job_title, 
            e.company_name
        FROM application_tracking at
        JOIN student s ON at.stud_id = s.stud_id
        JOIN job_posting j ON at.job_id = j.job_id
        JOIN employer e ON j.employer_id = e.employer_id
        WHERE at.application_id = :application_id
    ");
    $getDetails->bindParam(':application_id', $input['application_id'], PDO::PARAM_INT);
    $getDetails->execute();
    $details = $getDetails->fetch(PDO::FETCH_ASSOC);

    if (!$details) {
        throw new Exception('Application details not found');
    }

    $conn->beginTransaction();

    // Step 1: Reject the application
    $stmt = $conn->prepare("
        UPDATE application_tracking 
        SET application_status = 'Rejected', deleted_at = NOW()
        WHERE application_id = :application_id
    ");
    $stmt->execute([':application_id' => $input['application_id']]);

    if ($stmt->rowCount() === 0) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Application not found or already rejected']);
        exit;
    }

    // Step 2: Cancel any scheduled interview(s) for that application
    $stmt2 = $conn->prepare("
        UPDATE interviews 
        SET status = 'Cancelled'
        WHERE application_id = :application_id AND status = 'Scheduled'
    ");
    $stmt2->execute([':application_id' => $input['application_id']]);

    $conn->commit();

    // ===== EMAIL CONFIGURATION =====
    $mail = new PHPMailer(true);
    
    try {
        // For production, use your real SMTP credentials:
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'powercreeper098@gmail.com';
        $mail->Password = 'iqui fnfi ypkz vrhr';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('no-reply@careerportal.com', $details['company_name']);
        $mail->addAddress($details['stud_email'], $details['stud_first_name'].' '.$details['stud_last_name']);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Application Update for {$details['job_title']} at {$details['company_name']}";
        
        $mail->Body = "
            <h2>Application Status Update</h2>
            <p>Dear {$details['stud_first_name']},</p>
            <p>Thank you for your interest in the <strong>{$details['job_title']}</strong> position at <strong>{$details['company_name']}</strong>.</p>
            
            <p>After careful consideration, we regret to inform you that we have decided to move forward with other candidates whose qualifications more closely match our current needs.</p>
            
            <p>We appreciate the time and effort you invested in your application and encourage you to apply for future openings that may be a better fit for your skills and experience.</p>
            
            <p>Best regards,<br>
            <strong>{$details['company_name']} Hiring Team</strong></p>
        ";

        // Plain text version for non-HTML clients
        $mail->AltBody = "Application Status Update\n\n".
            "Dear {$details['stud_first_name']},\n\n".
            "Thank you for your interest in the {$details['job_title']} position at {$details['company_name']}.\n\n".
            "After careful consideration, we regret to inform you that we have decided to move forward with other candidates.\n\n".
            "We appreciate your time and encourage you to apply for future openings.\n\n".
            "Best regards,\n".
            "{$details['company_name']} Hiring Team";

        $mail->send();
        
        echo json_encode([
            'success' => true,
            'message' => 'Application rejected and notification sent'
        ]);
    } catch (Exception $e) {
        // Email failed but DB transaction succeeded
        error_log("Email error: " . $e->getMessage());
        echo json_encode([
            'success' => true, // Still true because DB operation succeeded
            'message' => 'Application rejected but email failed to send',
            'error' => $e->getMessage()
        ]);
    }

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>