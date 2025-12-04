<?php
require '../auth/employer_auth.php';
require '../config/dbcon.php';
/** @var PDO $conn */
function cleanData($str) {
    $str = str_replace('"', '""', $str);
    return '"' . $str . '"';
}

try {
    $employer_id = $_SESSION['employer_id'];

    // Base query
    $query = "
        SELECT 
            at.application_id,
            at.application_status,
            at.applied_at,
            s.stud_first_name,
            s.stud_last_name,
            s.stud_email,
            s.institution,
            s.edu_background,
            s.graduation_yr,
            jp.title AS job_title,
            jp.location AS job_location,
            jt.job_type_title,
            sm.match_score
        FROM application_tracking at
        INNER JOIN student s ON at.stud_id = s.stud_id
        INNER JOIN job_posting jp ON at.job_id = jp.job_id
        INNER JOIN job_type jt ON jp.job_type_id = jt.job_type_id
        LEFT JOIN skill_matching sm ON at.application_id = sm.match_id
        WHERE jp.employer_id = :employer_id
    ";

    $params = [':employer_id' => $employer_id];
    $conditions = [];

    // 🔹 NEW: handle "type=hired"
    if (isset($_GET['type']) && $_GET['type'] === 'hired') {
        $conditions[] = "at.application_status = 'hired'";
    }

    // Existing filters (only apply if not exporting hired, unless you still want them)
    if (!isset($_GET['type']) || $_GET['type'] !== 'hired') {
        if (isset($_GET['status']) && $_GET['status'] !== 'all') {
            $conditions[] = "at.application_status = :status";
            $params[':status'] = $_GET['status'];
        }

        if (isset($_GET['job']) && $_GET['job'] !== 'all') {
            $conditions[] = "jp.job_id = :job_id";
            $params[':job_id'] = $_GET['job'];
        }

        if (isset($_GET['date']) && $_GET['date'] !== 'all') {
            if ($_GET['date'] === 'today') {
                $conditions[] = "DATE(at.applied_at) = CURDATE()";
            } elseif ($_GET['date'] === 'week') {
                $conditions[] = "at.applied_at >= CURDATE() - INTERVAL 7 DAY";
            } elseif ($_GET['date'] === 'month') {
                $conditions[] = "at.applied_at >= CURDATE() - INTERVAL 1 MONTH";
            }
        }

        if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
            $search = '%' . trim($_GET['search']) . '%';
            $conditions[] = "(s.stud_first_name LIKE :search1 
                           OR s.stud_last_name LIKE :search2 
                           OR s.stud_email LIKE :search3)";
            $params[':search1'] = $search;
            $params[':search2'] = $search;
            $params[':search3'] = $search;
        }
    }

    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY at.applied_at DESC";

    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // File name changes if hired
    $filename = (isset($_GET['type']) && $_GET['type'] === 'hired')
        ? 'hired_individuals_export_' . date('Y-m-d_His') . '.csv'
        : 'job_applications_export_' . date('Y-m-d_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // CSV headers
    fputcsv($output, [
        'Application ID',
        'Applicant Name',
        'Email',
        'Institution',
        'Education Background',
        'Graduation Year',
        'Job Title',
        'Job Location',
        'Job Type',
        'Application Status',
        'Match Score (%)',
        'Applied At'
    ]);

    // CSV rows
    foreach ($results as $row) {
        fputcsv($output, [
            cleanData($row['application_id']),
            cleanData($row['stud_first_name'] . ' ' . $row['stud_last_name']),
            cleanData($row['stud_email']),
            cleanData($row['institution'] ?? ''),
            cleanData($row['edu_background'] ?? ''),
            cleanData($row['graduation_yr'] ?? ''),
            cleanData($row['job_title']),
            cleanData($row['job_location'] ?? ''),
            cleanData($row['job_type_title']),
            cleanData($row['application_status']),
            cleanData($row['match_score'] ?? 'N/A'),
            cleanData($row['applied_at'])
        ]);
    }

    fclose($output);
    $stmt->closeCursor();
    exit;

} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Failed to export applications']);
    exit;
}
