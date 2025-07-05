<?php
require '../config/dbcon.php'; 

$applicantId = $_GET['applicantId'] ?? null;

if (!$applicantId) {
    http_response_code(400);
    echo json_encode(['error' => 'No applicant ID provided']);
    exit;
}

// Fetch applicant details
$sql = "
    SELECT 
        s.stud_id,
        s.stud_first_name,
        s.stud_last_name,
        s.bio,
        s.profile_picture,
        s.stud_email,
        s.graduation_yr,
        s.institution,
        s.edu_background,
        s.resume_file,
        a.application_status,
        j.title AS job_title,
        j.job_id
    FROM application_tracking a
    JOIN student s ON a.stud_id = s.stud_id
    JOIN job_posting j ON a.job_id = j.job_id
    WHERE a.application_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->execute([$applicantId]);
$applicant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$applicant) {
    http_response_code(404);
    echo json_encode(['error' => 'Applicant not found']);
    exit;
}

echo json_encode($applicant);
?>
