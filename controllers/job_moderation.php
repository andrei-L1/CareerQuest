<?php 
header('Content-Type: application/json');

require "../config/dbcon.php";
require "../auth/auth_check.php"; 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
$user_id = $_SESSION['user_id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $img_url = $_FILES['img_url']['name'] ?? null;
        
        // Handle file upload
        if ($img_url) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES["img_url"]["name"]);
            move_uploaded_file($_FILES["img_url"]["tmp_name"], $target_file);
        }

        // Insert job posting
        $stmt = $conn->prepare("
            INSERT INTO job_posting (employer_id, job_type_id, description, location, salary, img_url, expires_at, moderation_status)
            VALUES (:employer_id, :job_type_id, :description, :location, :salary, :img_url, :expires_at, :moderation_status)
        ");
        $stmt->execute([
            ':employer_id' => $employer_id,
            ':job_type_id' => $job_type_id,
            ':description' => $description,
            ':location' => $location,
            ':salary' => $salary,
            ':img_url' => $img_url,
            ':expires_at' => $expires_at,
            ':moderation_status' => $moderation_status
        ]);

        $job_id = $conn->lastInsertId(); // Get the last inserted job ID

        // Insert job skills
        if (!empty($_POST['skills'])) {
            $stmtSkill = $conn->prepare("
                INSERT INTO job_skill (job_id, skill_id, importance, group_no) 
                VALUES (:job_id, :skill_id, :importance, :group_no)
            ");

            foreach ($_POST['skills'] as $index => $skill_id) {
                $importance = $_POST['importance'][$index] ?? 'Medium'; // Default Medium
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
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}





if (isset($_GET['type']) && $_GET['type'] === 'employers') {
    $stmt = $conn->query("
        SELECT e.employer_id, e.company_name, u.user_first_name, u.user_last_name
        FROM employer e
        JOIN user u ON e.user_id = u.user_id
    ");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}



if (isset($_GET['type']) && $_GET['type'] === 'job_types') {
    $stmt = $conn->query("SELECT job_type_id, job_type_title FROM job_type");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$result) {
        echo json_encode(["error" => "No job types found"]);
    } else {
        echo json_encode($result);
    }
    exit;
}

try {
    $stmt = $conn->query("SELECT skill_id, skill_name FROM skill_masterlist");
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($skills);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

?>