<?php
require "../config/dbcon.php";
function fetchUsers() {
    global $conn; // Use $conn instead of $pdo
    
    $sql = "SELECT a.actor_id, a.entity_type, a.entity_id, 
        COALESCE(u.user_first_name, s.stud_first_name) AS first_name, 
        COALESCE(u.user_last_name, s.stud_last_name) AS last_name, 
        COALESCE(u.user_email, s.stud_email) AS email, 
        COALESCE(u.status, 'Pending') AS status, 
        COALESCE(r.role_title, 'Student') AS role_name 
    FROM actor a
    LEFT JOIN user u ON a.entity_type = 'user' AND a.entity_id = u.user_id
    LEFT JOIN role r ON u.role_id = r.role_id
    LEFT JOIN student s ON a.entity_type = 'student' AND a.entity_id = s.stud_id
    WHERE a.deleted_at IS NULL";


    $stmt = $conn->prepare($sql); 
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$users = fetchUsers();


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $conn->beginTransaction();

        $entity = $_POST['entity'] ?? null;
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? null;
        $confirm_password = $_POST['confirm_password'] ?? null;
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $status = $_POST['status'] ?? 'Pending';
        $role = $_POST['role'] ?? null;
        $institution = trim($_POST['institution'] ?? 'Unknown'); // Defaults for students

        // Validate required fields
        if (!$email || !$password || !$confirm_password || empty($first_name) || empty($last_name)) {
            throw new Exception("All fields are required.");
        }

        // Ensure password and confirm password match
        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match.");
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_ARGON2ID);

        if ($entity === 'student') {
            // Generate a unique student number
            $stud_no = strtoupper(substr($first_name, 0, 2) . rand(1000, 9999));

            // Insert into student table
            $stmt = $conn->prepare("INSERT INTO student (stud_no, stud_email, stud_password, stud_first_name, stud_last_name, institution) 
                                    VALUES (:stud_no, :email, :password, :first_name, :last_name, :institution)");
            $stmt->execute([
                ":stud_no" => $stud_no,
                ":email" => $email,
                ":password" => $hashed_password,
                ":first_name" => $first_name,
                ":last_name" => $last_name,
                ":institution" => $institution
            ]);
            $entity_id = $conn->lastInsertId();
        } else {
            // Validate role (now using role **title** instead of ID)
            if (!$role) {
                throw new Exception("Role is required for non-students.");
            }

            $roleStmt = $conn->prepare("SELECT role_id FROM role WHERE role_title = :role");
            $roleStmt->execute([":role" => $role]);
            $roleData = $roleStmt->fetch(PDO::FETCH_ASSOC);
            $role_id = $roleData['role_id'] ?? null;

            if (!$role_id) {
                throw new Exception("Invalid role.");
            }

            // Insert into user table
            $stmt = $conn->prepare("INSERT INTO user (user_email, user_password, role_id, user_first_name, user_last_name, status) 
                                    VALUES (:email, :password, :role_id, :first_name, :last_name, :status)");
            $stmt->execute([
                ":email" => $email,
                ":password" => $hashed_password,
                ":role_id" => $role_id,
                ":first_name" => $first_name,
                ":last_name" => $last_name,
                ":status" => $status
            ]);
            $entity_id = $conn->lastInsertId();
        }

        // Insert into actor table
        $stmt = $conn->prepare("INSERT INTO actor (entity_type, entity_id) VALUES (:entity, :entity_id)");
        $stmt->execute([":entity" => $entity, ":entity_id" => $entity_id]);

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "User added successfully."]);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>


