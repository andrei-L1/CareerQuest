<?php
require '../config/dbcon.php';
/** @var PDO $conn */
$applicantId = $_GET['applicantId'] ?? null;

if (!$applicantId) {
    http_response_code(400);
    echo json_encode(['error' => 'No applicant ID provided']);
    exit;
}

try {
    // Get basic student details directly (not dependent on application)
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
            c.course_title
        FROM student s
        LEFT JOIN course c ON s.course_id = c.course_id
        WHERE s.stud_id = ? AND s.deleted_at IS NULL
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$applicantId]);
    $applicant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$applicant) {
        http_response_code(404);
        echo json_encode(['error' => 'Student not found']);
        exit;
    }

    // Get skills
    $skills_sql = "
        SELECT sm.skill_name, ss.proficiency
        FROM stud_skill ss
        JOIN skill_masterlist sm ON ss.skill_id = sm.skill_id
        WHERE ss.stud_id = ? AND ss.deleted_at IS NULL
        ORDER BY sm.skill_name
    ";
    
    $skills_stmt = $conn->prepare($skills_sql);
    $skills_stmt->execute([$applicant['stud_id']]);
    $skills_result = $skills_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format skills
    $formatted_skills = [];
    foreach ($skills_result as $skill) {
        $formatted_skills[] = $skill['skill_name'] . ' (' . $skill['proficiency'] . ')';
    }
    $applicant['skills'] = !empty($formatted_skills) ? implode(', ', $formatted_skills) : 'No skills listed';

    // Get application information if exists
    $application_sql = "
        SELECT 
            a.application_id,
            a.application_status,
            j.title AS job_title,
            j.job_id
        FROM application_tracking a
        JOIN job_posting j ON a.job_id = j.job_id
        WHERE a.stud_id = ? AND a.deleted_at IS NULL
        ORDER BY a.applied_at DESC
        LIMIT 1
    ";
    
    $application_stmt = $conn->prepare($application_sql);
    $application_stmt->execute([$applicantId]);
    $application = $application_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($application) {
        $applicant['application_id'] = $application['application_id'];
        $applicant['application_status'] = $application['application_status'];
        $applicant['job_title'] = $application['job_title'];
        $applicant['job_id'] = $application['job_id'];
        
        // Get interview information if exists
        $interview_sql = "
            SELECT 
                interview_id,
                interview_date,
                interview_mode,
                location_details,
                additional_notes,
                status AS interview_status
            FROM interviews
            WHERE application_id = ?
        ";
        
        $interview_stmt = $conn->prepare($interview_sql);
        $interview_stmt->execute([$application['application_id']]);
        $interview = $interview_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($interview) {
            $applicant['interview_id'] = $interview['interview_id'];
            $applicant['interview_date'] = $interview['interview_date'];
            $applicant['interview_mode'] = $interview['interview_mode'];
            $applicant['location_details'] = $interview['location_details'];
            $applicant['additional_notes'] = $interview['additional_notes'];
            $applicant['interview_status'] = $interview['interview_status'];
        } else {
            $applicant['interview_id'] = null;
            $applicant['interview_date'] = null;
            $applicant['interview_mode'] = null;
            $applicant['location_details'] = null;
            $applicant['additional_notes'] = null;
            $applicant['interview_status'] = null;
        }
    } else {
        // No application found, set defaults
        $applicant['application_id'] = null;
        $applicant['application_status'] = 'No Application';
        $applicant['job_title'] = 'No Job Applied';
        $applicant['job_id'] = null;
        $applicant['interview_id'] = null;
        $applicant['interview_date'] = null;
        $applicant['interview_mode'] = null;
        $applicant['location_details'] = null;
        $applicant['additional_notes'] = null;
        $applicant['interview_status'] = null;
    }

    // Ensure resume_file is accessible or provide a fallback
    $applicant['resume_file'] = $applicant['resume_file'] ?? null;

    echo json_encode($applicant);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>