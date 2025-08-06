<?php
session_start();
require '../config/dbcon.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['stud_id'])) {
    echo json_encode(["status" => "error", "message" => "Authentication required"]);
    exit();
}

$stud_id = $_SESSION['stud_id'];
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
$allowedResumeTypes = ['pdf', 'doc', 'docx'];

// File size limits (in bytes)
$maxImageSize = 5 * 1024 * 1024;    // 5MB
$maxResumeSize = 5 * 1024 * 1024;   // 5MB

// Initialize response
$response = ["status" => "success", "message" => "Profile updated successfully"];

try {
    // Begin transaction for atomic updates
    $conn->beginTransaction();

    // 1. Update basic information
    $updateStmt = $conn->prepare("UPDATE student SET 
        stud_first_name = :first_name,
        stud_middle_name = :middle_name,
        stud_last_name = :last_name,
        stud_gender = :gender,
        stud_date_of_birth = :date_of_birth,
        stud_email = :email,
        institution = :institution,
        graduation_yr = :graduation_yr,
        course_id = :course_id,
        bio = :bio
        WHERE stud_id = :stud_id");

    $updateStmt->execute([
        ':first_name' => $_POST['first_name'],
        ':middle_name' => $_POST['middle_name'] ?? null,
        ':last_name' => $_POST['last_name'],
        ':gender' => $_POST['gender'] ?? null,
        ':date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
        ':email' => $_POST['email'],
        ':institution' => $_POST['institution'] ?? null,
        ':graduation_yr' => !empty($_POST['graduation_yr']) ? $_POST['graduation_yr'] : null,
        ':course_id' => $_POST['course_id'] ?? null,
        ':bio' => $_POST['bio'] ?? null,
        ':stud_id' => $stud_id
    ]);

    // 2. Handle Profile Picture Upload
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
            throw new Exception("Profile picture must be less than 5MB");
        }

        // Get old filename for cleanup
        $getOldFile = $conn->prepare("SELECT profile_picture FROM student WHERE stud_id = :stud_id");
        $getOldFile->execute([':stud_id' => $stud_id]);
        $oldFile = $getOldFile->fetchColumn();

        // Generate unique filename
        $newFilename = "profile_" . $stud_id . "_" . time() . "." . $fileExtension;
        $targetPath = $targetDir . $newFilename;

        // Move uploaded file
        if (!move_uploaded_file($fileInfo["tmp_name"], $targetPath)) {
            throw new Exception("Failed to upload profile picture");
        }
        chmod($targetPath, 0644);

        // Update database
        $updatePic = $conn->prepare("UPDATE student SET profile_picture = :picture WHERE stud_id = :stud_id");
        $updatePic->execute([':picture' => $newFilename, ':stud_id' => $stud_id]);

        // Delete old file if it exists and belongs to user
        if ($oldFile && file_exists($targetDir . $oldFile)) {
            if (strpos($oldFile, "profile_" . $stud_id . "_") === 0) {
                @unlink($targetDir . $oldFile);
            }
        }
    }

    // 3. Handle Resume Upload
    if (!empty($_FILES["resume"]["name"])) {
        $fileInfo = $_FILES["resume"];
        $fileName = basename($fileInfo["name"]);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $fileSize = $fileInfo["size"];

        // Validate file
        if (!in_array($fileExtension, $allowedResumeTypes)) {
            throw new Exception("Only PDF, DOC and DOCX files are allowed for resumes");
        }

        if ($fileSize > $maxResumeSize) {
            throw new Exception("Resume must be less than 5MB");
        }

        // Get old filename for cleanup
        $getOldFile = $conn->prepare("SELECT resume_file FROM student WHERE stud_id = :stud_id");
        $getOldFile->execute([':stud_id' => $stud_id]);
        $oldFile = $getOldFile->fetchColumn();

        // Generate unique filename
        $newFilename = "resume_" . $stud_id . "_" . time() . "." . $fileExtension;
        $targetPath = $targetDir . $newFilename;

        // Move uploaded file
        if (!move_uploaded_file($fileInfo["tmp_name"], $targetPath)) {
            throw new Exception("Failed to upload resume");
        }
        chmod($targetPath, 0644);

        // Update database
        $updateResume = $conn->prepare("UPDATE student SET resume_file = :resume WHERE stud_id = :stud_id");
        $updateResume->execute([':resume' => $newFilename, ':stud_id' => $stud_id]);

        // Delete old file if it exists and belongs to user
        if ($oldFile && file_exists($targetDir . $oldFile)) {
            if (strpos($oldFile, "resume_" . $stud_id . "_") === 0) {
                @unlink($targetDir . $oldFile);
            }
        }
    }


