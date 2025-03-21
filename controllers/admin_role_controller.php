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

// Handle Add Role
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $title = $_POST['role_title'];
    $description = $_POST['role_description'];

    $stmt = $conn->prepare("INSERT INTO role (role_title, role_description) VALUES (:title, :description)");
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);

    echo $stmt->execute() ? 'success' : 'error';
    exit;
}

// Handle Fetch Roles with Pagination
if (isset($_POST['action']) && $_POST['action'] == 'fetch') {
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $start = ($page - 1) * $limit;

    $totalStmt = $conn->query("SELECT COUNT(*) FROM role WHERE deleted_at IS NULL");
    $totalRecords = $totalStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    $stmt = $conn->prepare("SELECT * FROM role WHERE deleted_at IS NULL ORDER BY role_id DESC LIMIT :start, :limit");
    $stmt->bindParam(':start', $start, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'roles' => $roles,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ]);
    exit;
}

// Handle Edit Role
if (isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['id'];
    $title = $_POST['role_title'];
    $description = $_POST['role_description'];

    $stmt = $conn->prepare("UPDATE role SET role_title = :title, role_description = :description WHERE role_id = :id");
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    echo $stmt->execute() ? 'success' : 'error';
    exit;
}

// Handle Soft Delete Role
if (isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = $_POST['id'];

    $stmt = $conn->prepare("UPDATE role SET deleted_at = NOW() WHERE role_id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    echo $stmt->execute() ? 'success' : 'error';
    exit;
}
?>