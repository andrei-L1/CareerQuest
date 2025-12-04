<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the employer is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Database connection
require_once '../config/dbcon.php';
/** @var PDO $conn */
try {
    // Test database connection
    $conn->query("SELECT 1");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper functions (unchanged)
function getTimeAgo($date) {
    $time = strtotime($date);
    $time_difference = time() - $time;

    if ($time_difference < 1) { return 'just now'; }
    $condition = [
        12 * 30 * 24 * 60 * 60 => 'year',
        30 * 24 * 60 * 60 => 'month',
        24 * 60 * 60 => 'day',
        60 * 60 => 'hour',
        60 => 'minute',
        1 => 'second'
    ];

    foreach ($condition as $secs => $str) {
        $d = $time_difference / $secs;
        if ($d >= 1) {
            $t = round($d);
            return $t . ' ' . $str . ($t > 1 ? 's' : '') . ' ago';
        }
    }
}

function truncateDescription($text, $length = 250) {
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length) . '...';
    }
    return $text;
}

function getStatusClass($status, $expires_at) {
    if ($status == 'Pending') return 'status-pending';
    if ($status == 'Approved') {
        if ($expires_at && strtotime($expires_at) < time()) {
            return 'status-expired';
        }
        return 'status-approved';
    }
    if ($status == 'Rejected') return 'status-rejected';
    if ($status == 'Paused') return 'status-paused';
    return '';
}

function getStatusText($status, $expires_at) {
    if ($status == 'Pending') return 'Pending Approval';
    if ($status == 'Approved') {
        if ($expires_at && strtotime($expires_at) < time()) {
            return 'Expired';
        }
        return 'Active';
    }
    if ($status == 'Rejected') return 'Rejected';
    if ($status == 'Paused') return 'Paused';
    return $status;
}

// Get employer ID
$user_id = $_SESSION['user_id'];

// Fetch employer_id based on user_id
try {
    $employer_stmt = $conn->prepare("SELECT employer_id, company_name, company_logo FROM employer WHERE user_id = :user_id AND deleted_at IS NULL");
    $employer_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $employer_stmt->execute();
    $employer_row = $employer_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employer_row) {
        $_SESSION['error'] = "Employer account not found or has been deleted.";
        header("Location: ../unauthorized.php");
        exit();
    }

    $employer_id = $employer_row['employer_id'];
    $company_name = $employer_row['company_name'];
    $company_logo = $employer_row['company_logo'];

} catch (PDOException $e) {
    die("Error fetching employer data: " . $e->getMessage());
}

// Initialize variables
$jobs = [];
$total_jobs = 0;
$active_jobs = 0;
$pending_jobs = 0;
$expired_jobs = 0;
$current_page = 1;
$jobs_per_page = 10;
$total_pages = 1;

// Handle pagination
if (isset($_GET['page']) && is_numeric($_GET['page'])) {
    $current_page = max(1, (int)$_GET['page']);
}

// Handle filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Build base query
    $query = "SELECT j.job_id, j.title, j.description, j.location, 
                     j.min_salary, j.max_salary, j.salary_type, j.salary_disclosure, 
                     j.posted_at, j.expires_at, j.moderation_status, j.img_url, j.flagged,
                     t.job_type_title,
                     COUNT(DISTINCT a.application_id) AS applicant_count,
                     COUNT(DISTINCT CASE WHEN a.applied_at > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN a.application_id END) AS new_applicants,
                     (SELECT COUNT(*) FROM saved_jobs WHERE job_id = j.job_id) AS saved_count
              FROM job_posting j
              LEFT JOIN job_type t ON j.job_type_id = t.job_type_id
              LEFT JOIN application_tracking a ON j.job_id = a.job_id AND a.deleted_at IS NULL
              WHERE j.employer_id = :employer_id AND j.deleted_at IS NULL";

    $params = [':employer_id' => $employer_id];

    // Apply status filter
    if ($status_filter) {
        if ($status_filter == 'Expired') {
            $query .= " AND j.expires_at IS NOT NULL AND j.expires_at < NOW() AND j.moderation_status = 'Approved'";
        } else {
            $query .= " AND j.moderation_status = :status";
            $params[':status'] = $status_filter;
        }
    }

    // Apply date filter
    if ($date_filter) {
        switch ($date_filter) {
            case 'today':
                $query .= " AND DATE(j.posted_at) = CURDATE()";
                break;
            case 'week':
                $query .= " AND j.posted_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $query .= " AND j.posted_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $query .= " AND j.posted_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
        }
    }

    // Apply search filter
    if (!empty($search_filter)) {
        $query .= " AND (j.title LIKE :search OR j.description LIKE :search)";
        $params[':search'] = "%$search_filter%";
    }

    // Complete the query with grouping
    $query .= " GROUP BY j.job_id, j.title, j.description, j.location, 
                        j.min_salary, j.max_salary, j.salary_type, j.salary_disclosure, 
                        j.posted_at, j.expires_at, j.moderation_status, j.img_url, j.flagged, t.job_type_title";

    // Get total count for pagination
    $count_query = "SELECT COUNT(*) AS total FROM ($query) AS count_query";
    $stmt = $conn->prepare($count_query);
    foreach ($params as $key => $value) {
        $type = PDO::PARAM_STR;
        if ($key === ':employer_id') $type = PDO::PARAM_INT;
        $stmt->bindValue($key, $value, $type);
    }
    $stmt->execute();
    $total_jobs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = max(1, ceil($total_jobs / $jobs_per_page));

    // Apply pagination
    $offset = ($current_page - 1) * $jobs_per_page;
    $query .= " ORDER BY j.posted_at DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $jobs_per_page;
    $params[':offset'] = $offset;

    // Execute main query
    $stmt = $conn->prepare($query);
    
    // Bind parameters with types
    foreach ($params as $key => $value) {
        $type = PDO::PARAM_STR;
        if ($key === ':employer_id') $type = PDO::PARAM_INT;
        if ($key === ':limit' || $key === ':offset') $type = PDO::PARAM_INT;
        $stmt->bindValue($key, $value, $type);
    }
    
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process job data
    foreach ($jobs as &$job) {
        // Get skills for this job
        $skills_query = "SELECT s.skill_id, s.skill_name 
                         FROM job_skill js
                         JOIN skill_masterlist s ON js.skill_id = s.skill_id
                         WHERE js.job_id = :job_id AND js.deleted_at IS NULL";
        $skills_stmt = $conn->prepare($skills_query);
        $skills_stmt->bindParam(':job_id', $job['job_id'], PDO::PARAM_INT);
        $skills_stmt->execute();
        $job['skills'] = $skills_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count job statuses for stats
        if ($job['moderation_status'] == 'Approved') {
            if ($job['expires_at'] && strtotime($job['expires_at']) < time()) {
                $expired_jobs++;
            } else {
                $active_jobs++;
            }
        } elseif ($job['moderation_status'] == 'Pending') {
            $pending_jobs++;
        }
    }
    unset($job); // Break the reference

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    error_log($error_message);
    $_SESSION['error'] = "An error occurred while fetching job data. Please try again later.";
}

