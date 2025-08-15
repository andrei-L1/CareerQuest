<?php
require '../config/dbcon.php';

$applicantId = $_GET['applicantId'] ?? null;

if (!$applicantId) {
    http_response_code(400);
    echo json_encode(['error' => 'No applicant ID provided']);
    exit;
}

// Fetch applicant details with skills and interview information
$sql = "
    SELECT 
        s.stud_id,
        s.stud_first_name,
        s.stud_last_name,
        s.stud_gender,
        s.stud_date_of_birth,
        s.bio,
        s.profile_picture,
        s.stud_email,
        s.graduation_yr,
        s.institution,
        s.edu_background,
        s.resume_file,
        a.application_status,
        j.title AS job_title,
        j.job_id,
        c.course_title,
        GROUP_CONCAT(CONCAT(sm.skill_name, ' (', ss.proficiency, ')') SEPARATOR ', ') AS skills,
        i.interview_id,
        i.interview_date,
        i.interview_mode,
        i.location_details,
        i.additional_notes,
        i.status AS interview_status
    FROM application_tracking a
    JOIN student s ON a.stud_id = s.stud_id
    JOIN job_posting j ON a.job_id = j.job_id
    LEFT JOIN course c ON s.course_id = c.course_id
    LEFT JOIN stud_skill ss ON s.stud_id = ss.stud_id
    LEFT JOIN skill_masterlist sm ON ss.skill_id = sm.skill_id
    LEFT JOIN interviews i ON a.application_id = i.application_id
    WHERE a.application_id = ?
    GROUP BY s.stud_id, a.application_id, i.interview_id
";

$stmt = $conn->prepare($sql);
$stmt->execute([$applicantId]);
$applicant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$applicant) {
    http_response_code(404);
    echo json_encode(['error' => 'Applicant not found']);
    exit;
}

// Ensure skills is not null
$applicant['skills'] = $applicant['skills'] ?? 'No skills listed';

// Ensure resume_file is accessible or provide a fallback
$applicant['resume_file'] = $applicant['resume_file'] ?? null;

echo json_encode($applicant);
?>