<?php
require "../config/dbcon.php";
/** @var PDO $conn */
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

// Handle Fetch All Roles (No Pagination)
if (isset($_POST['action']) && $_POST['action'] == 'fetch') {
    $stmt = $conn->prepare("SELECT * FROM role WHERE deleted_at IS NULL ORDER BY role_id DESC");
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'roles' => $roles
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
