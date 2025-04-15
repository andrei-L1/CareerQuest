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

/*
// Handle Fetch Skills with Pagination
if (isset($_POST['action']) && $_POST['action'] == 'fetch') {
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $start = ($page - 1) * $limit;

    $totalStmt = $conn->query("SELECT COUNT(*) FROM skill_masterlist WHERE deleted_at IS NULL");
    $totalRecords = $totalStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    $stmt = $conn->prepare("SELECT * FROM skill_masterlist WHERE deleted_at IS NULL ORDER BY skill_id DESC LIMIT :start, :limit");
    $stmt->bindParam(':start', $start, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'skills' => $skills,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ]);
    exit;
}

*/
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
    $stmt = $conn->prepare("SELECT skill_id, skill_name, category FROM skill_masterlist WHERE deleted_at IS NULL");
    $stmt->execute();
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($skills);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

?>
