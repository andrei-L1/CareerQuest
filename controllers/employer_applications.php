<?php

require '../config/dbcon.php';
/** @var PDO $conn */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the employer is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get employer data
    $employer_stmt = $conn->prepare("SELECT employer_id, company_name, company_logo FROM employer WHERE user_id = :user_id AND deleted_at IS NULL");
    $employer_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $employer_stmt->execute();
    $employer_row = $employer_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employer_row) {
        $_SESSION['error'] = "Employer account not found or has been deleted.";
        header("Location: ../unauthorized.php");
        exit();
    }

    $employer_id = $employer_row['employer_id'];

    // Check if this is a request for hired individuals
    if (isset($_GET['type']) && $_GET['type'] === 'hired') {
        try {
            // Get hired individuals with detailed information (fixed for actual DB structure)
            $hired_stmt = $conn->prepare("
                SELECT 
                    at.application_id,
                    at.stud_id,
                    at.job_id,
                    at.application_status,
                    at.applied_at,
                    at.applied_at as hire_date,
                    jp.title as job_title,
                    jp.location,
                    jt.job_type_title as job_type,
                    s.stud_first_name,
                    s.stud_last_name,
                    s.stud_email as email,
                    s.profile_picture,
                    s.resume_file,
                    s.edu_background,
                    s.institution,
                    c.course_title,
                    s.graduation_yr,
                    s.bio
                FROM application_tracking at
                JOIN job_posting jp ON at.job_id = jp.job_id
                LEFT JOIN job_type jt ON jp.job_type_id = jt.job_type_id
                JOIN student s ON at.stud_id = s.stud_id
                LEFT JOIN course c ON s.course_id = c.course_id
                WHERE jp.employer_id = :employer_id 
                AND at.application_status = 'Accepted' 
                AND at.deleted_at IS NULL
                ORDER BY at.applied_at DESC
            ");
            $hired_stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
            $hired_stmt->execute();
            $hired_individuals = $hired_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Process skills for each individual (using correct table names)
            foreach ($hired_individuals as &$individual) {
                $individual['name'] = $individual['stud_first_name'] . ' ' . $individual['stud_last_name'];
                
                // Get skills for this individual using correct table names
                $skills_stmt = $conn->prepare("
                    SELECT sm.skill_name
                    FROM stud_skill ss
                    JOIN skill_masterlist sm ON ss.skill_id = sm.skill_id
                    WHERE ss.stud_id = :stud_id
                    AND ss.deleted_at IS NULL
                    ORDER BY sm.skill_name
                ");
                $skills_stmt->bindParam(':stud_id', $individual['stud_id'], PDO::PARAM_INT);
                $skills_stmt->execute();
                $skills_result = $skills_stmt->fetchAll(PDO::FETCH_ASSOC);
                $individual['skills'] = array_column($skills_result, 'skill_name');
            }

            // Calculate hired statistics using applied_at since there's no updated_at
            $current_month = date('Y-m');
            $current_quarter = ceil(date('n') / 3);
            $current_year = date('Y');

            // Total hired
            $total_hired = count($hired_individuals);

            // Monthly hired
            $monthly_stmt = $conn->prepare("
                SELECT COUNT(*) as count
                FROM application_tracking at
                JOIN job_posting jp ON at.job_id = jp.job_id
                WHERE jp.employer_id = :employer_id 
                AND at.application_status = 'Accepted' 
                AND DATE_FORMAT(at.applied_at, '%Y-%m') = :current_month
                AND at.deleted_at IS NULL
            ");
            $monthly_stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
            $monthly_stmt->bindParam(':current_month', $current_month, PDO::PARAM_STR);
            $monthly_stmt->execute();
            $monthly_hired = (int)$monthly_stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Quarterly hired
            $quarterly_stmt = $conn->prepare("
                SELECT COUNT(*) as count
                FROM application_tracking at
                JOIN job_posting jp ON at.job_id = jp.job_id
                WHERE jp.employer_id = :employer_id 
                AND at.application_status = 'Accepted' 
                AND QUARTER(at.applied_at) = :current_quarter
                AND YEAR(at.applied_at) = :current_year
                AND at.deleted_at IS NULL
            ");
            $quarterly_stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
            $quarterly_stmt->bindParam(':current_quarter', $current_quarter, PDO::PARAM_INT);
            $quarterly_stmt->bindParam(':current_year', $current_year, PDO::PARAM_INT);
            $quarterly_stmt->execute();
            $quarterly_hired = (int)$quarterly_stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Yearly hired
            $yearly_stmt = $conn->prepare("
                SELECT COUNT(*) as count
                FROM application_tracking at
                JOIN job_posting jp ON at.job_id = jp.job_id
                WHERE jp.employer_id = :employer_id 
                AND at.application_status = 'Accepted' 
                AND YEAR(at.applied_at) = :current_year
                AND at.deleted_at IS NULL
            ");
            $yearly_stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
            $yearly_stmt->bindParam(':current_year', $current_year, PDO::PARAM_INT);
            $yearly_stmt->execute();
            $yearly_hired = (int)$yearly_stmt->fetch(PDO::FETCH_ASSOC)['count'];

            $stats = [
                'total' => $total_hired,
                'monthly' => $monthly_hired,
                'quarterly' => $quarterly_hired,
                'yearly' => $yearly_hired
            ];

            echo json_encode([
                'hired_individuals' => $hired_individuals,
                'stats' => $stats
            ]);
            exit();
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => "Error fetching hired individuals: " . $e->getMessage()]);
            exit();
        }
    }

    // Application counts by status
    $statuses = ['Pending', 'Accepted', 'Interview', 'Offered', 'Rejected'];
    $results = [];

    // Total applications
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total 
        FROM application_tracking at
        JOIN job_posting jp ON at.job_id = jp.job_id
        WHERE jp.employer_id = :employer_id AND at.deleted_at IS NULL
    ");
    $stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
    $stmt->execute();
    $results['total_applications'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Count by individual statuses
    foreach ($statuses as $status) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total 
            FROM application_tracking at
            JOIN job_posting jp ON at.job_id = jp.job_id
            WHERE jp.employer_id = :employer_id AND at.application_status = :status AND at.deleted_at IS NULL
        ");
        $stmt->bindParam(':employer_id', $employer_id, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->execute();
        $results[strtolower($status)] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    echo json_encode($results);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => "Error fetching application data: " . $e->getMessage()]);
}
?>