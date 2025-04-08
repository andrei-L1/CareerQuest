<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../config/dbcon.php';

if (!isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit();
}

class StudentSkillsController {
    private $db;
    private $studentId;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
        $this->studentId = $_SESSION['stud_id']; // Get student ID from session
    }

    public function handleRequest() {
        $action = $_GET['action'] ?? '';

        try {
            switch ($action) {
                case 'get_student_skills':
                    echo json_encode($this->getStudentSkills());
                    break;
                default:
                    throw new Exception("Invalid action");
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function getStudentSkills() {
        $query = "SELECT ss.user_skills_id, sm.skill_id, sm.skill_name, ss.proficiency
                 FROM stud_skill ss
                 JOIN skill_masterlist sm ON ss.skill_id = sm.skill_id
                 WHERE ss.stud_id = :stud_id AND ss.deleted_at IS NULL";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':stud_id', $this->studentId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Create controller instance with database connection and handle request
$controller = new StudentSkillsController($conn);
$controller->handleRequest();
?>