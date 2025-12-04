<?php
header('Content-Type: application/json'); 
require '../config/dbcon.php';
/** @var PDO $conn */
require '../auth/auth_check_student.php';

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to validate and sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Function to send JSON response
function jsonResponse($success, $message = '', $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function getSavedJobs($studentId, $page = 1, $perPage = 10, $sort = 'saved_at', $order = 'DESC', $search = '') {
    global $conn;
    
    try {
        // Validate sort column to prevent SQL injection
        $validSortColumns = ['saved_at', 'posted_at', 'title', 'company_name', 'salary'];
        $sort = in_array($sort, $validSortColumns) ? $sort : 'saved_at';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        
        // Use COALESCE for salary sorting to handle NULL max_salary
        $sortColumn = $sort;
        if ($sort === 'salary') {
            $sortColumn = 'COALESCE(jp.max_salary, jp.min_salary)';
        }
        
        // Calculate offset for pagination
        $offset = ($page - 1) * $perPage;
        
        // Base WHERE conditions - added check for active employer
        $whereConditions = "WHERE sj.stud_id = :stud_id 
                            AND sj.deleted_at IS NULL 
                            AND jp.deleted_at IS NULL
                            AND e.status = 'Active'";
        
        // Add search condition if search term exists
        $searchCondition = '';
        if (!empty($search)) {
            $searchCondition = " AND (jp.title LIKE :search OR jp.description LIKE :search OR e.company_name LIKE :search)";
        }
        
        // Get total count for pagination (including search filter)
        $countQuery = "
            SELECT COUNT(*) as total 
            FROM saved_jobs sj
            JOIN job_posting jp ON sj.job_id = jp.job_id
            JOIN employer e ON jp.employer_id = e.employer_id
            $whereConditions $searchCondition
        ";
        
        $countStmt = $conn->prepare($countQuery);
        $countStmt->bindValue(':stud_id', $studentId, PDO::PARAM_INT);
        if (!empty($search)) {
            $countStmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated results (including search filter)
        $query = "
            SELECT sj.saved_id, jp.job_id, jp.title, jp.description, jp.location, 
                   jp.min_salary, jp.max_salary, jp.salary_type, jp.salary_disclosure, 
                   jp.posted_at, sj.saved_at, e.company_name
            FROM saved_jobs sj
            JOIN job_posting jp ON sj.job_id = jp.job_id
            JOIN employer e ON jp.employer_id = e.employer_id
            $whereConditions $searchCondition
            ORDER BY $sortColumn $order
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':stud_id', $studentId, PDO::PARAM_INT);
        if (!empty($search)) {
            $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'jobs' => $jobs,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

// Function to delete a saved job
function deleteSavedJob($jobId, $studentId) {
    global $conn;
    
    try {
        // Start transaction
        $conn->beginTransaction();
        
        // Verify the job exists and belongs to the student
        $verifyStmt = $conn->prepare("
            SELECT 1 FROM saved_jobs 
            WHERE job_id = :job_id AND stud_id = :stud_id AND deleted_at IS NULL
        ");
        $verifyStmt->execute([':job_id' => $jobId, ':stud_id' => $studentId]);
        
        if (!$verifyStmt->fetch()) {
            $conn->rollBack();
            return ['success' => false, 'message' => 'Job not found in your saved list'];
        }
        
        // Soft delete the job
        $query = "UPDATE saved_jobs SET deleted_at = NOW() 
                  WHERE job_id = :job_id AND stud_id = :stud_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
        $stmt->bindParam(':stud_id', $studentId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $conn->commit();
            return ['success' => true, 'message' => 'Job removed from saved list'];
        } else {
            $conn->rollBack();
            return ['success' => false, 'message' => 'Failed to remove job'];
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Delete error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error'];
    }
}

// Main request handling
try {
    $stud_id = $_SESSION['stud_id'];
    
    // Handle GET request for fetching saved jobs
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = isset($_GET['per_page']) ? min(max(1, intval($_GET['per_page'])), 50) : 10;
        $sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'saved_at';
        $order = isset($_GET['order']) ? sanitizeInput($_GET['order']) : 'DESC';
        
        if (!empty($search) && strlen($search) < 2) {
            jsonResponse(false, 'Search term too short');
        }

        $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
        $result = getSavedJobs($stud_id, $page, $perPage, $sort, $order, $search); 
        
        if ($result === false) {
            jsonResponse(false, 'Failed to fetch saved jobs');
        }
        
        jsonResponse(true, 'Saved jobs retrieved', $result);
    }
    // Handle POST request for deleting a saved job
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['job_id']) || !isset($_POST['student_id'])) {
            jsonResponse(false, 'Missing required parameters');
        }
        
        // Validate ownership
        if ($_POST['student_id'] != $stud_id) {
            jsonResponse(false, 'Unauthorized action');
        }
        
        $jobId = intval($_POST['job_id']);
        $studentId = intval($_POST['student_id']);
        
        $result = deleteSavedJob($jobId, $studentId);
        jsonResponse($result['success'], $result['message']);
    }
    // Handle unsupported methods
    else {
        jsonResponse(false, 'Method not allowed');
    }
} catch (Exception $e) {
    error_log("Controller error: " . $e->getMessage());
    jsonResponse(false, 'An error occurred');
}