function getJobDetails($job_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT j.job_id, j.title, j.description, j.location, 
               j.min_salary, j.max_salary, j.salary_type, j.salary_disclosure, 
               j.posted_at, j.expires_at, j.moderation_status, j.img_url, j.flagged,
               j.visible_to, jt.job_type_title 
        FROM job_posting j 
        LEFT JOIN job_type jt ON j.job_type_id = jt.job_type_id 
        WHERE j.job_id = ? AND j.employer_id = ? AND j.deleted_at IS NULL
    ");
    $stmt->execute([$job_id, $_SESSION['employer_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getJobTypes() {
    global $conn;
    $stmt = $conn->query("SELECT job_type_id, job_type_title FROM job_type");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAvailableSkills() {
    global $conn;
    $stmt = $conn->query("SELECT skill_id, skill_name FROM skill_masterlist WHERE deleted_at IS NULL");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getJobSkills($job_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT s.skill_id, s.skill_name, js.importance
        FROM job_skill js 
        JOIN skill_masterlist s ON js.skill_id = s.skill_id 
        WHERE js.job_id = ? AND js.deleted_at IS NULL
    ");
    $stmt->execute([$job_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getJobs($employer_id, $page = 1, $per_page = 10, $status = '', $date = '', $search = '') {
    global $conn;
    $offset = ($page - 1) * $per_page;
    $conditions = ['j.employer_id = ?', 'j.deleted_at IS NULL'];
    $params = [$employer_id];

    if ($status) {
        $conditions[] = 'j.moderation_status = ?';
        $params[] = $status;
    }
    if ($date) {
        if ($date === 'today') {
            $conditions[] = 'DATE(j.posted_at) = CURDATE()';
        } elseif ($date === 'week') {
            $conditions[] = 'j.posted_at >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)';
        } elseif ($date === 'month') {
            $conditions[] = 'j.posted_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)';
        } elseif ($date === 'year') {
            $conditions[] = 'j.posted_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)';
        }
    }
    if ($search) {
        $conditions[] = '(j.title LIKE ? OR j.description LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $query = "
        SELECT j.job_id, j.title, j.description, j.location, 
               j.min_salary, j.max_salary, j.salary_type, j.salary_disclosure, 
               j.posted_at, j.expires_at, j.moderation_status, j.img_url, j.flagged,
               j.visible_to, jt.job_type_title, 
               (SELECT COUNT(*) FROM application_tracking a WHERE a.job_id = j.job_id AND a.deleted_at IS NULL) as applicant_count,
               (SELECT COUNT(*) FROM saved_jobs s WHERE s.job_id = j.job_id AND s.deleted_at IS NULL) as saved_count
        FROM job_posting j
        LEFT JOIN job_type jt ON j.job_type_id = jt.job_type_id
        $where
        ORDER BY j.posted_at DESC
        LIMIT ? OFFSET ?
    ";
    $params[] = $per_page;
    $params[] = $offset;

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTotalJobs($employer_id, $status = '', $date = '', $search = '') {
    global $conn;
    $conditions = ['employer_id = ?', 'deleted_at IS NULL'];
    $params = [$employer_id];

    if ($status) {
        $conditions[] = 'moderation_status = ?';
        $params[] = $status;
    }
    if ($date) {
        if ($date === 'today') {
            $conditions[] = 'DATE(posted_at) = CURDATE()';
        } elseif ($date === 'week') {
            $conditions[] = 'posted_at >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)';
        } elseif ($date === 'month') {
            $conditions[] = 'posted_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)';
        } elseif ($date === 'year') {
            $conditions[] = 'posted_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)';
        }
    }
    if ($search) {
        $conditions[] = '(title LIKE ? OR description LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $stmt = $conn->prepare("SELECT COUNT(*) FROM job_posting $where");
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

?>