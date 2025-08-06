<?php
// Start session
session_start();

// Include database connection
require '../config/dbcon.php';

// Set response header to JSON
header('Content-Type: application/json');

// Function to send JSON response
function sendResponse($status, $message = '') {
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    sendResponse('error', 'Invalid CSRF token.');
}

// Validate student ID
if (!isset($_POST['stud_id']) || !is_numeric($_POST['stud_id'])) {
    sendResponse('error', 'Invalid student ID.');
}

$stud_id = (int)$_POST['stud_id'];

// Check if student exists and has a resume
try {
    $stmt = $conn->prepare("SELECT resume_file FROM student WHERE stud_id = :stud_id AND deleted_at IS NULL");
    $stmt->execute(['stud_id' => $stud_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        sendResponse('error', 'Student not found.');
    }

    $resume_file = $student['resume_file'];

    if (empty($resume_file)) {
        sendResponse('error', 'No resume file to delete.');
    }

    // Delete the file from the server
    $upload_dir = '../Uploads/';
    $file_path = $upload_dir . $resume_file;
    if (file_exists($file_path)) {
        if (!unlink($file_path)) {
            sendResponse('error', 'Failed to delete resume file from server.');
        }
    }

    // Update the database to set resume_file to NULL
    $stmt = $conn->prepare("UPDATE student SET resume_file = NULL WHERE stud_id = :stud_id");
    if (!$stmt->execute(['stud_id' => $stud_id])) {
        sendResponse('error', 'Failed to update database.');
    }

    // Send success response
    sendResponse('success', 'Resume deleted successfully.');
} catch (PDOException $e) {
    sendResponse('error', 'Database error: ' . $e->getMessage());
}
?>