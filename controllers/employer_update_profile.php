<?php
// Prevent any output before JSON response
ob_clean();
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require '../config/dbcon.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Authentication required"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$targetDir = "../uploads/";

// Create upload directory if it doesn't exist
if (!is_dir($targetDir)) {
    if (!mkdir($targetDir, 0755, true)) {
        echo json_encode(["status" => "error", "message" => "Failed to create upload directory"]);
        exit();
    }
}

// File type whitelists
$allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif'];

// File size limits (in bytes)
$maxImageSize = 10 * 1024 * 1024;    // 2MB

// Initialize response
$response = ["status" => "success", "message" => "Profile updated successfully"];

try {
    // Begin transaction for atomic updates
    $conn->beginTransaction();

    // Get employer ID
    $employerStmt = $conn->prepare("SELECT employer_id FROM employer WHERE user_id = :user_id AND deleted_at IS NULL");
    $employerStmt->execute([':user_id' => $user_id]);
    $employer = $employerStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employer) {
        throw new Exception("Employer not found");
    }
    
    $employer_id = $employer['employer_id'];

    // Check for required columns in employer table
    $requiredColumns = ['company_description', 'company_website'];
    $columnsStmt = $conn->query("SHOW COLUMNS FROM employer");
    $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN, 0);
    foreach ($requiredColumns as $col) {
        if (!in_array($col, $columns)) {
            throw new Exception("Missing required column in employer table: $col");
        }
    }

    // Handle Profile Picture Upload
    if (!empty($_FILES["profile_picture"]["name"])) {
        $fileInfo = $_FILES["profile_picture"];
        $fileName = basename($fileInfo["name"]);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $fileSize = $fileInfo["size"];

        // Validate file
        if (!in_array($fileExtension, $allowedImageTypes)) {
            throw new Exception("Only JPG, JPEG, PNG & GIF files are allowed for profile pictures");
        }

        if ($fileSize > $maxImageSize) {
            throw new Exception("Profile picture must be less than 10MB");
        }

        // Generate unique filename
        $newFileName = "profile_" . $user_id . "_" . time() . "." . $fileExtension;
        $targetFilePath = $targetDir . $newFileName;

        // Move uploaded file
        if (move_uploaded_file($fileInfo["tmp_name"], $targetFilePath)) {
            // Update user table with new profile picture
            $updateUserStmt = $conn->prepare("UPDATE user SET picture_file = :filename WHERE user_id = :user_id");
            $updateUserStmt->execute([':filename' => $newFileName, ':user_id' => $user_id]);
            
            $response['profile_picture'] = $newFileName;
        } else {
            throw new Exception("Failed to upload profile picture");
        }
    }

    // Handle Company Logo Upload
    if (!empty($_FILES["company_logo"]["name"])) {
        $fileInfo = $_FILES["company_logo"];
        $fileName = basename($fileInfo["name"]);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $fileSize = $fileInfo["size"];

        // Validate file
        if (!in_array($fileExtension, $allowedImageTypes)) {
            throw new Exception("Only JPG, JPEG, PNG & GIF files are allowed for company logo");
        }

        if ($fileSize > 10 * 1024 * 1024) { // 10MB
            throw new Exception("Company logo must be less than 10MB");
        }

        // Generate unique filename
        $newFileName = "company_logo_" . $user_id . "_" . time() . "." . $fileExtension;
        $targetFilePath = $targetDir . $newFileName;

        // Move uploaded file
        if (move_uploaded_file($fileInfo["tmp_name"], $targetFilePath)) {
            // Update employer table with new company logo
            $updateEmployerStmt = $conn->prepare("UPDATE employer SET company_logo = :filename WHERE employer_id = :employer_id");
            $updateEmployerStmt->execute([':filename' => $newFileName, ':employer_id' => $employer_id]);
            $response['company_logo'] = $newFileName;
        } else {
            throw new Exception("Failed to upload company logo");
        }
    }

    // Handle profile information updates
    if (isset($_POST['first_name']) || isset($_POST['last_name']) || isset($_POST['email'])) {
        $userUpdateFields = [];
        $userParams = [':user_id' => $user_id];
        
        if (isset($_POST['first_name'])) {
            $userUpdateFields[] = "user_first_name = :first_name";
            $userParams[':first_name'] = trim($_POST['first_name']);
        }
        
        if (isset($_POST['last_name'])) {
            $userUpdateFields[] = "user_last_name = :last_name";
            $userParams[':last_name'] = trim($_POST['last_name']);
        }
        
        if (isset($_POST['email'])) {
            // Check if email is already taken by another user
            $emailCheck = $conn->prepare("SELECT user_id FROM user WHERE user_email = :email AND user_id != :user_id");
            $emailCheck->execute([':email' => trim($_POST['email']), ':user_id' => $user_id]);
            if ($emailCheck->fetch()) {
                throw new Exception("Email address is already in use by another account");
            }
            
            $userUpdateFields[] = "user_email = :email";
            $userParams[':email'] = trim($_POST['email']);
        }
        
        if (!empty($userUpdateFields)) {
            $sql = "UPDATE user SET " . implode(", ", $userUpdateFields) . " WHERE user_id = :user_id";
            $updateStmt = $conn->prepare($sql);
            $updateStmt->execute($userParams);
        }
    }
    
    // Handle employer information updates
    if (isset($_POST['job_title']) || isset($_POST['company_name']) || isset($_POST['contact_number']) || isset($_POST['company_description']) || isset($_POST['company_website'])) {
        $employerUpdateFields = [];
        $employerParams = [':employer_id' => $employer_id];
        
        if (isset($_POST['job_title'])) {
            $employerUpdateFields[] = "job_title = :job_title";
            $employerParams[':job_title'] = trim($_POST['job_title']);
        }
        
        if (isset($_POST['company_name'])) {
            $employerUpdateFields[] = "company_name = :company_name";
            $employerParams[':company_name'] = trim($_POST['company_name']);
        }
        
        if (isset($_POST['contact_number'])) {
            $employerUpdateFields[] = "contact_number = :contact_number";
            $employerParams[':contact_number'] = trim($_POST['contact_number']);
        }
        
        if (isset($_POST['company_description'])) {
            $employerUpdateFields[] = "company_description = :company_description";
            $employerParams[':company_description'] = trim($_POST['company_description']);
        }
        
        if (isset($_POST['company_website'])) {
            $employerUpdateFields[] = "company_website = :company_website";
            $employerParams[':company_website'] = trim($_POST['company_website']);
        }
        
        if (!empty($employerUpdateFields)) {
            $sql = "UPDATE employer SET " . implode(", ", $employerUpdateFields) . " WHERE employer_id = :employer_id";
            $updateStmt = $conn->prepare($sql);
            $updateStmt->execute($employerParams);
        }
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode($response);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    // Log error to a file for debugging
    file_put_contents(__DIR__ . '/../logs/employer_update_error.log', date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . "\n", FILE_APPEND);
    error_log("Employer Profile Update Error: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 