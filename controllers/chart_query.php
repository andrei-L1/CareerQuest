<?php
require "../config/dbcon.php"; 

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


?>
