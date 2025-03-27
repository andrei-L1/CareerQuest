<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../config/dbcon.php';

if (!isset($_SESSION['stud_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in."]);
    exit();
}

$stud_id = $_SESSION['stud_id'];
$targetDir = "../uploads/";

// Ensure upload directory exists
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// Allowed file types
$allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif'];
$allowedResumeTypes = ['pdf', 'doc', 'docx'];

// Max file sizes (in bytes)
$maxImageSize = 10 * 1024 * 1024; // 2MB
$maxResumeSize = 5 * 1024 * 1024; // 5MB

try {
    $response = ["status" => "success", "message" => "Profile updated!"];

    // ✅ Update Bio
    if (isset($_POST['bio'])) {
        $bio = filter_var($_POST['bio'], FILTER_SANITIZE_STRING);
        $updateStmt = $conn->prepare("UPDATE student SET bio = :bio WHERE stud_id = :stud_id");
        $updateStmt->bindParam(':bio', $bio);
        $updateStmt->bindParam(':stud_id', $stud_id);
        $updateStmt->execute();
    }

    // ✅ Handle Profile Picture Upload
    if (!empty($_FILES["profile_picture"]["name"])) {
        $fileName = $_FILES["profile_picture"]["name"];
        $fileTmp = $_FILES["profile_picture"]["tmp_name"];
        $fileSize = $_FILES["profile_picture"]["size"];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedImageTypes)) {
            echo json_encode(["status" => "error", "message" => "Invalid image format."]);
            exit();
        }
        if ($fileSize > $maxImageSize) {
            echo json_encode(["status" => "error", "message" => "Image is too large. Max size: 2MB."]);
            exit();
        }

        $profilePicture = "profile_" . $stud_id . "_" . time() . "." . $fileExtension;
        $targetFilePath = $targetDir . $profilePicture;

        if (move_uploaded_file($fileTmp, $targetFilePath)) {
            chmod($targetFilePath, 0644);
            $updateStmt = $conn->prepare("UPDATE student SET profile_picture = :profile_picture WHERE stud_id = :stud_id");
            $updateStmt->bindParam(':profile_picture', $profilePicture);
            $updateStmt->bindParam(':stud_id', $stud_id);
            $updateStmt->execute();

            $response["profile_picture"] = $profilePicture;
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to upload profile picture.";
        }
    }

    // ✅ Handle Resume Upload
    if (!empty($_FILES["resume"]["name"])) {
        $fileName = $_FILES["resume"]["name"];
        $fileTmp = $_FILES["resume"]["tmp_name"];
        $fileSize = $_FILES["resume"]["size"];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedResumeTypes)) {
            echo json_encode(["status" => "error", "message" => "Invalid resume format."]);
            exit();
        }
        if ($fileSize > $maxResumeSize) {
            echo json_encode(["status" => "error", "message" => "Resume file is too large. Max size: 5MB."]);
            exit();
        }

        $resumeFile = "resume_" . $stud_id . "_" . time() . "." . $fileExtension;
        $targetFilePath = $targetDir . $resumeFile;

        if (move_uploaded_file($fileTmp, $targetFilePath)) {
            chmod($targetFilePath, 0644);
            $updateStmt = $conn->prepare("UPDATE student SET resume_file = :resume_file WHERE stud_id = :stud_id");
            $updateStmt->bindParam(':resume_file', $resumeFile);
            $updateStmt->bindParam(':stud_id', $stud_id);
            $updateStmt->execute();

            $response["resume_file"] = $resumeFile;
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to upload resume.";
        }
    }

    echo json_encode($response);
    exit();
} catch (Exception $e) {
    error_log("Profile Update Error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Update failed."]);
    exit();
}
?>
