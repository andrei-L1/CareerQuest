<?php
require '../auth/employer_auth.php';
require '../config/dbcon.php';

// Set JSON content type
header('Content-Type: application/json');

// Start session for CSRF and employer_id
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Validate input
$job_id = (int)$_POST['job_id'] ?? 0;
$title = filter_var(trim($_POST['title'] ?? ''), FILTER_SANITIZE_STRING);
$job_type_id = (int)$_POST['job_type_id'] ?? 0;
$location = filter_var(trim($_POST['location'] ?? ''), FILTER_SANITIZE_STRING);
$min_salary = !empty($_POST['min_salary']) ? (float)$_POST['min_salary'] : null;
$max_salary = !empty($_POST['max_salary']) ? (float)$_POST['max_salary'] : null;
$salary_type = filter_var(trim($_POST['salary_type'] ?? 'Yearly'), FILTER_SANITIZE_STRING);
$salary_disclosure = isset($_POST['salary_disclosure']) ? 1 : 0;
$expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
$description = filter_var(trim($_POST['description'] ?? ''), FILTER_SANITIZE_STRING);
$visible_to = filter_var(trim($_POST['visible_to'] ?? 'both'), FILTER_SANITIZE_STRING);
$skills_json = $_POST['skills_data'] ?? '[]';

// Log skills input for debugging
error_log("Skills JSON received: " . $skills_json);

// Decode skills JSON
$skills = json_decode($skills_json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON decode error: " . json_last_error_msg());
    echo json_encode(['success' => false, 'message' => 'Invalid JSON format for skills: ' . json_last_error_msg()]);
    exit;
}

// Server-side validation
$errors = [];
if (empty($title) || strlen($title) > 255) {
    $errors[] = 'Job title is required and must be 255 characters or less.';
}
if (empty($job_type_id)) {
    $errors[] = 'Job type is required.';
}
if (empty($location) || strlen($location) > 255) {
    $errors[] = 'Location is required and must be 255 characters or less.';
}
if (empty($description)) {
    $errors[] = 'Job description is required.';
}
if ($salary_disclosure) {
    if ($min_salary !== null && ($min_salary < 0 || $min_salary > 9999999.99)) {
        $errors[] = 'Minimum salary must be between 0 and 9,999,999.99.';
    }
    if ($max_salary !== null && ($max_salary < 0 || $max_salary > 9999999.99)) {
        $errors[] = 'Maximum salary must be between 0 and 9,999,999.99.';
    }
    if ($min_salary !== null && $max_salary !== null && $max_salary < $min_salary) {
        $errors[] = 'Maximum salary must be greater than or equal to minimum salary.';
    }
    if (empty($salary_type) || !in_array($salary_type, ['Hourly', 'Weekly', 'Monthly', 'Yearly', 'Commission', 'Negotiable'])) {
        $errors[] = 'Invalid salary type.';
    }
}
if ($expires_at && strtotime($expires_at) <= time()) {
    $errors[] = 'Expiration date must be in the future.';
}
if (!in_array($visible_to, ['students', 'applicants', 'both'])) {
    $errors[] = 'Invalid visibility option selected.';
}

// Validate skills (optional, so empty array is allowed)
$valid_skills = [];
if (!is_array($skills)) {
    $errors[] = 'Skills must be a valid array.';
} elseif (!empty($skills)) {
    $valid_skill_ids = array_column(getAvailableSkills(), 'skill_id');
    foreach ($skills as $index => $skill) {
        $skill_id = isset($skill['id']) ? (int)$skill['id'] : null;
        if (!$skill_id || !is_numeric($skill_id) || $skill_id <= 0) {
            error_log("Invalid skill at index $index: missing or invalid ID - " . json_encode($skill));
            continue; // Skip invalid skill
        }
        if (!in_array($skill_id, $valid_skill_ids)) {
            error_log("Invalid skill ID {$skill_id} at index $index");
            $errors[] = "Skill ID {$skill_id} at index $index is not a valid skill.";
            continue;
        }
        if (!in_array($skill['importance'], ['Low', 'Medium', 'High'])) {
            error_log("Invalid importance value '{$skill['importance']}' for skill ID {$skill_id} at index $index");
            $errors[] = "Invalid importance value '{$skill['importance']}' for skill ID {$skill_id} at index $index.";
            continue;
        }
        $valid_skills[] = ['id' => $skill_id, 'importance' => $skill['importance']];
    }
}

if (!empty($errors)) {
    error_log("Validation errors: " . implode(' ', $errors));
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// Update job in database
try {
    global $conn;
    $conn->beginTransaction();

    // Verify job belongs to employer
    $stmt = $conn->prepare("SELECT employer_id FROM job_posting WHERE job_id = ? AND deleted_at IS NULL");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$job || $job['employer_id'] != $_SESSION['employer_id']) {
        throw new Exception('Unauthorized access to job.');
    }

    // Update job details
    $stmt = $conn->prepare("
        UPDATE job_posting 
        SET title = ?, job_type_id = ?, location = ?, min_salary = ?, max_salary = ?, 
            salary_type = ?, salary_disclosure = ?, description = ?, expires_at = ?, 
            moderation_status = 'Pending', visible_to = ?
        WHERE job_id = ? AND employer_id = ?
    ");
    $stmt->execute([
        $title,
        $job_type_id,
        $location,
        $salary_disclosure ? $min_salary : null,
        $salary_disclosure ? $max_salary : null,
        $salary_type,
        $salary_disclosure,
        $description,
        $expires_at,
        $visible_to,
        $job_id,
        $_SESSION['employer_id']
    ]);

    // Update skills
    $stmt = $conn->prepare("UPDATE job_skill SET deleted_at = NOW() WHERE job_id = ?");
    $stmt->execute([$job_id]);

    if (!empty($valid_skills)) {
        $stmt = $conn->prepare("INSERT INTO job_skill (job_id, skill_id, importance) VALUES (?, ?, ?)");
        foreach ($valid_skills as $skill) {
            $stmt->execute([$job_id, $skill['id'], $skill['importance']]);
        }
    }

    $conn->commit();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Regenerate CSRF token
    echo json_encode(['success' => true, 'message' => 'Job updated successfully']);
    exit;
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Failed to update job: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update job: ' . $e->getMessage()]);
    exit;
}

// Helper function to get available skills
function getAvailableSkills() {
    global $conn;
    $stmt = $conn->query("SELECT skill_id, skill_name FROM skill_masterlist WHERE deleted_at IS NULL");
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Available Skills: " . print_r($skills, true)); // Log for debugging
    return $skills;
}
?>