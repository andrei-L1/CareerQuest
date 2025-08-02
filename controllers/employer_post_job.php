<?php
require_once '../config/dbcon.php';

// Enable error reporting during development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the employer is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get employer data
global $conn;

try {
    $stmt = $conn->prepare("SELECT employer_id, company_name, company_logo, `status`, document_url FROM employer WHERE user_id = :user_id AND deleted_at IS NULL");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $employer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employer) {
        $_SESSION['error'] = "Employer account not found or has been deleted.";
        header("Location: ../unauthorized.php");
        exit();
    }

    // Check if employer status is Verification or document_url is empty
    if ($employer['status'] === 'Verification' || empty($employer['document_url'])) {
        $errorMessage = $employer['status'] === 'Verification' 
            ? "Your account is under verification. Please wait for approval before posting jobs."
            : "You must upload a verification document before posting jobs.";
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => $errorMessage]);
        exit();
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit();
}

// Handle job form submission
// Handle job form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Prepare and sanitize form data
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $location = $_POST['location'] ?? '';
        $job_type_id = $_POST['job_type_id'] ?? null;
        $min_salary = isset($_POST['min_salary']) && $_POST['min_salary'] !== '' ? $_POST['min_salary'] : null;
        $max_salary = isset($_POST['max_salary']) && $_POST['max_salary'] !== '' ? $_POST['max_salary'] : null;
        $salary_type = $_POST['salary_type'] ?? 'Yearly';
        $salary_disclosure = isset($_POST['salary_disclosure']) && $_POST['salary_disclosure'] === 'on' ? 1 : 0;
        $expires_at = isset($_POST['expires_at']) && $_POST['expires_at'] !== '' ? $_POST['expires_at'] : null;
        $visible_to = $_POST['visible_to'] ?? 'both'; // New field for visibility

        // Validate visible_to value
        if (!in_array($visible_to, ['students', 'applicants', 'both'])) {
            $visible_to = 'both'; // Default to 'both' if invalid
        }

        // Insert job posting
        $query = "INSERT INTO job_posting 
                  (employer_id, title, description, location, job_type_id, min_salary, max_salary, salary_type, salary_disclosure, expires_at, visible_to) 
                  VALUES (:employer_id, :title, :description, :location, :job_type_id, :min_salary, :max_salary, :salary_type, :salary_disclosure, :expires_at, :visible_to)";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':employer_id', $employer['employer_id'], PDO::PARAM_INT);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':job_type_id', $job_type_id);
        $stmt->bindParam(':min_salary', $min_salary);
        $stmt->bindParam(':max_salary', $max_salary);
        $stmt->bindParam(':salary_type', $salary_type);
        $stmt->bindParam(':salary_disclosure', $salary_disclosure, PDO::PARAM_BOOL);
        $stmt->bindParam(':expires_at', $expires_at);
        $stmt->bindParam(':visible_to', $visible_to);

        $stmt->execute();
        $jobId = $conn->lastInsertId();

        // Insert job skills if provided
        if (!empty($_POST['skills']) && is_array($_POST['skills'])) {
            $skillQuery = "INSERT INTO job_skill (skill_id, job_id, importance, group_no) 
                           VALUES (:skill_id, :job_id, :importance, :group_no)";
            $skillStmt = $conn->prepare($skillQuery);

            foreach ($_POST['skills'] as $skill) {
                if (!empty($skill['skill_id'])) {
                    $skill_id = $skill['skill_id'];
                    $importance = $skill['importance'] ?? 'Medium'; 
                    $group_no = $jobId; 

                    $skillStmt->bindParam(':skill_id', $skill_id, PDO::PARAM_INT);
                    $skillStmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
                    $skillStmt->bindParam(':importance', $importance);
                    $skillStmt->bindParam(':group_no', $group_no, PDO::PARAM_INT); 
                    $skillStmt->execute();
                }
            }
        }

        $conn->commit();
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>