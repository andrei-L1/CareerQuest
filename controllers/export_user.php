<?php
require "../config/dbcon.php";
/** @var PDO $conn */
require "../auth/auth_check.php"; 

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=user_list.csv');

$output = fopen('php://output', 'w');

// Column Headers
fputcsv($output, ['Actor ID', 'Entity Type', 'Entity ID', 'First Name', 'Middle Name', 'Last Name', 'Email', 'Status', 'Role']);

// Fetch Users
$sql = "SELECT a.actor_id, a.entity_type, a.entity_id, 
        COALESCE(u.user_first_name, s.stud_first_name) AS first_name, 
        COALESCE(u.user_middle_name, s.stud_middle_name) AS middle_name, 
        COALESCE(u.user_last_name, s.stud_last_name) AS last_name, 
        COALESCE(u.user_email, s.stud_email) AS email, 
        COALESCE(u.status, s.status, 'Active') AS status, 
        CASE 
            WHEN a.entity_type = 'user' THEN COALESCE(r.role_title, 'User')
            WHEN a.entity_type = 'student' AND s.is_student = 1 THEN 'Student'
            ELSE 'Professional'
        END AS role_name 
    FROM actor a
    LEFT JOIN user u ON a.entity_type = 'user' AND a.entity_id = u.user_id
    LEFT JOIN role r ON u.role_id = r.role_id
    LEFT JOIN student s ON a.entity_type = 'student' AND a.entity_id = s.stud_id
    WHERE a.deleted_at IS NULL";

$stmt = $conn->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add each row to CSV
foreach ($users as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit();
?>