// 4. Handle Skills Update
if (isset($_POST['skills']) && is_array($_POST['skills'])) {
    // Get all existing skill IDs for this student
    $getExisting = $conn->prepare("SELECT skill_id FROM stud_skill WHERE stud_id = :stud_id AND deleted_at IS NULL");
    $getExisting->execute([':stud_id' => $stud_id]);
    $existingSkills = $getExisting->fetchAll(PDO::FETCH_COLUMN);
    
    $processedSkills = [];
    
    foreach ($_POST['skills'] as $skill) {
        if (!empty($skill['skill_id']) && !empty($skill['proficiency'])) {
            $skillId = $skill['skill_id'];
            $proficiency = $skill['proficiency'];
            
            // Check if this is an existing skill being updated
            if (in_array($skillId, $existingSkills)) {
                $updateStmt = $conn->prepare("UPDATE stud_skill SET 
                    proficiency = :proficiency
                    WHERE stud_id = :stud_id AND skill_id = :skill_id");
                $updateStmt->execute([
                    ':proficiency' => $proficiency,
                    ':stud_id' => $stud_id,
                    ':skill_id' => $skillId
                ]);
            } else {
                // This is a new skill
                $insertStmt = $conn->prepare("INSERT INTO stud_skill 
                    (stud_id, skill_id, proficiency, group_no, created_at)
                    VALUES (:stud_id, :skill_id, :proficiency, :group_no, NOW())");
                $insertStmt->execute([
                    ':stud_id' => $stud_id,
                    ':skill_id' => $skillId,
                    ':proficiency' => $proficiency,
                    ':group_no' => $stud_id
                ]);
            }
            
            $processedSkills[] = $skillId;
        }
    }
    
    // Soft-delete skills that are no longer in the submitted list
    if (!empty($processedSkills)) {
        // Create named parameters for each skill ID
        $placeholders = array_map(function($i) {
            return ':skill_' . $i;
        }, array_keys($processedSkills));

        $sql = "UPDATE stud_skill SET 
                deleted_at = NOW()
                WHERE stud_id = :stud_id AND skill_id NOT IN (" . 
                implode(',', $placeholders) . ")";
                
        $deleteStmt = $conn->prepare($sql);

        // Create parameters array
        $params = [':stud_id' => $stud_id];
        foreach ($processedSkills as $i => $skillId) {
            $params[':skill_' . $i] = $skillId;
        }

        $deleteStmt->execute($params);
    }
} else {
    // If no skills are submitted, soft-delete all existing skills
    $deleteAllStmt = $conn->prepare("UPDATE stud_skill SET 
        deleted_at = NOW()
        WHERE stud_id = :stud_id AND deleted_at IS NULL");
    $deleteAllStmt->execute([':stud_id' => $stud_id]);
}


    // Commit all changes if everything succeeded
    $conn->commit();

    // Return success response
    echo json_encode($response);

} catch (Exception $e) {
    // Roll back any database changes if error occurs
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    // Clean up any partially uploaded files
    if (isset($targetPath) && file_exists($targetPath)) {
        @unlink($targetPath);
    }

    // Return error message
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
    exit();
}