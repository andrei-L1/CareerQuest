<?php
require_once '../config/dbcon.php'; 
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $conn->query("
            SELECT 
                e.employer_id, 
                COALESCE(
                    CONCAT(u.user_first_name, ' ', IFNULL(u.user_middle_name, ''), ' ', u.user_last_name), 
                    CONCAT('Employer ', e.employer_id)
                ) AS full_name, 
                e.company_name,
                COALESCE(e.job_title, 'N/A') AS job_title, 
                (SELECT COUNT(*) FROM job_posting jp WHERE jp.employer_id = e.employer_id) AS jobs_posted,
                COALESCE(e.status, 'Active') AS status,
                e.document_url  -- Add document_url to the query
            FROM employer e
            LEFT JOIN user u ON e.user_id = u.user_id
        ");

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(!empty($data) ? $data : ["error" => "No employers found"]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employer_id = $_POST['employer_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if (!$employer_id || !$action) {
        echo json_encode(["error" => "Missing parameters"]);
        exit();
    }

    $validActions = [
        'suspend' => 'Suspended',
        'ban' => 'Banned',
        'reactivate' => 'Active'
    ];

    if (!array_key_exists($action, $validActions)) {
        echo json_encode(["error" => "Invalid action"]);
        exit();
    }

    try {
        $stmt = $conn->prepare("UPDATE employer SET status = :status WHERE employer_id = :employer_id");
        $stmt->execute([
            ':status' => $validActions[$action],
            ':employer_id' => $employer_id
        ]);

        echo json_encode(["success" => true, "message" => "Employer has been {$action}d successfully"]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
    exit();
}

?>
