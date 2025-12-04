<?php
// employer_auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include your existing database connection
require_once '../config/dbcon.php';
/** @var PDO $conn */
class EmployerAuth {
    private $conn;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }
    
    // Employer login function
    public function login($email, $password) {
        try {
            // 1. Get user by email
            $user = $this->getUserByEmail($email);
            
            if (!$user) {
                throw new Exception('Invalid email or password');
            }
            
            // 2. Verify password
            if (!password_verify($password, $user['user_password'])) {
                throw new Exception('Invalid email or password');
            }
            
            // 3. Check if user is deleted
            if ($user['deleted_at'] !== null) {
                throw new Exception('Your account has been deactivated');
            }
            
            // 4. Check user status
            if ($user['status'] !== 'active') {
                throw new Exception('Your account is not active');
            }
            
            // 5. Get employer record
            $employer = $this->getEmployerByUserId($user['user_id']);
            
            if (!$employer) {
                throw new Exception('No employer profile found');
            }
            
            // 6. Check employer status
            if (!in_array($employer['status'], ['Verification', 'Active'])) {
                throw new Exception('Employer account is not authorized');
            }
            
            // 7. Get role information
            $role = $this->getRoleById($user['role_id']);
            
            if (!$role || stripos($role['role_title'], 'employer') === false) {
                throw new Exception('Invalid access privileges');
            }
            
            // Create session
            $this->createSession($user, $employer, $role);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Validate session on each request
    public function validateSession() {
        if (!isset($_SESSION['employer_session'])) {
            $this->logout();
            throw new Exception('Session expired');
        }
        
        $session = $_SESSION['employer_session'];
        
        // Validate session structure
        $requiredFields = ['user_id', 'role_id', 'employer_id', 'session_token', 'last_activity'];
        foreach ($requiredFields as $field) {
            if (!isset($session[$field])) {
                $this->logout();
                throw new Exception('Invalid session');
            }
        }
        
        // Check session timeout (30 minutes)
        if (time() - $session['last_activity'] > 1800) {
            $this->logout();
            throw new Exception('Session expired');
        }
        
        // Get current user data
        $user = $this->getUserById($session['user_id']);
        
        // Validate user
        if (!$user || 
            $user['deleted_at'] !== null || 
            $user['status'] !== 'active' || 
            $user['role_id'] !== $session['role_id']) {
            $this->logout();
            throw new Exception('Account validation failed');
        }
        
        // Validate employer
        $employer = $this->getEmployerById($session['employer_id']);
        
        if (!$employer || 
            $employer['user_id'] !== $session['user_id'] || 
            !in_array($employer['status'], ['Verification', 'Active'])) {
            $this->logout();
            throw new Exception('Employer validation failed');
        }
        
        // Validate session token
        if (!$this->validateSessionToken($session['user_id'], $session['session_token'])) {
            $this->logout();
            throw new Exception('Security token mismatch');
        }
        
        // Update last activity time
        $_SESSION['employer_session']['last_activity'] = time();
        
        return true;
    }
    
    public function logout() {
        // Unset all session variables
        $_SESSION = array();
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
    }
    
    // Database methods
    private function getUserByEmail($email) {
        $stmt = $this->conn->prepare("
            SELECT * FROM user 
            WHERE user_email = :email
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getUserById($userId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM user 
            WHERE user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getEmployerByUserId($userId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM employer 
            WHERE user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getEmployerById($employerId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM employer 
            WHERE employer_id = :employer_id
            LIMIT 1
        ");
        $stmt->execute([':employer_id' => $employerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getRoleById($roleId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM role 
            WHERE role_id = :role_id 
            AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':role_id' => $roleId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Session methods
    private function createSession($user, $employer, $role) {
        $_SESSION['employer_session'] = [
            'user_id' => $user['user_id'],
            'email' => $user['user_email'],
            'role_id' => $user['role_id'],
            'employer_id' => $employer['employer_id'],
            'company_name' => $employer['company_name'],
            'session_token' => $this->generateSessionToken($user['user_id']),
            'last_activity' => time(),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ];
    }
    
    private function generateSessionToken($userId) {
        return hash('sha256', 
            $userId . 
            $_SERVER['HTTP_USER_AGENT'] . 
            $_SERVER['REMOTE_ADDR'] . 
            microtime()
        );
    }
    
    private function validateSessionToken($userId, $token) {
        $expected = hash('sha256', 
            $userId . 
            $_SERVER['HTTP_USER_AGENT'] . 
            $_SERVER['REMOTE_ADDR'] . 
            $_SESSION['employer_session']['last_activity']
        );
        
        return hash_equals($expected, $token);
    }
    
    // Getters for session data
    public function getUserId() {
        return $_SESSION['employer_session']['user_id'] ?? null;
    }
    
    public function getEmployerId() {
        return $_SESSION['employer_session']['employer_id'] ?? null;
    }
    
    public function getCompanyName() {
        return $_SESSION['employer_session']['company_name'] ?? null;
    }
}

// Initialize with your existing connection
$employerAuth = new EmployerAuth($conn);
?>