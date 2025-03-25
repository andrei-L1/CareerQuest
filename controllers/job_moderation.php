<?php
header('Content-Type: application/json');
require "../config/dbcon.php";
require "../auth/auth_check.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent any unwanted output
ob_clean();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Unauthorized access"]);
    exit();
}

$user_id = $_SESSION['user_id'];

// 🟢 Handle Job Posting (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_title'])) {
    try {
        $conn->beginTransaction();

        // Sanitize input
        $employer_id = $_POST['employer_id'] ?? null;
        $job_type_id = $_POST['job_type_id'] ?? null;
        $location = $_POST['location'] ?? null;
        $salary = $_POST['salary'] ?? null;
        $description = $_POST['description'] ?? null;
        $expires_at = $_POST['expires_at'] ?? null;
        $moderation_status = "Pending"; // Default status
        $img_url = null;
        $job_title = $_POST['job_title'] ?? null;

        // Handle file upload (validate type and size)
        if (!empty($_FILES['img_url']['name'])) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['img_url']['type'], $allowed_types)) {
                echo json_encode(["error" => "Invalid image format"]);
                exit();
            }
            if ($_FILES['img_url']['size'] > 2 * 1024 * 1024) { // 2MB limit
                echo json_encode(["error" => "File size too large"]);
                exit();
            }
            $target_dir = "uploads/";
            $img_url = basename($_FILES["img_url"]["name"]);
            move_uploaded_file($_FILES["img_url"]["tmp_name"], $target_dir . $img_url);
        }

        // Insert job posting
        $stmt = $conn->prepare("
            INSERT INTO job_posting (employer_id, title, job_type_id, description, location, salary, img_url, expires_at, moderation_status)
            VALUES (:employer_id, :title, :job_type_id, :description, :location, :salary, :img_url, :expires_at, :moderation_status)
        ");
        $stmt->execute([
            ':employer_id' => $employer_id,
            ':title' => $job_title,
            ':job_type_id' => $job_type_id,
            ':description' => $description,
            ':location' => $location,
            ':salary' => $salary,
            ':img_url' => $img_url,
            ':expires_at' => $expires_at,
            ':moderation_status' => $moderation_status
        ]);
        $job_id = $conn->lastInsertId();

        // Insert job skills
        if (!empty($_POST['skills'])) {
            $stmtSkill = $conn->prepare("
                INSERT INTO job_skill (job_id, skill_id, importance, group_no) 
                VALUES (:job_id, :skill_id, :importance, :group_no)
            ");

            foreach ($_POST['skills'] as $index => $skill_id) {
                $importance = $_POST['importance'][$index] ?? 'Medium';
                $group_no = $_POST['group_no'][$index] ?? 1;

                $stmtSkill->execute([
                    ':job_id' => $job_id,
                    ':skill_id' => $skill_id,
                    ':importance' => $importance,
                    ':group_no' => $group_no
                ]);
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Job added successfully!']);
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}

// 🟢 Fetch Employers
if (isset($_GET['type']) && $_GET['type'] === 'employers') {
    $stmt = $conn->query("
        SELECT e.employer_id, e.company_name, u.user_first_name, u.user_last_name
        FROM employer e
        JOIN user u ON e.user_id = u.user_id
    ");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}

// 🟢 Fetch Job Types
if (isset($_GET['type']) && $_GET['type'] === 'job_types') {
    $stmt = $conn->query("SELECT job_type_id, job_type_title FROM job_type");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC) ?: ["error" => "No job types found"]);
    exit();
}

// 🟢 Fetch Skills
if (isset($_GET['type']) && $_GET['type'] === 'skills') {
    $stmt = $conn->query("SELECT skill_id, skill_name FROM skill_masterlist");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}



// ✅ Fetch a Single Job if job_id is Provided
if (isset($_GET['job_id']) && is_numeric($_GET['job_id'])) {
    $job_id = intval($_GET['job_id']); // Convert to integer for safety
    error_log("Fetching job details for job_id: $job_id");

    try {
        $stmt = $conn->prepare("
            SELECT job_id, title, description, location, moderation_status, posted_at 
            FROM job_posting 
            WHERE job_id = :job_id 
            AND deleted_at IS NULL 
            LIMIT 1
        ");
        $stmt->execute([':job_id' => $job_id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            echo json_encode(["error" => "Job not found"]);
        } else {
            // Rename field to match frontend expectations
            $job['posted_date'] = $job['posted_at'];
            unset($job['posted_at']);

            echo json_encode($job);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(["error" => "An unexpected error occurred."]);
    }
    exit(); // ✅ Exit here to prevent fetching all jobs
}


// 🟢 Fetch Jobs for Moderation
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("
        SELECT job_id, title, description, location, moderation_status
        FROM job_posting
        WHERE deleted_at IS NULL
    ");
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}

// 🟢 Handle Job Moderation (Approve, Reject, Flag)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'], $_POST['action'])) {
    $job_id = $_POST['job_id'];
    $action = $_POST['action'];

    $validActions = [
        'approve' => 'Approved',
        'reject' => 'Rejected',
        'flag' => 'Pending'
    ];

    if (!array_key_exists($action, $validActions)) {
        echo json_encode(["error" => "Invalid action"]);
        exit();
    }

    try {
        $conn->beginTransaction();
        
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
    } catch (PDOException $e) {
        $conn->rollBack();
        echo json_encode(["error" => $e->getMessage()]);
        exit();
    }
}


echo json_encode(["error" => "Invalid request"]);
exit();

?>
