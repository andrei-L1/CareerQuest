<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../config/dbcon.php';

if (!isset($_SESSION['stud_id'])) {
    header("Location: ../index.php");
    exit();
}

class StudentJobController {
    private $db;
    private $studentId;
    private $isStudent;

    public function __construct($dbConnection) {
        $this->db = $dbConnection;
        $this->studentId = $_SESSION['stud_id'];

        // Fetch is_student status
        $stmt = $this->db->prepare("SELECT is_student FROM student WHERE stud_id = :stud_id");
        $stmt->bindParam(':stud_id', $this->studentId, PDO::PARAM_INT);
        $stmt->execute();
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($student) {
            $this->isStudent = $student['is_student'];
        } else {
            throw new Exception("Student record not found");
        }
    }

    public function handleRequest() {
        $action = $_GET['action'] ?? '';

        try {
            switch ($action) {
                case 'get_all_jobs':
                    echo json_encode($this->getAllJobs());
                    break;
                case 'get_recommended_jobs':
                    echo json_encode($this->getRecommendedJobs());
                    break;
                case 'get_job_details':
                    if (!isset($_GET['job_id'])) {
                        throw new Exception("Job ID required");
                    }
                    echo json_encode($this->getJobDetails($_GET['job_id']));
                    break;
                case 'save_job':
                    $data = json_decode(file_get_contents('php://input'), true);
                    if (!isset($data['job_id'])) {
                        throw new Exception("Job ID required");
                    }
                    echo json_encode($this->saveJob($data['job_id']));
                    break;
                case 'unsave_job':
                    $data = json_decode(file_get_contents('php://input'), true);
                    if (!isset($data['job_id'])) {
                        throw new Exception("Job ID required");
                    }
                    echo json_encode($this->unsaveJob($data['job_id']));
                    break;
                default:
                    throw new Exception("Invalid action");
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function getAllJobs() {
        $query = "
            SELECT jp.job_id, jp.title, e.company_name AS company, 
                jt.job_type_title, jp.description, jp.location, 
                jp.min_salary, jp.max_salary, jp.salary_type, jp.salary_disclosure, 
                jp.posted_at, jp.expires_at,
                (SELECT COUNT(*) 
                    FROM application_tracking at 
                    WHERE at.job_id = jp.job_id AND at.stud_id = :stud_id) AS has_applied,
                (SELECT COUNT(*) 
                    FROM saved_jobs sj 
                    WHERE sj.job_id = jp.job_id AND sj.stud_id = :stud_id AND sj.deleted_at IS NULL) AS is_saved,
                GROUP_CONCAT(sm.category SEPARATOR ', ') AS categories
            FROM job_posting jp
            JOIN employer e ON jp.employer_id = e.employer_id
            JOIN job_type jt ON jp.job_type_id = jt.job_type_id
            LEFT JOIN job_skill js ON jp.job_id = js.job_id
            LEFT JOIN skill_masterlist sm ON js.skill_id = sm.skill_id
            WHERE jp.deleted_at IS NULL 
            AND jp.moderation_status = 'Approved'
            AND e.status = 'Active'
            AND (jp.expires_at >= CURDATE() OR jp.expires_at IS NULL)
            AND (jp.visible_to = 'both'
                OR (jp.visible_to = 'students' AND :is_student = 1)
                OR (jp.visible_to = 'applicants' AND :is_student = 0))
            GROUP BY jp.job_id
            ORDER BY jp.posted_at DESC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':stud_id', $this->studentId, PDO::PARAM_INT);
        $stmt->bindParam(':is_student', $this->isStudent, PDO::PARAM_BOOL);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getRecommendedJobs($category = "") {
        $query = "
            SELECT jp.job_id, jp.title, e.company_name AS company, 
                jt.job_type_title, jp.description, jp.location, 
                jp.min_salary, jp.max_salary, jp.salary_type, jp.salary_disclosure, 
                jp.posted_at, jp.expires_at,
                ROUND(AVG(CASE 
                    WHEN ss.proficiency = 'Advanced' AND js.importance = 'High' THEN 100
                    WHEN ss.proficiency = 'Advanced' AND js.importance = 'Medium' THEN 80
                    WHEN ss.proficiency = 'Intermediate' AND js.importance = 'High' THEN 80
                    WHEN ss.proficiency = 'Intermediate' AND js.importance = 'Medium' THEN 60
                    WHEN ss.proficiency = 'Beginner' AND js.importance = 'High' THEN 40
                    ELSE 20
                END), 0) AS match_score,
                GROUP_CONCAT(sm.category SEPARATOR ', ') AS categories
            FROM job_posting jp
            JOIN employer e ON jp.employer_id = e.employer_id
            JOIN job_type jt ON jp.job_type_id = jt.job_type_id
            JOIN job_skill js ON jp.job_id = js.job_id
            JOIN skill_masterlist sm ON js.skill_id = sm.skill_id
            JOIN stud_skill ss ON sm.skill_id = ss.skill_id
            WHERE jp.deleted_at IS NULL 
            AND jp.moderation_status = 'Approved'
            AND ss.stud_id = :stud_id
            AND (jp.visible_to = 'both'
                OR (jp.visible_to = 'students' AND :is_student = 1)
                OR (jp.visible_to = 'applicants' AND :is_student = 0))
        ";

        // Add category filter if specified
        if ($category !== "") {
            $query .= " AND sm.category = :category";
        }

        // Continue with the rest of the query
        $query .= "
            GROUP BY jp.job_id
            HAVING match_score > 30
            ORDER BY match_score DESC, jp.posted_at DESC
            LIMIT 20
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':stud_id', $this->studentId, PDO::PARAM_INT);
        $stmt->bindParam(':is_student', $this->isStudent, PDO::PARAM_BOOL);

        // Bind category if it exists
        if ($category !== "") {
            $stmt->bindParam(':category', $category);
        }

        $stmt->execute();
        
        $recommendedJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Insert skill match scores into skill_matching table
        foreach ($recommendedJobs as $job) {
            $jobId = $job['job_id'];
            
            $skillsQuery = "SELECT js.job_skills_id, sm.skill_id, ss.user_skills_id, 
                            ss.proficiency, js.importance
                            FROM job_skill js
                            JOIN skill_masterlist sm ON js.skill_id = sm.skill_id
                            JOIN stud_skill ss ON sm.skill_id = ss.skill_id
                            WHERE js.job_id = :job_id AND ss.stud_id = :stud_id";
            
            $skillsStmt = $this->db->prepare($skillsQuery);
            $skillsStmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
            $skillsStmt->bindParam(':stud_id', $this->studentId, PDO::PARAM_INT);
            $skillsStmt->execute();
            
            $skills = $skillsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($skills as $skill) {
                $matchScore = $this->calculateMatchScore($skill['proficiency'], $skill['importance']);
                
                // Check if the record already exists in the skill_matching table
                $checkQuery = "SELECT COUNT(*) FROM skill_matching
                            WHERE user_skills_id = :user_skills_id AND job_skills_id = :job_skills_id";
                $checkStmt = $this->db->prepare($checkQuery);
                $checkStmt->bindParam(':user_skills_id', $skill['user_skills_id'], PDO::PARAM_INT);
                $checkStmt->bindParam(':job_skills_id', $skill['job_skills_id'], PDO::PARAM_INT);
                $checkStmt->execute();
                
                $exists = $checkStmt->fetchColumn() > 0;
        
                if ($exists) {
                    // Update the match score if the record exists
                    $updateQuery = "UPDATE skill_matching
                                    SET match_score = :match_score
                                    WHERE user_skills_id = :user_skills_id AND job_skills_id = :job_skills_id";
                    $updateStmt = $this->db->prepare($updateQuery);
                    $updateStmt->bindParam(':user_skills_id', $skill['user_skills_id'], PDO::PARAM_INT);
                    $updateStmt->bindParam(':job_skills_id', $skill['job_skills_id'], PDO::PARAM_INT);
                    $updateStmt->bindParam(':match_score', $matchScore, PDO::PARAM_INT);
                    $updateStmt->execute();
                } else {
                    // Insert into skill_matching table if it doesn't exist
                    $insertQuery = "INSERT INTO skill_matching (user_skills_id, job_skills_id, match_score)
                                    VALUES (:user_skills_id, :job_skills_id, :match_score)";
                    $insertStmt = $this->db->prepare($insertQuery);
                    $insertStmt->bindParam(':user_skills_id', $skill['user_skills_id'], PDO::PARAM_INT);
                    $insertStmt->bindParam(':job_skills_id', $skill['job_skills_id'], PDO::PARAM_INT);
                    $insertStmt->bindParam(':match_score', $matchScore, PDO::PARAM_INT);
                    $insertStmt->execute();
                }
            }
        }

        return $recommendedJobs;
    }
    
    // Helper function to calculate match score
    private function calculateMatchScore($proficiency, $importance) {
        if ($proficiency == 'Advanced' && $importance == 'High') {
            return 100;
        } elseif ($proficiency == 'Advanced' && $importance == 'Medium') {
            return 80;
        } elseif ($proficiency == 'Intermediate' && $importance == 'High') {
            return 80;
        } elseif ($proficiency == 'Intermediate' && $importance == 'Medium') {
            return 60;
        } elseif ($proficiency == 'Beginner' && $importance == 'High') {
            return 40;
        } else {
            return 20;
        }
    }
    
    private function getJobDetails($jobId) {
        $query = "
            SELECT jp.*, e.company_name, jt.job_type_title
            FROM job_posting jp
            JOIN employer e ON jp.employer_id = e.employer_id
            JOIN job_type jt ON jp.job_type_id = jt.job_type_id
            WHERE jp.job_id = :job_id
            AND jp.deleted_at IS NULL
            AND jp.moderation_status = 'Approved'
            AND e.status = 'Active'
            AND (jp.expires_at >= CURDATE() OR jp.expires_at IS NULL)
            AND (jp.visible_to = 'both'
                OR (jp.visible_to = 'students' AND :is_student = 1)
                OR (jp.visible_to = 'applicants' AND :is_student = 0))
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
        $stmt->bindParam(':is_student', $this->isStudent, PDO::PARAM_BOOL);
        $stmt->execute();
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$job) {
            throw new Exception("Job not found or not accessible");
        }
        
        $query = "
            SELECT sm.skill_id, sm.skill_name, js.importance
            FROM job_skill js
            JOIN skill_masterlist sm ON js.skill_id = sm.skill_id
            WHERE js.job_id = :job_id AND js.deleted_at IS NULL
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
        $stmt->execute();
        $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($skills as &$skill) {
            $query = "SELECT proficiency FROM stud_skill 
                    WHERE skill_id = :skill_id AND stud_id = :stud_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':skill_id', $skill['skill_id'], PDO::PARAM_INT);
            $stmt->bindParam(':stud_id', $this->studentId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $skill['student_proficiency'] = $result ? $result['proficiency'] : null;
        }
        
        $query = "SELECT application_status FROM application_tracking
                WHERE job_id = :job_id AND stud_id = :stud_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':job_id', $jobId, PDO::PARAM_INT);
        $stmt->bindParam(':stud_id', $this->studentId, PDO::PARAM_INT);
        $stmt->execute();
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'job' => $job,
            'skills' => $skills,
            'application_status' => $application ? $application['application_status'] : null
        ];
    }

    private function saveJob($jobId) {
        // Check if the job is already saved
        $query = "SELECT saved_id, deleted_at FROM saved_jobs 
                  WHERE job_id = :job_id AND stud_id = :stud_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':job_id', $jobId);
        $stmt->bindParam(':stud_id', $this->studentId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($result) {
            if (is_null($result['deleted_at'])) {
                return ['success' => false, 'message' => 'Job already saved'];
            } else {
                $restoreQuery = "UPDATE saved_jobs 
                                 SET deleted_at = NULL, saved_at = NOW() 
                                 WHERE saved_id = :saved_id";
                $stmt = $this->db->prepare($restoreQuery);
                $stmt->bindParam(':saved_id', $result['saved_id']);
                $success = $stmt->execute();
    
                return [
                    'success' => $success,
                    'message' => $success ? 'Job re-saved successfully' : 'Failed to re-save job'
                ];
            }
        }
    
        // New save
        $insertQuery = "INSERT INTO saved_jobs (job_id, stud_id, saved_at) 
                        VALUES (:job_id, :stud_id, NOW())";
        $stmt = $this->db->prepare($insertQuery);
        $stmt->bindParam(':job_id', $jobId);
        $stmt->bindParam(':stud_id', $this->studentId);
        $success = $stmt->execute();
    
        return [
            'success' => $success,
            'message' => $success ? 'Job saved successfully' : 'Failed to save job'
        ];
    }

    private function unsaveJob($jobId) {
        // Check if the job is actually saved
        $query = "SELECT saved_id FROM saved_jobs 
                  WHERE job_id = :job_id AND stud_id = :stud_id AND deleted_at IS NULL";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':job_id', $jobId);
        $stmt->bindParam(':stud_id', $this->studentId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$result) {
            return ['success' => false, 'message' => 'Job not found in saved items'];
        }
    
        // Soft delete the saved job
        $updateQuery = "UPDATE saved_jobs 
                        SET deleted_at = NOW() 
                        WHERE saved_id = :saved_id";
        $stmt = $this->db->prepare($updateQuery);
        $stmt->bindParam(':saved_id', $result['saved_id']);
        $success = $stmt->execute();
    
        return [
            'success' => $success,
            'message' => $success ? 'Job unsaved successfully' : 'Failed to unearth job'
        ];
    }
}

$controller = new StudentJobController($conn);
$controller->handleRequest();
?>