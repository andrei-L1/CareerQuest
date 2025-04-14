<?php
require '../config/dbcon.php';
require '../auth/auth_check_student.php';

header('Content-Type: application/json');

// Validate input
$input = json_decode(file_get_contents("php://input"), true);
$job_id = filter_var($input['job_id'] ?? null, FILTER_VALIDATE_INT);

if (!$job_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid job ID']);
    exit;
}

try {
    // Check if already applied
    $stmt = $conn->prepare("SELECT * FROM application_tracking 
                           WHERE stud_id = :stud_id AND job_id = :job_id AND deleted_at IS NULL");
    $stmt->execute([':stud_id' => $_SESSION['stud_id'], ':job_id' => $job_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'You have already applied to this job']);
        exit;
    }

    // Create application
    $stmt = $conn->prepare("INSERT INTO application_tracking 
                           (stud_id, job_id, application_status, applied_at)
                           VALUES (:stud_id, :job_id, 'Pending', NOW())");
    $stmt->execute([':stud_id' => $_SESSION['stud_id'], ':job_id' => $job_id]);
    
    // Get employer user_id for notification
    $stmt = $conn->prepare("SELECT u.user_id FROM employer e
                           JOIN user u ON e.user_id = u.user_id
                           JOIN job_posting j ON j.employer_id = e.employer_id
                           WHERE j.job_id = :job_id");
    $stmt->execute([':job_id' => $job_id]);
    $employer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($employer) {
        // Check if actor already exists for employer
        $stmt = $conn->prepare("SELECT actor_id FROM actor WHERE entity_type = 'user' AND entity_id = :user_id");
        $stmt->execute([':user_id' => $employer['user_id']]);
        $actor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$actor) {
            // If actor doesn't exist, insert a new actor for employer
            $stmt = $conn->prepare("INSERT INTO actor (entity_type, entity_id) VALUES ('user', :user_id)");
            $stmt->execute([':user_id' => $employer['user_id']]);
            $actor_id = $conn->lastInsertId();
        } else {
            $actor_id = $actor['actor_id'];
        }
        
        // Get the job title
        $stmt = $conn->prepare("SELECT title FROM job_posting WHERE job_id = :job_id");
        $stmt->execute([':job_id' => $job_id]);
        $job_title = $stmt->fetchColumn();

        // Create notification for employer (student applied to their job)
        $stmt = $conn->prepare("INSERT INTO notification 
                            (actor_id, message, notification_type, action_url, reference_type, reference_id)
                            VALUES (:actor_id, :message, 'application', :url, 'user', :user_id)");
        $stmt->execute([
            ':actor_id' => $actor_id, // Employer receives the notification
            ':message' => "New application for: $job_title",
            ':url' => "/skillmatch/dashboard/employer_applications.php?job_id=$job_id",
            ':user_id' => $employer['user_id'] // Employer receives notification
        ]);

        // Get or create actor for the student (applicant)
        $stmt = $conn->prepare("SELECT actor_id FROM actor WHERE entity_type = 'student' AND entity_id = :stud_id");
        $stmt->execute([':stud_id' => $_SESSION['stud_id']]);
        $student_actor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student_actor) {
            $stmt = $conn->prepare("INSERT INTO actor (entity_type, entity_id) VALUES ('student', :stud_id)");
            $stmt->execute([':stud_id' => $_SESSION['stud_id']]);
            $student_actor_id = $conn->lastInsertId();
        } else {
            $student_actor_id = $student_actor['actor_id'];
        }

        // Create confirmation notification for student (confirming application)
        $stmt = $conn->prepare("INSERT INTO notification 
                            (actor_id, message, notification_type, action_url, reference_type, reference_id)
                            VALUES (:actor_id, :message, 'application', :url, 'student', :stud_id)");
        $stmt->execute([
            ':actor_id' => $student_actor_id, // Student performed the action
            ':message' => "You applied for: $job_title",
            ':url' => "/skillmatch/dashboard/student_applications.php?job_id=$job_id",
            ':stud_id' => $_SESSION['stud_id'] // Student receives notification
        ]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
