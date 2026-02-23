<?php
require_once 'config.php';
require_once 'db_connection.php';

class AuthCheck {
    private $db;
    private $conn;
    private $user = null;
    
    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
        
        // Auto-check login status on construct
        $this->checkSessionTimeout();
    }
    
    private function checkSessionTimeout() {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            $this->logout(true); // timeout logout
        }
        $_SESSION['last_activity'] = time();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header("Location: " . BASE_URL . "login.php");
            exit();
        }
    }
    
    public function requireRole($allowed_roles) {
        $this->requireLogin();
        
        if (!is_array($allowed_roles)) {
            $allowed_roles = [$allowed_roles];
        }
        
        $user = $this->getCurrentUser();
        if (!in_array($user['role'], $allowed_roles)) {
            header("Location: " . BASE_URL . "dashboard.php?error=unauthorized");
            exit();
        }
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        if ($this->user !== null) {
            return $this->user;
        }
        
        $user_id = $_SESSION['user_id'];
        $stmt = $this->conn->prepare("SELECT id, username, email, first_name, last_name, employee_id, department, position, role, created_at FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $this->user = $result->fetch_assoc();
            return $this->user;
        }
        
        return null;
    }
    
    public function getUserFullName() {
        $user = $this->getCurrentUser();
        if ($user) {
            return $user['first_name'] . ' ' . $user['last_name'];
        }
        return '';
    }
    
    public function logout($timeout = false) {
        // Log logout activity if needed
        if ($this->isLoggedIn()) {
            // You can add logout logging here
        }
        
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        if ($timeout) {
            header("Location: " . BASE_URL . "login.php?timeout=1");
        } else {
            header("Location: " . BASE_URL . "login.php");
        }
        exit();
    }
    
    public function checkLoginAttempts($username) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $lockout_time = LOCKOUT_TIME; // Store in variable to avoid constant in bind_param
        
        $stmt = $this->conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE ip_address = ? AND username = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND) AND success = 0");
        
        if ($stmt === false) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("ssi", $ip, $username, $lockout_time);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['attempts'] >= MAX_LOGIN_ATTEMPTS;
    }
    
    public function logLoginAttempt($username, $success) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $success_int = $success ? 1 : 0; // Convert boolean to integer
        
        $stmt = $this->conn->prepare("INSERT INTO login_attempts (ip_address, username, success) VALUES (?, ?, ?)");
        
        if ($stmt === false) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }
        
        $stmt->bind_param("ssi", $ip, $username, $success_int);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
}
?>