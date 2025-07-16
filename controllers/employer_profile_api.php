<?php
// Prevent any output before JSON response
ob_clean();
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../config/dbcon.php';

header('Content-Type: application/json');

// Check if employer is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in.']);
    exit();
}

// Check if user is an employer
$user_id = $_SESSION['user_id'];
$roleCheck = $conn->prepare("SELECT r.role_title FROM user u JOIN role r ON u.role_id = r.role_id WHERE u.user_id = :user_id");
$roleCheck->execute([':user_id' => $user_id]);
$role = $roleCheck->fetchColumn();
if (strtolower($role) !== 'employer') {
    echo json_encode(['error' => 'Access denied: Employers only.']);
    exit();
}

function getEmployerData($conn, $user_id) {
    $stmt = $conn->prepare("
        SELECT e.*, u.user_first_name, u.user_last_name, u.user_email
        FROM employer e
        JOIN user u ON e.user_id = u.user_id
        WHERE e.user_id = :user_id AND e.deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new Exception("Employer not found.");
    return $row;
}

function getStats($conn, $employer_id) {
    // Jobs posted
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_jobs FROM job_posting WHERE employer_id = :employer_id AND deleted_at IS NULL");
    $stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
    $stmt->execute();
    $totalJobs = $stmt->fetch(PDO::FETCH_ASSOC)['total_jobs'];

    // Applications received
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total_applications
        FROM application_tracking at
        JOIN job_posting jp ON at.job_id = jp.job_id
        WHERE jp.employer_id = :employer_id AND at.deleted_at IS NULL AND jp.deleted_at IS NULL
    ");
    $stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
    $stmt->execute();
    $totalApplications = $stmt->fetch(PDO::FETCH_ASSOC)['total_applications'];

    // Interviews scheduled - check if interviews table exists first
    $totalInterviews = 0;
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total_interviews
            FROM interviews i
            JOIN application_tracking at ON i.application_id = at.application_id
            JOIN job_posting jp ON at.job_id = jp.job_id
            WHERE jp.employer_id = :employer_id AND i.status = 'Scheduled' AND jp.deleted_at IS NULL
        ");
        $stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
        $stmt->execute();
        $totalInterviews = $stmt->fetch(PDO::FETCH_ASSOC)['total_interviews'];
    } catch (Exception $e) {
        // If interviews table doesn't exist, just return 0
        $totalInterviews = 0;
    }

    return [
        'totalJobPostings' => $totalJobs,
        'totalApplicationsReceived' => $totalApplications,
        'totalInterviewsScheduled' => $totalInterviews
    ];
}

function getRecentApplications($conn, $employer_id, $limit = 5) {
    $stmt = $conn->prepare("
        SELECT at.application_id, at.application_status as status, at.applied_at as applied_date, jp.title as job_title, s.stud_first_name, s.stud_last_name
        FROM application_tracking at
        JOIN job_posting jp ON at.job_id = jp.job_id
        JOIN student s ON at.stud_id = s.stud_id
        WHERE jp.employer_id = :employer_id AND at.deleted_at IS NULL AND jp.deleted_at IS NULL
        ORDER BY at.applied_at DESC
        LIMIT :limit
    ");
    $stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getActiveJobs($conn, $employer_id, $limit = 5) {
    $stmt = $conn->prepare("
        SELECT jp.job_id, jp.title, jp.location, jp.salary, jp.posted_at, jp.moderation_status, jp.flagged
        FROM job_posting jp
        WHERE jp.employer_id = :employer_id AND jp.deleted_at IS NULL
        ORDER BY jp.posted_at DESC
        LIMIT :limit
    ");
    $stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

try {
    $employer = getEmployerData($conn, $user_id);
    $employer_id = $employer['employer_id'];
    $stats = getStats($conn, $employer_id);
    $applications = getRecentApplications($conn, $employer_id, 5);
    $jobs = getActiveJobs($conn, $employer_id, 5);

    $response = [
        'employer' => $employer,
        'stats' => $stats,
        'applications' => $applications,
        'jobs' => $jobs
    ];
    
    $jsonResponse = json_encode($response);
    if ($jsonResponse === false) {
        echo json_encode([
            'error' => 'JSON encoding failed: ' . json_last_error_msg()
        ]);
    } else {
        echo $jsonResponse;
    }
} catch (Exception $e) {
    error_log("Employer Profile API Error: " . $e->getMessage());
    $errorResponse = json_encode(['error' => 'Something went wrong. Please try again later.']);
    if ($errorResponse === false) {
        echo json_encode(['error' => 'JSON encoding failed: ' . json_last_error_msg()]);
    } else {
        echo $errorResponse;
    }
}
?>
