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

// Initialize the variable to avoid the warning
$current_role_id = null;

// Fetch user details and role ID if `user_id` is provided
if (isset($_GET['user_id'])) {
    $userStmt = $conn->prepare("SELECT role_id FROM user WHERE user_id = :user_id");
    $userStmt->execute([":user_id" => $_GET['user_id']]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    $current_role_id = $user['role_id'] ?? null; // Assign role_id if available, otherwise remain null
}

// Fetch all roles
$roleStmt = $conn->prepare("SELECT role_id, role_title FROM role ORDER BY role_title ASC");
$roleStmt->execute();
$roles = $roleStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Roles Function
function fetchRoles() {
    global $conn;
    $stmt = $conn->prepare("SELECT role_id, role_title FROM role");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function fetchUsers() {
    global $conn; 
    $sql = "SELECT a.actor_id, a.entity_type, a.entity_id, 
        COALESCE(u.user_first_name, s.stud_first_name) AS first_name, 
        COALESCE(u.user_middle_name, s.stud_middle_name) AS middle_name, 
        COALESCE(u.user_last_name, s.stud_last_name) AS last_name, 
        COALESCE(u.user_email, s.stud_email) AS email, 
        COALESCE(u.status, s.status, 'Active') AS status, 
        CASE 
            WHEN a.entity_type = 'user' THEN COALESCE(r.role_title, 'User')
            WHEN a.entity_type = 'student' AND s.is_student = 1 THEN 'Student'
            ELSE 'Professional'
        END AS role_name 
    FROM actor a
    LEFT JOIN user u ON a.entity_type = 'user' AND a.entity_id = u.user_id
    LEFT JOIN role r ON u.role_id = r.role_id
    LEFT JOIN student s ON a.entity_type = 'student' AND a.entity_id = s.stud_id
    WHERE a.deleted_at IS NULL AND COALESCE(u.status, s.status, 'Active') = 'Active'";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchDeletedUsers() {
    global $conn; 
    
    $sql = "SELECT a.actor_id, a.entity_type, a.entity_id, 
        COALESCE(u.user_first_name, s.stud_first_name) AS first_name, 
        COALESCE(u.user_middle_name, s.stud_middle_name) AS middle_name, 
        COALESCE(u.user_last_name, s.stud_last_name) AS last_name, 
        COALESCE(u.user_email, s.stud_email) AS email, 
        'Deleted' AS status, 
        CASE 
            WHEN a.entity_type = 'user' THEN COALESCE(r.role_title, 'User')
            WHEN a.entity_type = 'student' AND s.is_student = 1 THEN 'Student'
            ELSE 'Professional'
        END AS role_name 
    FROM actor a
    LEFT JOIN user u ON a.entity_type = 'user' AND a.entity_id = u.user_id
    LEFT JOIN role r ON u.role_id = r.role_id
    LEFT JOIN student s ON a.entity_type = 'student' AND a.entity_id = s.stud_id
    WHERE a.deleted_at IS NOT NULL";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$users = fetchUsers();
$deletedusers = fetchDeletedUsers();

// Handle user creation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["entity"])) {
    try {
        $conn->beginTransaction();

        $entity = trim($_POST['entity'] ?? '');
        $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL) : null;
        $password = $_POST['password'] ?? null;
        $confirm_password = $_POST['confirm_password'] ?? null;
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $status = $_POST['status'] ?? 'active';
        $role_id = !empty($_POST['role_id']) ? $_POST['role_id'] : null;

        // Student-specific fields
        $edu_background = trim($_POST['edu_background'] ?? 'College Student');
        $is_student = isset($_POST['is_student']) ? (int)$_POST['is_student'] : 0;
        $stud_gender = trim($_POST['stud_gender'] ?? null);
        $stud_date_of_birth = trim($_POST['stud_date_of_birth'] ?? null);
        $course_id = !empty($_POST['course_id']) ? $_POST['course_id'] : null;
        $bio = trim($_POST['bio'] ?? '');
        $institution = trim($_POST['institution'] ?? 'Unknown');
        $graduation_yr = !empty($_POST['graduation_yr']) ? $_POST['graduation_yr'] : null;

        // Debug: Log received POST data
        error_log("POST data: " . print_r($_POST, true));

        // Validate required fields
        $missing_fields = [];
        if (empty($entity)) $missing_fields[] = 'entity';
        if (!$email) $missing_fields[] = 'email';
        if (empty($password)) $missing_fields[] = 'password';
        if (empty($confirm_password)) $missing_fields[] = 'confirm_password';
        if (empty($first_name)) $missing_fields[] = 'first_name';
        if (empty($last_name)) $missing_fields[] = 'last_name';
        if ($entity === 'user' && empty($role_id)) $missing_fields[] = 'role_id';

        if (!empty($missing_fields)) {
            throw new Exception("The following required fields are missing or empty: " . implode(', ', $missing_fields));
        }

        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match.");
        }

        // Validate email uniqueness
        $emailCheckStmt = $conn->prepare("SELECT user_id FROM user WHERE user_email = :email UNION SELECT stud_id FROM student WHERE stud_email = :email");
        $emailCheckStmt->execute([":email" => $email]);
        if ($emailCheckStmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception("Email is already in use.");
        }

        $hashed_password = password_hash($password, PASSWORD_ARGON2ID);

        if ($entity === 'student') {
            // Validate student-specific fields
            if (empty($edu_background)) {
                throw new Exception("Educational background is required for students.");
            }

            // Validate course_id if provided
            if ($course_id) {
                $courseStmt = $conn->prepare("SELECT course_id FROM course WHERE course_id = :course_id");
                $courseStmt->execute([":course_id" => $course_id]);
                if (!$courseStmt->fetch(PDO::FETCH_ASSOC)) {
                    throw new Exception("Invalid course selected.");
                }
            }

            // Validate graduation year for students
            if ($is_student && $graduation_yr) {
                $current_year = date('Y');
                if ($graduation_yr < 1900 || $graduation_yr > ($current_year + 10)) {
                    throw new Exception("Invalid graduation year.");
                }
            }

            // Insert into student table
            $stmt = $conn->prepare("
                INSERT INTO student (
                    stud_email, stud_password, stud_first_name, stud_middle_name, stud_last_name,
                    edu_background, is_student, stud_gender, stud_date_of_birth, course_id, bio,
                    institution, graduation_yr, status
                ) VALUES (
                    :email, :password, :first_name, :middle_name, :last_name,
                    :edu_background, :is_student, :stud_gender, :stud_date_of_birth, :course_id, :bio,
                    :institution, :graduation_yr, :status
                )
            ");
            $stmt->execute([
                ":email" => $email,
                ":password" => $hashed_password,
                ":first_name" => $first_name,
                ":middle_name" => $middle_name ?: null,
                ":last_name" => $last_name,
                ":edu_background" => $edu_background,
                ":is_student" => $is_student,
                ":stud_gender" => $stud_gender,
                ":stud_date_of_birth" => $stud_date_of_birth ?: null,
                ":course_id" => $course_id,
                ":bio" => $bio ?: null,
                ":institution" => $institution,
                ":graduation_yr" => $is_student ? $graduation_yr : null,
                ":status" => $status,
            ]);
            $entity_id = $conn->lastInsertId();
        } else {
            // Validate role_id for non-students
            if (!$role_id) {
                throw new Exception("Role is required for non-students.");
            }

            // Validate role_id
            $roleStmt = $conn->prepare("SELECT role_id, role_title FROM role WHERE role_id = :role_id");
            $roleStmt->execute([":role_id" => $role_id]);
            $role = $roleStmt->fetch(PDO::FETCH_ASSOC);
            if (!$role) {
                throw new Exception("Invalid role selected.");
            }

            $user_type = $role['role_title'];

            // Insert into user table
            $stmt = $conn->prepare("
                INSERT INTO user (
                    user_type, user_email, user_password, role_id, user_first_name, user_middle_name, user_last_name, status
                ) VALUES (
                    :user_type, :email, :password, :role_id, :first_name, :middle_name, :last_name, :status
                )
            ");
            $stmt->execute([
                ":user_type" => $user_type,
                ":email" => $email,
                ":password" => $hashed_password,
                ":role_id" => $role_id,
                ":first_name" => $first_name,
                ":middle_name" => $middle_name ?: null,
                ":last_name" => $last_name,
                ":status" => $status,
            ]);
            $entity_id = $conn->lastInsertId();

            // Insert into employer table if role_id = 1 (Employer)
            if ($role_id == 1) {
                $stmt = $conn->prepare("
                    INSERT INTO employer (user_id, status, created_at)
                    VALUES (:user_id, :status, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([
                    ":user_id" => $entity_id,
                    ":status" => 'Verification',
                ]);
            }

            // Insert into actor table
            $stmt = $conn->prepare("INSERT INTO actor (entity_type, entity_id) VALUES (:entity, :entity_id)");
            $stmt->execute([":entity" => $entity, ":entity_id" => $entity_id]);
        }

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "User added successfully."]);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// Handle user soft delete
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_id"])) {
    try {
        $conn->beginTransaction();

        $actor_id = $_POST["delete_id"];

        // Fetch entity details from actor table
        $stmt = $conn->prepare("SELECT entity_type, entity_id FROM actor WHERE actor_id = :actor_id");
        $stmt->execute([":actor_id" => $actor_id]);
        $entity = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$entity) {
            throw new Exception("Invalid actor ID.");
        }

        $entityType = $entity["entity_type"];
        $entityId = $entity["entity_id"];

        // Soft delete from actor table
        $stmt = $conn->prepare("UPDATE actor SET deleted_at = NOW() WHERE actor_id = :actor_id");
        $stmt->execute([":actor_id" => $actor_id]);

        // Update status in the user or student table
        if ($entityType === "user") {
            $stmt = $conn->prepare("UPDATE user SET status = 'Deleted', deleted_at = NOW() WHERE user_id = :entity_id");
            $stmt->execute([":entity_id" => $entityId]);
            // Update employer table if user is an employer
            $employerStmt = $conn->prepare("UPDATE employer SET status = 'Banned', deleted_at = NOW() WHERE user_id = :entity_id");
            $employerStmt->execute([":entity_id" => $entityId]);
        } elseif ($entityType === "student") {
            $stmt = $conn->prepare("UPDATE student SET status = 'Deleted', deleted_at = NOW() WHERE stud_id = :entity_id");
            $stmt->execute([":entity_id" => $entityId]);
        }

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "User soft deleted successfully."]);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// Handle user restore
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["restore_id"])) {
    try {
        $conn->beginTransaction();

        $actor_id = $_POST["restore_id"];

        // Fetch entity details from actor table
        $stmt = $conn->prepare("SELECT entity_type, entity_id FROM actor WHERE actor_id = :actor_id");
        $stmt->execute([":actor_id" => $actor_id]);
        $entity = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$entity) {
            throw new Exception("Invalid actor ID.");
        }

        $entityType = $entity["entity_type"];
        $entityId = $entity["entity_id"];

        // Restore actor (remove deleted_at)
        $stmt = $conn->prepare("UPDATE actor SET deleted_at = NULL WHERE actor_id = :actor_id");
        $stmt->execute([":actor_id" => $actor_id]);

        // Update status back to "Active" in user or student table
        if ($entityType === "user") {
            $stmt = $conn->prepare("UPDATE user SET status = 'active', deleted_at = NULL WHERE user_id = :entity_id");
            $stmt->execute([":entity_id" => $entityId]);
            // Restore employer record
            $employerStmt = $conn->prepare("UPDATE employer SET status = 'Verification', deleted_at = NULL WHERE user_id = :entity_id");
            $employerStmt->execute([":entity_id" => $entityId]);
        } elseif ($entityType === "student") {
            $stmt = $conn->prepare("UPDATE student SET status = 'active', deleted_at = NULL WHERE stud_id = :entity_id");
            $stmt->execute([":entity_id" => $entityId]);
        }

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "User restored successfully."]);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}


