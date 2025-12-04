<?php
require_once '../config/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Authentication check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized. Please log in."]);
    exit();
}

try {
    // Get employer data
    $employer = getEmployerData($conn, $_SESSION['user_id']);
    if (!$employer) {
        http_response_code(404);
        echo json_encode(["error" => "Employer not found or has been deleted."]);
        exit();
    }

    // Handle different request methods
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGetRequest($conn, $employer['employer_id']);
            break;
        case 'POST':
            handlePostRequest($conn, $employer['employer_id']); // Pass employer_id to validate ownership
            break;
        default:
            http_response_code(405);
            echo json_encode(["error" => "Method not allowed"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

function handleGetRequest(PDO $conn, int $employer_id) {
    $applications = getJobApplications($conn, $employer_id);
    
    // Check if pipeline view is requested
    $isPipelineView = isset($_GET['view']) && $_GET['view'] === 'pipeline';
    
    // Organize and output data
    if ($isPipelineView) {
        echo json_encode(formatPipelineData($applications));
    } else {
        echo json_encode(formatApplicationsData($applications));
    }
}

function handlePostRequest(PDO $conn, int $employer_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['action'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing action parameter"]);
        exit();
    }

    switch ($data['action']) {
        case 'update_status':
            updateApplicationStatus($conn, $data, $employer_id);
            break;
        default:
            http_response_code(400);
            echo json_encode(["error" => "Invalid action"]);
    }
}

// ===== CRUD Functions ===== //

function getEmployerData(PDO $conn, int $user_id): ?array {
    $stmt = $conn->prepare("
        SELECT employer_id, company_name, company_logo 
        FROM employer 
        WHERE user_id = :user_id AND deleted_at IS NULL
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function getJobApplications(PDO $conn, int $employer_id): array {
    // Get filter parameters
    $status_filter = isset($_GET['status']) && $_GET['status'] !== 'all' ? $_GET['status'] : null;
    $job_filter = isset($_GET['job']) && $_GET['job'] !== 'all' ? (int)$_GET['job'] : null;
    $date_filter = isset($_GET['date']) && $_GET['date'] !== 'all' ? $_GET['date'] : null;
    $match_filter = isset($_GET['match']) && $_GET['match'] !== 'all' ? $_GET['match'] : null;
    $search_filter = isset($_GET['search']) && !empty(trim($_GET['search'])) ? trim($_GET['search']) : null;
    
    // Build WHERE conditions
    $where_conditions = [
        "jp.employer_id = :employer_id",
        "jp.moderation_status = 'Approved'",
        "jp.deleted_at IS NULL",
        "at.deleted_at IS NULL",
        "(at.application_status != 'Accepted' OR at.updated_at >= DATE_SUB(NOW(), INTERVAL 20 MINUTE) OR at.updated_at IS NULL)"
    ];
    
    $params = [':employer_id' => $employer_id];
    
    // Add status filter
    if ($status_filter) {
        // Map filter values to database values (case-insensitive matching)
        $status_map = [
            'pending' => 'Pending',
            'under review' => 'Under Review',
            'interview scheduled' => 'Interview Scheduled',
            'interview' => 'Interview',
            'offered' => 'Offered',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            'withdrawn' => 'Withdrawn'
        ];
        
        $normalized_status = $status_map[strtolower($status_filter)] ?? $status_filter;
        $where_conditions[] = "at.application_status = :status_filter";
        $params[':status_filter'] = $normalized_status;
    }
    
    // Add job filter
    if ($job_filter) {
        $where_conditions[] = "jp.job_id = :job_filter";
        $params[':job_filter'] = $job_filter;
    }
    
    // Add date filter
    if ($date_filter) {
        switch ($date_filter) {
            case 'today':
                $where_conditions[] = "DATE(at.applied_at) = CURDATE()";
                break;
            case 'week':
                $where_conditions[] = "at.applied_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $where_conditions[] = "at.applied_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'custom':
                // Custom date range would need additional parameters
                // For now, skip custom filter
                break;
        }
    }
    
    // Define match score ranges
    $match_ranges = [
        '80-100' => ['min' => 80, 'max' => 100],
        '60-79' => ['min' => 60, 'max' => 79],
        '40-59' => ['min' => 40, 'max' => 59],
        '0-39' => ['min' => 0, 'max' => 39]
    ];
    
    // Store match filter for later use in HAVING clause
    $match_min = null;
    $match_max = null;
    if ($match_filter && isset($match_ranges[$match_filter])) {
        $match_min = $match_ranges[$match_filter]['min'];
        $match_max = $match_ranges[$match_filter]['max'];
    }
    
    // Add search filter
    if ($search_filter) {
        $where_conditions[] = "(
            CONCAT(s.stud_first_name, ' ', s.stud_last_name) LIKE :search_filter
            OR s.stud_email LIKE :search_filter
            OR sl.skill_name LIKE :search_filter
            OR jp.title LIKE :search_filter
        )";
        $params[':search_filter'] = '%' . $search_filter . '%';
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $sql = "
        SELECT 
            jp.job_id,
            jp.title AS job_title,
            jp.description AS job_description,
            jp.location,
            jp.posted_at,
            jp.moderation_status,
            s.stud_id,
            CONCAT(s.stud_first_name, ' ', s.stud_last_name) AS applicant_name,
            s.stud_email,
            s.resume_file,
            s.profile_picture,
            at.application_id,
            at.application_status,
            at.applied_at,
            at.updated_at,
            ROUND(AVG(sm.match_score), 2) AS avg_match_score,
            GROUP_CONCAT(DISTINCT sl.skill_name) AS skills
        FROM job_posting jp
        LEFT JOIN application_tracking at ON jp.job_id = at.job_id
        LEFT JOIN student s ON at.stud_id = s.stud_id
        LEFT JOIN stud_skill ss ON s.stud_id = ss.stud_id
        LEFT JOIN skill_masterlist sl ON ss.skill_id = sl.skill_id
        LEFT JOIN job_skill js ON jp.job_id = js.job_id
        LEFT JOIN skill_matching sm ON sm.user_skills_id = ss.user_skills_id AND sm.job_skills_id = js.job_skills_id
        WHERE {$where_clause}
        GROUP BY at.application_id, jp.job_id, s.stud_id, jp.title, jp.description, jp.location, 
                 jp.posted_at, jp.moderation_status, s.stud_first_name, 
                 s.stud_last_name, s.stud_email, s.resume_file, 
                 at.application_status, at.applied_at, at.updated_at
    ";
    
    // Add HAVING clause for match score filter if specified
    if ($match_min !== null && $match_max !== null) {
        $sql .= " HAVING avg_match_score >= {$match_min} AND avg_match_score <= {$match_max}";
    }
    
    $sql .= " ORDER BY jp.job_id, at.applied_at DESC";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function updateApplicationStatus(PDO $conn, array $data, int $employer_id): void {
    if (!isset($data['application_id']) || !isset($data['new_status'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required fields"]);
        exit();
    }

    $application_id = $data['application_id'];
    $new_status = $data['new_status'];

    $valid_statuses = ['Pending', 'Under Review', 'Interview Scheduled', 'Interview', 'Offered', 'Accepted', 'Rejected', 'Withdrawn'];
    if (!in_array($new_status, $valid_statuses)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid status"]);
        exit();
    }

    // Verify ownership
    $check_stmt = $conn->prepare("
        SELECT at.application_id
        FROM application_tracking at
        JOIN job_posting jp ON at.job_id = jp.job_id
        WHERE at.application_id = :application_id
        AND jp.employer_id = :employer_id
        AND at.deleted_at IS NULL
    ");
    $check_stmt->execute([
        ':application_id' => $application_id,
        ':employer_id' => $employer_id
    ]);
    if ($check_stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["error" => "Application not found or unauthorized"]);
        exit();
    }

    // Conditional update with safeguard
    if ($new_status === 'Accepted') {
        $stmt = $conn->prepare("
            UPDATE application_tracking 
            SET application_status = :new_status, updated_at = NOW()
            WHERE application_id = :application_id
            AND deleted_at IS NULL
            AND application_status <> :new_status
        ");
    } else {
        $stmt = $conn->prepare("
            UPDATE application_tracking 
            SET application_status = :new_status, updated_at = NULL
            WHERE application_id = :application_id
            AND deleted_at IS NULL
            AND application_status <> :new_status
        ");
    }

    $stmt->execute([
        ':new_status' => $new_status,
        ':application_id' => $application_id
    ]);

    if ($stmt->rowCount() === 0) {
        echo json_encode([
            "success" => false,
            "message" => "No changes made (status was already set to this value)"
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "message" => "Application status updated successfully"
        ]);
    }
}


// ===== Data Formatting Functions ===== //

function formatApplicationsData(array $rows): array {
    $jobs = [];
    
    foreach ($rows as $row) {
        $job_id = $row['job_id'];
        
        if (!isset($jobs[$job_id])) {
            $jobs[$job_id] = [
                "job_id" => $job_id,
                "title" => $row['job_title'],
                "description" => $row['job_description'],
                "location" => $row['location'],
                "posted_at" => $row['posted_at'],
                "moderation_status" => $row['moderation_status'],
                "applicants" => [],
            ];
        }

        if ($row['stud_id']) {
            $jobs[$job_id]["applicants"][] = [
                "application_id" => $row['application_id'],
                "stud_id" => $row['stud_id'],
                "name" => $row['applicant_name'],
                "email" => $row['stud_email'],
                "resume_file" => $row['resume_file'],
                "application_status" => $row['application_status'],
                "applied_at" => $row['applied_at'],
                "match_score" => $row['avg_match_score'],
                "profile_picture" => $row['profile_picture'],
                "skills" => $row['skills'] ? explode(",", $row['skills']) : []
            ];
        }
    }
    
    return array_values($jobs);
}

function formatPipelineData(array $rows): array {
    $pipelineStages = [
        'New Applications' => ['Pending'],
        'Under Review' => ['Under Review'],
        'Interview Scheduled' => ['Interview Scheduled'],
        'Interview' => ['Interview'],
        'Offer' => ['Offered'],
        'Hired' => ['Accepted'],
        'Rejected' => ['Rejected', 'Withdrawn']
    ];    
    
    $pipeline = [];
    
    foreach ($pipelineStages as $stageName => $statuses) {
        $stage = [
            'name' => $stageName,
            'statuses' => $statuses,
            'applicants' => []
        ];
        
        foreach ($rows as $row) {
            if (in_array($row['application_status'], $statuses)) {
                $stage['applicants'][] = [
                    "application_id" => $row['application_id'],
                    "job_id" => $row['job_id'],
                    "job_title" => $row['job_title'],
                    "stud_id" => $row['stud_id'],
                    "name" => $row['applicant_name'],
                    "email" => $row['stud_email'],
                    "resume_file" => $row['resume_file'],
                    "application_status" => $row['application_status'],
                    "applied_at" => $row['applied_at'],
                    "match_score" => $row['avg_match_score'],
                    "profile_picture" => $row['profile_picture'],
                    "skills" => $row['skills'] ? explode(",", $row['skills']) : []
                ];
            }
        }
        
        $pipeline[] = $stage;
    }
    
    return $pipeline;
}