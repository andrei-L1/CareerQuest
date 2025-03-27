<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../config/dbcon.php';

// Check if the student is logged in
if (!isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit();
}

$stud_id = $_SESSION['stud_id'];

try {
    // Fetch student details
    $stmt = $conn->prepare("
        SELECT stud_first_name, stud_last_name, stud_email, institution, graduation_yr, course.course_title, bio, profile_picture, resume_file 
        FROM student 
        LEFT JOIN course ON student.course_id = course.course_id
        WHERE stud_id = :stud_id
    ");
    $stmt->bindParam(':stud_id', $stud_id, PDO::PARAM_INT);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        throw new Exception("Student not found.");
    }

} catch (Exception $e) {
    error_log("Student Profile Error: " . $e->getMessage());
    header("Location: ../auth/logout.php");
    exit();
}

// Handle form submission for updating bio, profile picture, or resume
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['bio'])) {
            $bio = htmlspecialchars($_POST['bio']);
            $updateStmt = $conn->prepare("UPDATE student SET bio = :bio WHERE stud_id = :stud_id");
            $updateStmt->bindParam(':bio', $bio);
            $updateStmt->bindParam(':stud_id', $stud_id);
            $updateStmt->execute();
        }

        // File Upload Handling
        $targetDir = "../uploads/"; // Correct path

        // Handle Profile Picture Upload
        if (!empty($_FILES["profile_picture"]["name"])) {
            $fileExtension = pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION);
            $profilePicture = "profile_" . $stud_id . "_" . time() . "." . $fileExtension; // Unique filename
            $targetFilePath = $targetDir . $profilePicture;

            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFilePath)) {
                $updateStmt = $conn->prepare("UPDATE student SET profile_picture = :profile_picture WHERE stud_id = :stud_id");
                $updateStmt->bindParam(':profile_picture', $profilePicture);
                $updateStmt->bindParam(':stud_id', $stud_id);
                $updateStmt->execute();
            } else {
                header("Location: profile.php?error=upload_failed");
                exit();
            }
        }

        // Handle Resume Upload
        if (!empty($_FILES["resume"]["name"])) {
            $fileExtension = pathinfo($_FILES["resume"]["name"], PATHINFO_EXTENSION);
            $resumeFile = "resume_" . $stud_id . "_" . time() . "." . $fileExtension; // Unique filename
            $targetFilePath = $targetDir . $resumeFile;

            if (move_uploaded_file($_FILES["resume"]["tmp_name"], $targetFilePath)) {
                $updateStmt = $conn->prepare("UPDATE student SET resume_file = :resume_file WHERE stud_id = :stud_id");
                $updateStmt->bindParam(':resume_file', $resumeFile);
                $updateStmt->bindParam(':stud_id', $stud_id);
                $updateStmt->execute();
            } else {
                header("Location: profile.php?error=upload_failed");
                exit();
            }
        }

        header("Location: profile.php?success=1");
        exit();

    } catch (Exception $e) {
        error_log("Profile Update Error: " . $e->getMessage());
        header("Location: profile.php?error=update_failed");
        exit();
    }
}

// Determine Profile Picture URL
$profilePicPath = "../uploads/" . ($student['profile_picture'] ?? '');
if (!empty($student['profile_picture']) && file_exists($profilePicPath)) {
    $profile_pic = htmlspecialchars($profilePicPath);
} else {
    $full_name = trim(($student['stud_first_name'] ?? '') . ' ' . ($student['stud_last_name'] ?? ''));
    $profile_pic = "https://ui-avatars.com/api/?name=" . urlencode($full_name) . "&background=3A7BD5&color=fff&rounded=true&size=128";
}
?>
