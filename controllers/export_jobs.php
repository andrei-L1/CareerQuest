<?php
require "../config/dbcon.php";
/** @var PDO $conn */
require "../auth/auth_check.php"; 

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=job_export_data.csv');

$output = fopen('php://output', 'w');

// Function to export table data
function exportTable($output, $conn, $table, $columns, $query, $params = []) {
    fputcsv($output, ["Table: $table"]); // Table Header
    fputcsv($output, $columns); // Column Headers

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as $row) {
        fputcsv($output, array_values($row)); // Ensure all columns are included
    }
    
    fputcsv($output, []); // Empty line for separation
}

// Get status filter from query parameter
$statusFilter = isset($_GET['status']) && $_GET['status'] !== 'all' ? ucfirst(strtolower($_GET['status'])) : null;

// Export Job Posting Table
$jobPostingColumns = [
    'job_id', 'employer_id', 'title', 'description', 'location', 'job_type_id', 'img_url', 
    'posted_at', 'expires_at', 'deleted_at', 'moderation_status', 'flagged', 
    'min_salary', 'max_salary', 'salary_type', 'salary_disclosure'
];
$jobPostingQuery = "
    SELECT job_id, employer_id, title, description, location, job_type_id, img_url, 
           posted_at, expires_at, deleted_at, moderation_status, flagged, 
           min_salary, max_salary, salary_type, salary_disclosure 
    FROM job_posting 
    WHERE deleted_at IS NULL
";
$params = [];
if ($statusFilter && in_array($statusFilter, ['Pending', 'Approved', 'Rejected', 'Paused'])) {
    $jobPostingQuery .= " AND moderation_status = :status";
    $params = [':status' => $statusFilter];
}
exportTable($output, $conn, "Job Posting", $jobPostingColumns, $jobPostingQuery, $params);

// Export Employer Table
exportTable($output, $conn, "Employer", 
    ['employer_id', 'user_id', 'company_name', 'job_title', 'company_logo', 'status', 
     'company_website', 'contact_number', 'company_description', 'created_at', 'deleted_at', 'document_url'],
    "SELECT employer_id, user_id, company_name, job_title, company_logo, status, 
            company_website, contact_number, company_description, created_at, deleted_at, document_url 
     FROM employer"
);

fclose($output);
exit();
?>