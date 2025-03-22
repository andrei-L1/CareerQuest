<?php 
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
// Handle Add Job Type
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $title = $_POST['title'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("INSERT INTO job_type (job_type_title, job_type_description) VALUES (:title, :description)");
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);

    echo $stmt->execute() ? 'success' : 'error';
    exit;
}

// Handle Fetch Job Types with Pagination
if (isset($_POST['action']) && $_POST['action'] == 'fetch') {
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5; // Default 5 per page
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $start = ($page - 1) * $limit;

    // Get total count for pagination
    $totalStmt = $conn->query("SELECT COUNT(*) FROM job_type");
    $totalRecords = $totalStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    // Fetch paginated job types
    $stmt = $conn->prepare("SELECT * FROM job_type ORDER BY job_type_id DESC LIMIT :start, :limit");
    $stmt->bindParam(':start', $start, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $jobTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    echo json_encode([
        'jobTypes' => $jobTypes,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ]);
    exit;
}

// Handle Edit Job Type
if (isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("UPDATE job_type SET job_type_title = :title, job_type_description = :description WHERE job_type_id = :id");
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    echo $stmt->execute() ? 'success' : 'error';
    exit;
}

// Handle Delete Job Type
if (isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM job_type WHERE job_type_id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    echo $stmt->execute() ? 'success' : 'error';
    exit;
}


?>