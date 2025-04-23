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
    $stmt = $conn->prepare("SELECT employer_id, company_name, company_logo FROM employer WHERE user_id = :user_id AND deleted_at IS NULL");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $employer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employer) {
        $_SESSION['error'] = "Employer account not found or has been deleted.";
        header("Location: ../unauthorized.php");
        exit();
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit();
}

// Handle job form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Prepare and sanitize form data
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $location = $_POST['location'] ?? '';
        $job_type_id = $_POST['job_type_id'] ?? null;
        $salary = isset($_POST['salary']) && $_POST['salary'] !== '' ? $_POST['salary'] : null;
        $expires_at = isset($_POST['expires_at']) && $_POST['expires_at'] !== '' ? $_POST['expires_at'] : null;

        // Insert job posting
        $query = "INSERT INTO job_posting 
                  (employer_id, title, description, location, job_type_id, salary, expires_at) 
                  VALUES (:employer_id, :title, :description, :location, :job_type_id, :salary, :expires_at)";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':employer_id', $employer['employer_id'], PDO::PARAM_INT);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':job_type_id', $job_type_id);
        $stmt->bindParam(':salary', $salary);
        $stmt->bindParam(':expires_at', $expires_at);

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