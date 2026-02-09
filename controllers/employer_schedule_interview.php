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
    $requiredFields = ['application_id', 'email', 'date', 'mode', 'location'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Validate date format
    if (!DateTime::createFromFormat('Y-m-d\TH:i', $input['date'])) {
        throw new Exception('Invalid date format');
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

    $conn->beginTransaction();

    // Update application status
    $updateApp = $conn->prepare("
        UPDATE application_tracking 
        SET application_status = 'Interview Scheduled' 
        WHERE application_id = :application_id
    ");
    $updateApp->bindParam(':application_id', $input['application_id'], PDO::PARAM_INT);
    $updateApp->execute();

    // Insert interview record
    $insertInterview = $conn->prepare("
        INSERT INTO interviews (
            application_id,
            interview_date,
            interview_mode,
            location_details,
            additional_notes
        ) VALUES (
            :application_id,
            :interview_date,
            :interview_mode,
            :location_details,
            :additional_notes
        )
    ");
    
    $insertInterview->bindParam(':application_id', $input['application_id'], PDO::PARAM_INT);
    $insertInterview->bindParam(':interview_date', $input['date']);
    $insertInterview->bindParam(':interview_mode', $input['mode']);
    $insertInterview->bindParam(':location_details', $input['location']);
    $insertInterview->bindParam(':additional_notes', $input['notes']);
    $insertInterview->execute();

    // Get student and job details for email
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

    $conn->commit();

    // ===== EMAIL CONFIGURATION =====
    $mail = new PHPMailer(true);
    
    try {
         /* 
        // Mailtrap SMTP Configuration (for testing)
        $mail->isSMTP();
        $mail->Host = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth = true;
        $mail->Port = 2525;
        $mail->Username = '43d74f4b3c2834'; // Your Mailtrap username
        $mail->Password = '6fb34015cb5630'; // Your Mailtrap password
         */
       
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
        $mail->Subject = "Interview Invitation for {$details['job_title']} at {$details['company_name']}";
        
        $mail->Body = "
            <h2>Interview Invitation</h2>
            <p>Dear {$details['stud_first_name']},</p>
            <p>We're pleased to invite you for an interview regarding your application for <strong>{$details['job_title']}</strong> at <strong>{$details['company_name']}</strong>.</p>
            
            <h3>Interview Details:</h3>
            <ul>
                <li><strong>Date:</strong> ".date('l, F j, Y', strtotime($input['date']))."</li>
                <li><strong>Time:</strong> ".date('g:i a', strtotime($input['date']))."</li>
                <li><strong>Mode:</strong> {$input['mode']}</li>
                <li><strong>Location:</strong> {$input['location']}</li>
            </ul>
            
            ".(!empty($input['notes']) ? "<h3>Additional Notes:</h3><p>{$input['notes']}</p>" : "")."
            
            <p>Please do not reply to this email.</p>
            
            <p>Best regards,<br>
            <strong>{$details['company_name']} Hiring Team</strong></p>
        ";

        // Plain text version for non-HTML clients
        $mail->AltBody = "Interview Invitation\n\n".
            "Dear {$details['stud_first_name']},\n\n".
            "We're inviting you for an interview for {$details['job_title']} at {$details['company_name']}.\n\n".
            "Date: ".date('l, F j, Y', strtotime($input['date']))."\n".
            "Time: ".date('g:i a', strtotime($input['date']))."\n".
            "Mode: {$input['mode']}\n".
            "Location: {$input['location']}\n\n".
            (!empty($input['notes']) ? "Notes:\n{$input['notes']}\n\n" : "").
            "Please reply to confirm.\n\n".
            "Best regards,\n".
            "{$details['company_name']} Hiring Team";

        $mail->send();
        
        echo json_encode([
            'success' => true,
            'message' => 'Interview scheduled and email sent successfully'
        ]);
    } catch (Exception $e) {
        // Email failed but DB transaction succeeded
        error_log("Email error: " . $e->getMessage());
        echo json_encode([
            'success' => true, // Still true because DB operation succeeded
            'message' => 'Interview scheduled but email failed to send',
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