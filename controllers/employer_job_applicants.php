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

    // Get job postings with applicants
    $applications = getJobApplications($conn, $employer['employer_id']);
    
    // Organize and output data
    echo json_encode(formatApplicationsData($applications));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}

// ===== Functions ===== //

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
    $stmt = $conn->prepare("
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
            at.application_status,
            at.applied_at,
            ROUND(AVG(sm.match_score), 2) AS avg_match_score,
            GROUP_CONCAT(DISTINCT sl.skill_name) AS skills
        FROM job_posting jp
        LEFT JOIN application_tracking at ON jp.job_id = at.job_id
        LEFT JOIN student s ON at.stud_id = s.stud_id
        LEFT JOIN stud_skill ss ON s.stud_id = ss.stud_id
        LEFT JOIN skill_masterlist sl ON ss.skill_id = sl.skill_id
        LEFT JOIN job_skill js ON jp.job_id = js.job_id
        LEFT JOIN skill_matching sm ON sm.user_skills_id = ss.user_skills_id AND sm.job_skills_id = js.job_skills_id
        WHERE jp.employer_id = :employer_id
        AND jp.moderation_status = 'Approved' 
        AND at.application_status = 'Pending'
        GROUP BY jp.job_id, s.stud_id, jp.title, jp.description, jp.location, 
                 jp.posted_at, jp.moderation_status, s.stud_first_name, 
                 s.stud_last_name, s.stud_email, s.resume_file, 
                 at.application_status, at.applied_at
        ORDER BY jp.job_id, at.applied_at DESC
    ");
    $stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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