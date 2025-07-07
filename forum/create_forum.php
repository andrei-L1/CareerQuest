<?php
require_once '../config/dbcon.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    // Validate input
    if (empty($title) || empty($description)) {
        $_SESSION['error'] = "Title and description are required";
        header("Location: ../dashboard/forums.php");
        exit;
    }
    
    // Get actor ID based on who is logged in
    if (isset($_SESSION['user_id'])) {
        $entity_type = 'user';
        $entity_id = $_SESSION['user_id'];
    } else {
        $entity_type = 'student';
        $entity_id = $_SESSION['stud_id'];
    }
    
    // Get actor ID
    $query = "SELECT actor_id FROM actor WHERE entity_type = ? AND entity_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$entity_type, $entity_id]);
    $actor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$actor) {
        // Create actor record if it doesn't exist
        $query = "INSERT INTO actor (entity_type, entity_id) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$entity_type, $entity_id]);
        $actor_id = $conn->lastInsertId();
    } else {
        $actor_id = $actor['actor_id'];
    }
    
    // Create the forum
    $query = "INSERT INTO forum (title, description, created_by) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$title, $description, $actor_id]);
    
    $_SESSION['success'] = "Forum created successfully!";
    header("Location: ../dashboard/forums.php");
    exit;
} else {
    header("Location: ../dashboard/forums.php");
    exit;
}