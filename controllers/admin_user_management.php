<?php
require "../config/dbcon.php";

function fetchUsers() {
    global $conn; 
    
    $sql = "SELECT a.actor_id, a.entity_type, a.entity_id, 
        COALESCE(u.user_first_name, s.stud_first_name) AS first_name, 
        COALESCE(u.user_last_name, s.stud_last_name) AS last_name, 
        COALESCE(u.user_email, s.stud_email) AS email, 
        COALESCE(u.status, s.status, 'Active') AS status, 
        COALESCE(r.role_title, 'Student') AS role_name 
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
        COALESCE(u.user_last_name, s.stud_last_name) AS last_name, 
        COALESCE(u.user_email, s.stud_email) AS email, 
        'Deleted' AS status, 
        COALESCE(r.role_title, 'Student') AS role_name 
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

        $entity = trim($_POST['entity']);
        $email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL) : null;
        $password = $_POST['password'] ?? null;
        $confirm_password = $_POST['confirm_password'] ?? null;
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $status = $_POST['status'] ?? 'active';
        $role = trim($_POST['role'] ?? '');
        $institution = trim($_POST['institution'] ?? 'Unknown');
       

        if (!$email || !$password || !$confirm_password || empty($first_name) || empty($last_name)) {
            throw new Exception("All fields are required.");
        }

        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match.");
        }

        $hashed_password = password_hash($password, PASSWORD_ARGON2ID);

        if ($entity === 'student') {
            $stud_no = strtoupper(substr($first_name, 0, 2) . rand(1000, 9999));
            $stmt = $conn->prepare("INSERT INTO student (stud_no, stud_email, stud_password, stud_first_name, stud_last_name, institution, status) 
                                    VALUES (:stud_no, :email, :password, :first_name, :last_name, :institution, :status)");
            $stmt->execute([
                ":stud_no" => $stud_no,
                ":email" => $email,
                ":password" => $hashed_password,
                ":first_name" => $first_name,
                ":last_name" => $last_name,
                ":institution" => $institution,
                ":status" => $status,
            ]);
            $entity_id = $conn->lastInsertId();
        } else {
            // Validate role (now using role **title** instead of ID)
            if (!$role) {
                throw new Exception("Role is required for non-students.");
            }

            // Fetch role_id from role table
            $roleStmt = $conn->prepare("SELECT role_id FROM role WHERE role_title = :role");
            $roleStmt->bindParam(':role', $role, PDO::PARAM_STR);
            $roleStmt->execute();
            $roleData = $roleStmt->fetch(PDO::FETCH_ASSOC);
            $role_id = $roleData['role_id'] ?? null;

            if (!$role_id) {
                throw new Exception("Invalid role.");
            }

            // Fetch role_title (user_type) from role table
            $roleStmt = $conn->prepare("SELECT role_title FROM role WHERE role_id = :role_id");
            $roleStmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
            $roleStmt->execute();
            $role = $roleStmt->fetch(PDO::FETCH_ASSOC);

            if (!$role) {
                throw new Exception("Invalid role_id provided.");
            }

            $user_type = $role['role_title']; // Assign user_type based on role_title

            // Insert user into database
            $stmt = $conn->prepare("INSERT INTO user (user_type, user_email, user_password, role_id, user_first_name, user_last_name, `status`) 
                                    VALUES (:user_type, :email, :password, :role_id, :first_name, :last_name, :status)");
            $stmt->bindParam(':user_type', $user_type, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
            $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
            $stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
            $stmt->bindParam(':last_name', $last_name, PDO::PARAM_STR);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);

            $stmt->execute();
            $entity_id = $conn->lastInsertId();

        }

        $stmt = $conn->prepare("INSERT INTO actor (entity_type, entity_id) VALUES (:entity, :entity_id)");
        $stmt->execute([":entity" => $entity, ":entity_id" => $entity_id]);

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
        $stmt = $conn->prepare("UPDATE actor SET deleted_at = NOW() WHERE actor_id = :actor_id");
        $stmt->execute([":actor_id" => $actor_id]);

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
        $stmt = $conn->prepare("UPDATE actor SET deleted_at = NULL WHERE actor_id = :actor_id");
        $stmt->execute([":actor_id" => $actor_id]);

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "User restored successfully."]);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

?>