function fetchUserById($user_id) {
    global $conn; 
    
    $sql = "SELECT a.actor_id, a.entity_type, a.entity_id, 
        COALESCE(u.user_first_name, s.stud_first_name) AS first_name, 
        COALESCE(u.user_middle_name, s.stud_middle_name) AS middle_name, 
        COALESCE(u.user_last_name, s.stud_last_name) AS last_name, 
        COALESCE(u.user_email, s.stud_email) AS email, 
        COALESCE(u.status, s.status, 'Active') AS status, 
        COALESCE(r.role_title, 'Student') AS role_name, 
        u.role_id
    FROM actor a
    LEFT JOIN user u ON a.entity_type = 'user' AND a.entity_id = u.user_id
    LEFT JOIN role r ON u.role_id = r.role_id
    LEFT JOIN student s ON a.entity_type = 'student' AND a.entity_id = s.stud_id
    WHERE a.actor_id = :user_id AND a.deleted_at IS NULL";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Edit User Function
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["edit_id"])) {
    try {
        $conn->beginTransaction();

        $actor_id = $_POST["edit_id"];
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? ''); // Now handling middle name
        $last_name = trim($_POST['last_name'] ?? '');
        $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL) : null;
        $status = $_POST['status'] ?? 'active';
        $role_id = !empty($_POST['role']) ? $_POST['role'] : null; // Handle empty role

        if (!$email || empty($first_name) || empty($last_name)) {
            throw new Exception("All fields are required.");
        }

        // Fetch entity details
        $stmt = $conn->prepare("SELECT entity_type, entity_id FROM actor WHERE actor_id = :actor_id");
        $stmt->execute([':actor_id' => $actor_id]);
        $entity = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$entity) {
            throw new Exception("Invalid actor ID.");
        }

        $entityType = $entity["entity_type"];
        $entityId = $entity["entity_id"];

        if ($entityType === 'user') {
            // Role-to-UserType Mapping (Only for 'user' entity type)
            $roleToUserType = [
                "1" => "Employer",    
                "2" => "Professional",  
                "3" => "Moderator",     
                "4" => "Admin"         
            ];
            
            // Determine user_type based on role_id (default to NULL if role_id doesn't exist in mapping)
            $user_type = $roleToUserType[$role_id] ?? null;

            // Validate role_id if provided
            if ($role_id) {
                $roleStmt = $conn->prepare("SELECT role_id FROM role WHERE role_id = :role_id");
                $roleStmt->execute([':role_id' => $role_id]);
                if (!$roleStmt->fetch(PDO::FETCH_ASSOC)) {
                    throw new Exception("Invalid role selected.");
                }
            }

            // Ensure email is unique
            $stmt = $conn->prepare("SELECT user_id FROM user WHERE user_email = :email AND user_id != :entity_id");
            $stmt->execute([":email" => $email, ":entity_id" => $entityId]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception("Email is already in use.");
            }

            // Update user table
            $stmt = $conn->prepare("UPDATE user 
                                    SET user_first_name = :first_name, 
                                        user_middle_name = :middle_name, 
                                        user_last_name = :last_name, 
                                        user_email = :email, 
                                        role_id = :role_id, 
                                        user_type = :user_type,
                                        status = :status 
                                    WHERE user_id = :entity_id");
            $stmt->execute([
                ":first_name" => $first_name,
                ":middle_name" => $middle_name, 
                ":last_name" => $last_name,
                ":email" => $email,
                ":role_id" => $role_id,
                ":user_type" => $user_type,  
                ":status" => $status,
                ":entity_id" => $entityId,
            ]);

        } elseif ($entityType === 'student') {
            // Ensure email is unique
            $stmt = $conn->prepare("SELECT stud_id FROM student WHERE stud_email = :email AND stud_id != :entity_id");
            $stmt->execute([":email" => $email, ":entity_id" => $entityId]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception("Email is already in use.");
            }

            // Update student table
            $stmt = $conn->prepare("UPDATE student 
                                    SET stud_first_name = :first_name, 
                                        stud_middle_name = :middle_name, 
                                        stud_last_name = :last_name, 
                                        stud_email = :email, 
                                        status = :status 
                                    WHERE stud_id = :entity_id");
            $stmt->execute([
                ":first_name" => $first_name,
                ":middle_name" => $middle_name, 
                ":last_name" => $last_name,
                ":email" => $email,
                ":status" => $status,
                ":entity_id" => $entityId,
            ]);
        }

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "User updated successfully."]);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

$sql = "SELECT 
            (SELECT COUNT(*) FROM actor) AS total_users,
            (SELECT COUNT(*) FROM student WHERE is_student = 1 AND status = 'active') AS total_students,
            (SELECT COUNT(*) FROM user WHERE role_id = 4 AND status = 'active') AS total_admins,
            (SELECT COUNT(*) FROM user WHERE role_id = 3 AND status = 'active') AS total_moderators,
            (SELECT COUNT(*) FROM student WHERE is_student = 0 AND status = 'active') AS total_professionals,
            (SELECT COUNT(*) FROM user WHERE role_id = 1 AND status = 'active') AS total_employers";

$stmt = $conn->query($sql);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$totalUsers = $stats['total_users'];
$totalStudents = $stats['total_students'];
$totalAdmins = $stats['total_admins'];
$totalModerators = $stats['total_moderators'];
$totalProfessionals = $stats['total_professionals'];
$totalEmployers = $stats['total_employers'];

?>