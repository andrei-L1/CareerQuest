<?php
// Start session
session_start();

// Redirect if not logged in
if (!isset($_SESSION['stud_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access.'
    ]);
    exit();
}

require '../config/dbcon.php'; // Database connection (provides $conn)

header('Content-Type: application/json'); // Ensure JSON response

// CSRF token validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid CSRF token.'
    ]);
    exit();
}

$stud_id = $_SESSION['stud_id'];

try {
    // Begin transaction
    $conn->beginTransaction();

    // Fetch current is_student value
    $stmt = $conn->prepare("SELECT is_student FROM student WHERE stud_id = ? AND deleted_at IS NULL");
    $stmt->execute([$stud_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found or account is deleted.'
        ]);
        $conn->rollBack();
        exit();
    }

    // Determine new is_student value (toggle) and corresponding edu_background
    $new_is_student = $student['is_student'] ? 0 : 1;
    $new_edu_background = $new_is_student ? 'College Student' : 'Professional';

    // Update is_student and edu_background
    $updateStmt = $conn->prepare("UPDATE student SET is_student = ?, edu_background = ? WHERE stud_id = ?");
    $updateStmt->execute([$new_is_student, $new_edu_background, $stud_id]);

    // Verify update
    if ($updateStmt->rowCount() === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update account type.'
        ]);
        $conn->rollBack();
        exit();
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'status' => 'success',
        'message' => 'Account type updated successfully.'
    ]);

} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}