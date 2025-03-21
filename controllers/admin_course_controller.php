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

// Handle Add Course
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $title = $_POST['course_title'];
    $description = $_POST['course_description'];

    $stmt = $conn->prepare("INSERT INTO course (course_title, course_description) VALUES (:title, :description)");
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);

    echo $stmt->execute() ? 'success' : 'error';
    exit;
}

// Handle Fetch Courses with Pagination
if (isset($_POST['action']) && $_POST['action'] == 'fetch') {
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $start = ($page - 1) * $limit;

    $totalStmt = $conn->query("SELECT COUNT(*) FROM course WHERE deleted_at IS NULL");
    $totalRecords = $totalStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    $stmt = $conn->prepare("SELECT * FROM course WHERE deleted_at IS NULL ORDER BY course_id DESC LIMIT :start, :limit");
    $stmt->bindParam(':start', $start, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'courses' => $courses,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ]);
    exit;
}

// Handle Edit Course
if (isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['course_id'];
    $title = $_POST['course_title'];
    $description = $_POST['course_description'];

    $stmt = $conn->prepare("UPDATE course SET course_title = :title, course_description = :description WHERE course_id = :id");
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    echo $stmt->execute() ? 'success' : 'error';
    exit;
}

// Handle Soft Delete Course
if (isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = $_POST['course_id'];

    $stmt = $conn->prepare("UPDATE course SET deleted_at = NOW() WHERE course_id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    echo $stmt->execute() ? 'success' : 'error';
    exit;
}
?>
