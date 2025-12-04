<?php
require_once '../config/dbcon.php';
/** @var PDO $conn */
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
    $is_private = isset($_POST['is_private']) ? 1 : 0; // checkbox logic

    // Validate input
    if (empty($title) || empty($description)) {
        $_SESSION['error'] = "Title and description are required";
        header("Location: ../dashboard/forums.php");
        exit;
    }

    // Determine entity type and ID
    if (isset($_SESSION['user_id'])) {
        $entity_type = 'user';
        $entity_id = $_SESSION['user_id'];
    } else {
        $entity_type = 'student';
        $entity_id = $_SESSION['stud_id'];
    }

    try {
        // Get actor ID (create if doesn't exist)
        $query = "SELECT actor_id FROM actor WHERE entity_type = ? AND entity_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$entity_type, $entity_id]);
        $actor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$actor) {
            $query = "INSERT INTO actor (entity_type, entity_id) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->execute([$entity_type, $entity_id]);
            $actor_id = $conn->lastInsertId();
        } else {
            $actor_id = $actor['actor_id'];
        }

        // Insert into forum table with is_private
        $query = "INSERT INTO forum (title, description, is_private, created_by) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$title, $description, $is_private, $actor_id]);
        $forum_id = $conn->lastInsertId();

        // Add creator as Admin in forum_membership
        $membershipQuery = "INSERT INTO forum_membership (forum_id, actor_id, role, status) VALUES (?, ?, 'Admin', 'Active')";
        $stmt = $conn->prepare($membershipQuery);
        $stmt->execute([$forum_id, $actor_id]);

        $_SESSION['success'] = "Forum created successfully!";
        header("Location: ../dashboard/forums.php");
        exit;

    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: ../dashboard/forums.php");
        exit;
    }
} else {
    header("Location: ../dashboard/forums.php");
    exit;
}
