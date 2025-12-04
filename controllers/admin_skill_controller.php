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
$user_id = $_SESSION['user_id'];

// Handle Add Skill
if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $name = $_POST['skill_name']; // Fixed variable name
    $category = $_POST['category'];

    $stmt = $conn->prepare("INSERT INTO skill_masterlist (skill_name, category) VALUES (:name, :category)");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':category', $category);

    echo $stmt->execute() ? 'success' : 'error';
    exit;
}

// Handle Fetch Skills (no pagination)
if (isset($_POST['action']) && $_POST['action'] == 'fetch') {
    $stmt = $conn->prepare("SELECT * FROM skill_masterlist WHERE deleted_at IS NULL ORDER BY skill_id DESC");
    $stmt->execute();
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'skills' => $skills
    ]);
    exit;
}

// Handle Edit Skill
if (isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['id'];
    $name = $_POST['skill_name']; // Fixed variable name
    $category = $_POST['category'];

    $stmt = $conn->prepare("UPDATE skill_masterlist SET skill_name = :name, category = :category WHERE skill_id = :id");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':category', $category);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    echo $stmt->execute() ? 'success' : 'error';
    exit;
}

// Handle Soft Delete Skill
if (isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = $_POST['id'];

    $stmt = $conn->prepare("UPDATE skill_masterlist SET deleted_at = NOW() WHERE skill_id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    echo $stmt->execute() ? 'success' : 'error';
    exit;
}

try {
    // Fetch all skills, no pagination
    $stmt = $conn->prepare("SELECT skill_id, skill_name, category FROM skill_masterlist WHERE deleted_at IS NULL");
    $stmt->execute();
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($skills);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
