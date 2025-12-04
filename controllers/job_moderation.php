<?php
header('Content-Type: application/json');
require "../config/dbcon.php";
/** @var PDO $conn */
require "../auth/auth_check.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_clean();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Unauthorized access"]);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_title'])) {
    try {
        $conn->beginTransaction();

        $employer_id = intval($_POST['employer_id'] ?? 0);
        $job_type_id = intval($_POST['job_type_id'] ?? 0);
        $location = trim($_POST['location'] ?? '');
        $min_salary = isset($_POST['min_salary']) && $_POST['min_salary'] !== '' ? floatval($_POST['min_salary']) : null;
        $max_salary = isset($_POST['max_salary']) && $_POST['max_salary'] !== '' ? floatval($_POST['max_salary']) : null;
        $salary_type = trim($_POST['salary_type'] ?? '');
        $salary_disclosure = filter_var($_POST['salary_disclosure'] ?? 0, FILTER_VALIDATE_BOOLEAN);
        $description = trim($_POST['description'] ?? '');
        $expires_at = $_POST['expires_at'] ?? null;
        $job_title = trim($_POST['job_title'] ?? '');
        $moderation_status = "Pending";
        $img_url = null;

        if (!$employer_id || !$job_type_id || !$job_title || !$description || !$location || !$salary_type) {
            echo json_encode(["error" => "Missing required fields"]);
            exit();
        }

        if (!in_array($salary_type, ['Hourly', 'Weekly', 'Monthly', 'Yearly', 'Commission', 'Negotiable'])) {
            echo json_encode(["error" => "Invalid salary type"]);
            exit();
        }

        if (!empty($_FILES['img_url']['name'])) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['img_url']['type'];
            $file_size = $_FILES['img_url']['size'];
            
            if (!in_array($file_type, $allowed_types) || $file_size > 2 * 1024 * 1024) {
                echo json_encode(["error" => "Invalid file type or size too large"]);
                exit();
            }
            
            $target_dir = "../Uploads/";
            $img_url = time() . "_" . basename($_FILES["img_url"]["name"]);
            move_uploaded_file($_FILES["img_url"]["tmp_name"], $target_dir . $img_url);
        }

        $stmt = $conn->prepare("
            INSERT INTO job_posting (employer_id, title, job_type_id, description, location, min_salary, max_salary, salary_type, salary_disclosure, img_url, expires_at, moderation_status)
            VALUES (:employer_id, :title, :job_type_id, :description, :location, :min_salary, :max_salary, :salary_type, :salary_disclosure, :img_url, :expires_at, :moderation_status)
        ");
        $stmt->execute([
            ':employer_id' => $employer_id,
            ':title' => $job_title,
            ':job_type_id' => $job_type_id,
            ':description' => $description,
            ':location' => $location,
            ':min_salary' => $min_salary,
            ':max_salary' => $max_salary,
            ':salary_type' => $salary_type,
            ':salary_disclosure' => $salary_disclosure,
            ':img_url' => $img_url,
            ':expires_at' => $expires_at,
            ':moderation_status' => $moderation_status
        ]);
        $job_id = $conn->lastInsertId();

        if (!empty($_POST['skills']) && is_array($_POST['skills'])) {
            $stmtSkill = $conn->prepare("
                INSERT INTO job_skill (job_id, skill_id, importance, group_no) 
                VALUES (:job_id, :skill_id, :importance, :group_no)
            ");

            foreach ($_POST['skills'] as $index => $skillData) {
                $skill_id = intval($skillData['skill']);
                $importance = $skillData['importance'] ?? 'Medium';
                
                $checkSkill = $conn->prepare("SELECT skill_id FROM skill_masterlist WHERE skill_id = ?");
                $checkSkill->execute([$skill_id]);
                
                if (!$checkSkill->fetch()) {
                    $conn->rollBack();
                    echo json_encode(['error' => "Invalid skill ID: $skill_id"]);
                    exit();
                }

                $stmtSkill->execute([
                    ':job_id' => $job_id,
                    ':skill_id' => $skill_id,
                    ':importance' => $importance,
                    ':group_no' => $job_id 
                ]);
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Job added successfully!']);
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error adding job: " . $e->getMessage());
        echo json_encode(['error' => 'An unexpected error occurred']);
        exit();
    }
}

if (isset($_GET['type']) && $_GET['type'] === 'employers') {
    $stmt = $conn->query("
        SELECT e.employer_id, 
            COALESCE(e.company_name, CONCAT(u.user_first_name, ' ', u.user_last_name)) AS company_name, 
            u.user_first_name, 
            u.user_last_name
        FROM employer e
        JOIN user u ON e.user_id = u.user_id
    ");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}

if (isset($_GET['type']) && $_GET['type'] === 'job_types') {
    $stmt = $conn->query("SELECT job_type_id, job_type_title FROM job_type");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: ["error" => "No job types found"]);
    exit();
}

if (isset($_GET['type']) && $_GET['type'] === 'skills') {
    $stmt = $conn->query("SELECT skill_id, skill_name FROM skill_masterlist");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}

if (isset($_GET['job_id']) && is_numeric($_GET['job_id'])) {
    $job_id = intval($_GET['job_id']); 

    try {
        $stmt = $conn->prepare("
            SELECT 
                jp.job_id, 
                jp.title, 
                jp.description, 
                jp.location, 
                jp.moderation_status, 
                jp.posted_at, 
                jp.expires_at, 
                jp.min_salary, 
                jp.max_salary, 
                jp.salary_type, 
                jp.salary_disclosure,
                jt.job_type_title,
                e.company_name,
                e.company_logo
            FROM job_posting jp
            LEFT JOIN employer e ON jp.employer_id = e.employer_id
            LEFT JOIN job_type jt ON jp.job_type_id = jt.job_type_id
            WHERE jp.job_id = :job_id 
            AND jp.deleted_at IS NULL 
            LIMIT 1
        ");
        $stmt->execute([':job_id' => $job_id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            echo json_encode(["error" => "Job not found"]);
            exit();
        }

        $stmtSkills = $conn->prepare("
            SELECT sm.skill_name, js.importance 
            FROM job_skill js
            JOIN skill_masterlist sm ON js.skill_id = sm.skill_id
            WHERE js.job_id = :job_id
        ");
        $stmtSkills->execute([':job_id' => $job_id]);
        $job['skills'] = $stmtSkills->fetchAll(PDO::FETCH_ASSOC);

        $job['posted_date'] = $job['posted_at'];
        unset($job['posted_at']);

        echo json_encode($job);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(["error" => "An unexpected error occurred."]);
    }
    exit(); 
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['job_id'])) {
    try {
        $statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
        
        $query = "
            SELECT 
                jp.job_id, 
                jp.title, 
                jp.description, 
                jp.location, 
                jp.moderation_status, 
                jp.posted_at, 
                jp.expires_at, 
                jp.flagged, 
                jp.min_salary, 
                jp.max_salary, 
                jp.salary_type, 
                jp.salary_disclosure, 
                jp.img_url,
                jt.job_type_title,
                e.company_name,
                e.company_logo,
                e.job_title AS employer_job_title,
                e.status AS employer_status
            FROM job_posting jp
            LEFT JOIN employer e ON jp.employer_id = e.employer_id
            LEFT JOIN job_type jt ON jp.job_type_id = jt.job_type_id
            WHERE jp.deleted_at IS NULL
        ";

        if ($statusFilter !== 'all') {
            $query .= " AND jp.moderation_status = :status";
        }

        $stmt = $conn->prepare($query);
        
        if ($statusFilter !== 'all') {
            $stmt->bindValue(':status', ucfirst(strtolower($statusFilter)));
        }

        $stmt->execute();
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch skills for each job
        foreach ($jobs as &$job) {
            $stmtSkills = $conn->prepare("
                SELECT sm.skill_name, js.importance 
                FROM job_skill js
                JOIN skill_masterlist sm ON js.skill_id = sm.skill_id
                WHERE js.job_id = :job_id
            ");
            $stmtSkills->execute([':job_id' => $job['job_id']]);
            $job['skills'] = $stmtSkills->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode($jobs);
        exit();
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(["error" => "An unexpected error occurred."]);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'], $_POST['action'])) {
    $job_id = intval($_POST['job_id']);
    $action = $_POST['action'];

    $validActions = [
        'approve' => 'Approved',
        'reject' => 'Rejected',
        'flag' => 'Pending',
        'delete' => 'Deleted'
    ];

    if (!array_key_exists($action, $validActions)) {
        echo json_encode(["error" => "Invalid action"]);
        exit();
    }

    try {
        $conn->beginTransaction();

        if ($action === 'delete') {
            // Soft delete the job by setting deleted_at timestamp
            $stmt = $conn->prepare("
                UPDATE job_posting
                SET deleted_at = NOW()
                WHERE job_id = :job_id
            ");
            $stmt->execute([':job_id' => $job_id]);

            $conn->commit();
            echo json_encode(["message" => "Job deleted successfully"]);
            exit();
        } else {
            // Handle existing moderation actions (approve, reject, flag)
            $stmt = $conn->prepare("
                UPDATE job_posting
                SET moderation_status = :status, flagged = :flagged
                WHERE job_id = :job_id
            ");
            $stmt->execute([
                ':status' => $validActions[$action],
                ':flagged' => ($action === 'flag' ? 1 : 0),
                ':job_id' => $job_id
            ]);

            $conn->commit();
            echo json_encode(["message" => "Job status updated successfully"]);
            exit();
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(["error" => $e->getMessage()]);
        exit();
    }
}

echo json_encode(["error" => "Invalid request"]);
exit();
?>