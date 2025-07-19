<?php
require_once '../config/dbcon.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_GET['id'])) {
    header("Location: ../dashboard/employer_jobs.php");
    exit;
}

$job_id = (int)$_GET['id'];
$employer_id = $_SESSION['employer_id'];

try {
    // Get the original job data
    $job_query = "SELECT title, description, location, job_type_id, min_salary, max_salary, salary_type, salary_disclosure, img_url 
                  FROM job_posting 
                  WHERE job_id = :job_id AND employer_id = :employer_id AND deleted_at IS NULL";
    $job_stmt = $conn->prepare($job_query);
    $job_stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
    $job_stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
    $job_stmt->execute();

    if ($job_stmt->rowCount() == 0) {
        $_SESSION['error'] = "Job not found or you don't have permission to duplicate it.";
        header("Location: ../dashboard/employer_jobs.php");
        exit;
    }

    $original_job = $job_stmt->fetch(PDO::FETCH_ASSOC);

    // Insert the duplicated job
    $duplicate_query = "INSERT INTO job_posting 
                        (employer_id, title, description, location, job_type_id, min_salary, max_salary, salary_type, salary_disclosure, img_url, posted_at, moderation_status)
                        VALUES (:employer_id, :title, :description, :location, :job_type_id, :min_salary, :max_salary, :salary_type, :salary_disclosure, :img_url, NOW(), 'Pending')";
    $duplicate_stmt = $conn->prepare($duplicate_query);
    
    $new_title = $original_job['title'] . ' (Copy)';
    
    $duplicate_stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
    $duplicate_stmt->bindParam(':title', $new_title);
    $duplicate_stmt->bindParam(':description', $original_job['description']);
    $duplicate_stmt->bindParam(':location', $original_job['location']);
    $duplicate_stmt->bindParam(':job_type_id', $original_job['job_type_id'], PDO::PARAM_INT);
    $duplicate_stmt->bindParam(':min_salary', $original_job['min_salary'], PDO::PARAM_STR);
    $duplicate_stmt->bindParam(':max_salary', $original_job['max_salary'], PDO::PARAM_STR);
    $duplicate_stmt->bindParam(':salary_type', $original_job['salary_type']);
    $duplicate_stmt->bindParam(':salary_disclosure', $original_job['salary_disclosure'], PDO::PARAM_BOOL);
    $duplicate_stmt->bindParam(':img_url', $original_job['img_url']);
    
    $conn->beginTransaction();
    
    if ($duplicate_stmt->execute()) {
        $new_job_id = $conn->lastInsertId();
        
        // Duplicate skills
        $skills_query = "INSERT INTO job_skill (job_id, skill_id, importance, group_no)
                         SELECT :new_job_id, skill_id, importance, group_no
                         FROM job_skill
                         WHERE job_id = :original_job_id AND deleted_at IS NULL";
        $skills_stmt = $conn->prepare($skills_query);
        $skills_stmt->bindParam(':new_job_id', $new_job_id, PDO::PARAM_INT);
        $skills_stmt->bindParam(':original_job_id', $job_id, PDO::PARAM_INT);
        
        if ($skills_stmt->execute()) {
            $conn->commit();
            $_SESSION['success'] = "Job duplicated successfully. The new job is pending approval.";
        } else {
            $conn->rollBack();
            $_SESSION['error'] = "Error duplicating job skills.";
        }
    } else {
        $conn->rollBack();
        $_SESSION['error'] = "Error duplicating job posting.";
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Error duplicating job: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while duplicating the job posting.";
}

header("Location: ../dashboard/employer_jobs.php");
exit;
?>