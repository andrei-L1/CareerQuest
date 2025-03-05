<?php
require "../config/dbcon.php"; 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['actor_id'])) {
    $actor_id = $_SESSION['actor_id'];
} 

$actor_id = $_SESSION['actor_id'];

try {
    // Query to count new users per month
    $query = "SELECT 
                DATE_FORMAT(created_at, '%b') AS month, 
                COUNT(*) AS user_count 
              FROM actor 
              WHERE entity_type IN ('user', 'student')
              AND deleted_at IS NULL
              GROUP BY MONTH(created_at)
              ORDER BY MONTH(created_at)";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $growthData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Extract labels (months) and data (user count)
    $labels = [];
    $data = [];
    foreach ($growthData as $row) {
        $labels[] = $row['month'];
        $data[] = (int) $row['user_count'];
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $labels = [];
    $data = [];
}


try {
    // Query to count job postings per month
    $query = "SELECT 
                DATE_FORMAT(posted_at, '%b') AS month, 
                COUNT(*) AS job_count 
              FROM job_posting 
              WHERE deleted_at IS NULL
              GROUP BY MONTH(posted_at)
              ORDER BY MONTH(posted_at)";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $jobData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Extract labels (months) and data (job count)
    $jobLabels = [];
    $jobCounts = [];
    foreach ($jobData as $row) {
        $jobLabels[] = $row['month'];
        $jobCounts[] = (int) $row['job_count'];
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $jobLabels = [];
    $jobCounts = [];
}



try {
    $query = "
        -- New Job Postings
        SELECT 'New job posted by' AS activity_type, 
               employer.company_name AS entity_name, 
               NULL AS user_type, 
               jp.posted_at AS activity_date
        FROM job_posting jp
        JOIN employer ON jp.employer_id = employer.employer_id
        WHERE jp.deleted_at IS NULL

        UNION

        -- New Users Registered
        SELECT 'User registered' AS activity_type, 
               CONCAT(u.user_first_name, ' ', u.user_last_name) AS entity_name, 
               u.user_type,  
               u.created_at AS activity_date
        FROM user u
        WHERE u.deleted_at IS NULL

        UNION

        -- New Students Registered
        SELECT 'Student registered' AS activity_type, 
               CONCAT(s.stud_first_name, ' ', s.stud_last_name) AS entity_name, 
               NULL AS user_type, 
               s.created_at AS activity_date
        FROM student s
        WHERE s.deleted_at IS NULL

        UNION

        -- Job Applications Submitted
        SELECT 'Application submitted' AS activity_type, 
               CONCAT(s.stud_first_name, ' ', s.stud_last_name) AS entity_name, 
               NULL AS user_type, 
               at.applied_at AS activity_date
        FROM application_tracking at
        JOIN student s ON at.stud_id = s.stud_id
        WHERE at.deleted_at IS NULL

        ORDER BY activity_date DESC
        LIMIT 5";  

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $recentActivities = [];
}

try {
    $query = "
        SELECT message, notification_type, created_at 
        FROM notification 
        WHERE actor_id = :actor_id 
        AND is_read = FALSE 
        AND deleted_at IS NULL 
        ORDER BY created_at DESC 
        LIMIT 5";  

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':actor_id', $actor_id, PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $notifications = [];
}




?>
