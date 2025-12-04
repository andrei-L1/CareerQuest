<?php
session_start();
require '../config/dbcon.php';
/** @var PDO $conn */
require '../auth/auth_check_student.php';

// CSRF token check
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token');
}

$application_id = (int)$_POST['application_id'];

try {
    // Check if application exists and is pending
    $stmt = $conn->prepare("SELECT * FROM application_tracking WHERE application_id = :application_id AND application_status = 'Pending' AND deleted_at IS NULL");
    $stmt->execute([':application_id' => $application_id]);

    if ($stmt->rowCount() === 0) {
        die('Application not found or already processed');
    }

    // Withdraw application
    $stmt = $conn->prepare("UPDATE application_tracking SET application_status = 'Withdrawn', deleted_at = NOW() WHERE application_id = :application_id");
    $stmt->execute([':application_id' => $application_id]);

    // Get job, student, and employer info
    $stmt = $conn->prepare("
        SELECT 
            at.job_id, at.stud_id,
            jp.title AS job_title, jp.employer_id,
            e.user_id AS employer_user_id,
            s.stud_first_name, s.stud_last_name
        FROM application_tracking at
        JOIN job_posting jp ON at.job_id = jp.job_id
        JOIN employer e ON jp.employer_id = e.employer_id
        JOIN student s ON at.stud_id = s.stud_id
        WHERE at.application_id = :application_id
    ");
    $stmt->execute([':application_id' => $application_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get or create actor IDs
    // Student actor
    $stmt = $conn->prepare("SELECT actor_id FROM actor WHERE entity_type = 'student' AND entity_id = :stud_id");
    $stmt->execute([':stud_id' => $data['stud_id']]);
    $student_actor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student_actor) {
        $stmt = $conn->prepare("INSERT INTO actor (entity_type, entity_id) VALUES ('student', :stud_id)");
        $stmt->execute([':stud_id' => $data['stud_id']]);
        $student_actor_id = $conn->lastInsertId();
    } else {
        $student_actor_id = $student_actor['actor_id'];
    }

    // Employer actor
    $stmt = $conn->prepare("SELECT actor_id FROM actor WHERE entity_type = 'user' AND entity_id = :user_id");
    $stmt->execute([':user_id' => $data['employer_user_id']]);
    $employer_actor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employer_actor) {
        $stmt = $conn->prepare("INSERT INTO actor (entity_type, entity_id) VALUES ('user', :user_id)");
        $stmt->execute([':user_id' => $data['employer_user_id']]);
        $employer_actor_id = $conn->lastInsertId();
    } else {
        $employer_actor_id = $employer_actor['actor_id'];
    }

    // Compose messages
    $student_name = $data['stud_first_name'] . ' ' . $data['stud_last_name'];
    $job_title = $data['job_title'];

    // Notify employer (student withdrew application)
    $stmt = $conn->prepare("INSERT INTO notification 
        (actor_id, message, notification_type, action_url, reference_type, reference_id)
        VALUES (:actor_id, :message, 'application_withdrawal', :url, 'student', :reference_actor_id)");
    $stmt->execute([
        ':actor_id' => $employer_actor_id,
        ':message' => "$student_name has withdrawn their application for: $job_title.",
        ':url' => "../dashboard/employer_applications.php?job_id=" . $data['job_id'],
        ':reference_actor_id' => $student_actor_id
    ]);

    // Notify student (confirmation)
    $stmt = $conn->prepare("INSERT INTO notification 
        (actor_id, message, notification_type, action_url, reference_type, reference_id)
        VALUES (:actor_id, :message, 'application_withdrawal', :url, 'user', :reference_actor_id)");
    $stmt->execute([
        ':actor_id' => $student_actor_id,
        ':message' => "You have withdrawn your application for: $job_title.",
        ':url' => "../dashboard/student_applications.php?job_id=" . $data['job_id'],
        ':reference_actor_id' => $employer_actor_id
    ]);

    $_SESSION['message'] = 'Application withdrawn successfully';
    header("Location: ../dashboard/student_applications.php");
    exit;

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
