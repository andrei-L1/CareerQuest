<?php
require "../config/dbcon.php";
require "../auth/auth_check.php"; 

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=export_data.csv');

$output = fopen('php://output', 'w');

// Function to export table data
function exportTable($output, $conn, $table, $columns, $query) {
    fputcsv($output, ["Table: $table"]); // Table Header
    fputcsv($output, $columns); // Column Headers

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fputcsv($output, []); // Empty line for separation
}

// Export Role Table
exportTable($output, $conn, "Role", 
    ['role_id', 'role_title', 'role_description', 'created_at', 'updated_at', 'deleted_at'],
    "SELECT role_id, role_title, role_description, created_at, updated_at, deleted_at FROM role"
);

// Export Course Table
exportTable($output, $conn, "Course", 
    ['course_id', 'course_title', 'course_description', 'deleted_at', 'created_at'],
    "SELECT course_id, course_title, course_description, deleted_at, created_at FROM course"
);

// Export Skill Masterlist
exportTable($output, $conn, "Skill Masterlist", 
    ['skill_id', 'skill_name', 'category', 'created_at', 'updated_at', 'deleted_at'],
    "SELECT skill_id, skill_name, category, created_at, updated_at, deleted_at FROM skill_masterlist"
);

// Export Job Type
exportTable($output, $conn, "Job Type", 
    ['job_type_id', 'job_type_title', 'job_type_description'],
    "SELECT job_type_id, job_type_title, job_type_description FROM job_type"
);

fclose($output);
exit();
?>
