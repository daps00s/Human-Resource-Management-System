<?php
require_once 'config.php';
require_once 'db_connection.php';
require_once 'phpmailer_config.php';

class UserFunctions {
    private $conn;
    
    public function __construct() {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }
    
    public function registerUser($data) {
        // Validate required fields
        $required = ['username', 'email', 'password', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'];
            }
        }
        
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // Validate password strength
        $password_errors = $this->validatePasswordStrength($data['password']);
        if (!empty($password_errors)) {
            return ['success' => false, 'message' => implode(' ', $password_errors)];
        }
        
        // Check if user exists
        $check_stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check_stmt->bind_param("ss", $data['username'], $data['email']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Generate employee ID if not provided
        if (empty($data['employee_id'])) {
            $data['employee_id'] = $this->generateEmployeeId();
        }
        
        // Hash password
        $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        
        // Insert user
        $insert_stmt = $this->conn->prepare("INSERT INTO users (username, email, password, first_name, last_name, employee_id, department, position, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $role = $data['role'] ?? 'employee';
        $insert_stmt->bind_param("sssssssss", 
            $data['username'], 
            $data['email'], 
            $hashed_password,
            $data['first_name'],
            $data['last_name'],
            $data['employee_id'],
            $data['department'],
            $data['position'],
            $role
        );
        
        if ($insert_stmt->execute()) {
            return ['success' => true, 'message' => 'Registration successful! Please login.'];
        } else {
            return ['success' => false, 'message' => 'Registration failed: ' . $this->conn->error];
        }
    }
    
    private function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }
        
        return $errors;
    }
    
    private function generateEmployeeId() {
        $year = date('Y');
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM users WHERE employee_id LIKE ?");
        $prefix = $year . '%';
        $stmt->bind_param("s", $prefix);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        $number = str_pad($result['count'] + 1, 4, '0', STR_PAD_LEFT);
        return $year . $number;
    }
    
    public function loginUser($username, $password) {
        $auth = new AuthCheck();
        
        // Check login attempts
        if ($auth->checkLoginAttempts($username)) {
            return ['success' => false, 'message' => 'Too many failed attempts. Please try again after 15 minutes.'];
        }
        
        if (empty($username) || empty($password)) {
            $auth->logLoginAttempt($username, false);
            return ['success' => false, 'message' => 'Username and password are required'];
        }
        
        $stmt = $this->conn->prepare("SELECT id, username, email, password, first_name, last_name, role FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Check if password needs rehash
                if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST])) {
                    $this->updatePasswordHash($user['id'], $password);
                }
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                $auth->logLoginAttempt($username, true);
                
                return ['success' => true, 'message' => 'Login successful'];
            }
        }
        
        $auth->logLoginAttempt($username, false);
        return ['success' => false, 'message' => 'Invalid username/email or password'];
    }
    
    private function updatePasswordHash($user_id, $password) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        $stmt->execute();
    }
    
    /**
     * Step 1: Verify username and email combination and send verification code
     */
    public function verifyUserAndSendCode($username, $email) {
        // Validate inputs
        if (empty($username) || empty($email)) {
            return ['success' => false, 'message' => 'Please enter both username and email address.'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        }
        
        // Check if user exists with matching username and email
        $stmt = $this->conn->prepare("SELECT id, username, email, first_name, last_name FROM users WHERE username = ? AND email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'No account found with this username and email combination.'];
        }
        
        $user = $result->fetch_assoc();
        $full_name = trim($user['first_name'] . ' ' . $user['last_name']);
        if (empty($full_name)) {
            $full_name = $user['username'];
        }
        
        // Generate verification code
        $verification_code = sprintf("%06d", mt_rand(1, 999999));
        $expiry_time = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        // Save verification code in session
        $_SESSION['password_reset'] = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $full_name,
            'code' => $verification_code,
            'expiry' => $expiry_time,
            'attempts' => 0,
            'verified' => false
        ];
        
        // Send email with verification code
        $email_sent = sendVerificationCodeEmail($email, $verification_code, $full_name);
        
        if ($email_sent) {
            return ['success' => true, 'message' => 'Verification code has been sent to your email.'];
        } else {
            return ['success' => false, 'message' => 'Failed to send verification code. Please try again later.'];
        }
    }
    
    /**
     * Step 2: Verify the code entered by user
     */
    public function verifyCode($input_code) {
        // Check if reset session exists
        if (!isset($_SESSION['password_reset'])) {
            return ['success' => false, 'message' => 'Password reset session expired. Please start over.', 'redirect' => 'forgot_password.php'];
        }
        
        $reset_data = $_SESSION['password_reset'];
        
        // Check expiry
        if (strtotime($reset_data['expiry']) < time()) {
            unset($_SESSION['password_reset']);
            return ['success' => false, 'message' => 'Verification code has expired. Please request a new one.', 'redirect' => 'forgot_password.php'];
        }
        
        // Check attempts
        if ($reset_data['attempts'] >= 3) {
            unset($_SESSION['password_reset']);
            return ['success' => false, 'message' => 'Too many incorrect attempts. Please request a new code.', 'redirect' => 'forgot_password.php'];
        }
        
        // Check code
        if ($input_code != $reset_data['code']) {
            $_SESSION['password_reset']['attempts']++;
            $attempts_left = 3 - $_SESSION['password_reset']['attempts'];
            return ['success' => false, 'message' => "Invalid verification code. {$attempts_left} attempt(s) left."];
        }
        
        // Code verified successfully
        $_SESSION['password_reset']['verified'] = true;
        
        return ['success' => true, 'message' => 'Code verified successfully', 'redirect' => 'reset_password.php'];
    }
    
    /**
     * Step 3: Reset password
     */
    public function resetPassword($new_password, $confirm_password) {
        // Check if reset session exists and is verified
        if (!isset($_SESSION['password_reset']) || !isset($_SESSION['password_reset']['verified'])) {
            return ['success' => false, 'message' => 'Please verify your identity first.', 'redirect' => 'forgot_password.php'];
        }
        
        // Validate passwords
        if (empty($new_password) || empty($confirm_password)) {
            return ['success' => false, 'message' => 'Please enter and confirm your new password'];
        }
        
        if (strlen($new_password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters long'];
        }
        
        if ($new_password !== $confirm_password) {
            return ['success' => false, 'message' => 'Passwords do not match'];
        }
        
        $reset_data = $_SESSION['password_reset'];
        $user_id = $reset_data['user_id'];
        
        // Update password in database
        try {
            // Hash the password
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            
            // Update the password
            $update_stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($update_stmt->execute()) {
                // Clear session
                unset($_SESSION['password_reset']);
                
                return ['success' => true, 'message' => 'Password reset successfully! Please login with your new password.', 'redirect' => 'login.php'];
            } else {
                return ['success' => false, 'message' => 'Failed to update password. Please try again.'];
            }
            
        } catch (Exception $e) {
            error_log("Reset password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error occurred. Please try again.'];
        }
    }
    
    /**
     * Resend verification code
     */
    public function resendVerificationCode() {
        if (!isset($_SESSION['password_reset'])) {
            return ['success' => false, 'message' => 'Password reset session expired.', 'redirect' => 'forgot_password.php'];
        }
        
        $reset_data = $_SESSION['password_reset'];
        
        // Generate new verification code
        $verification_code = sprintf("%06d", mt_rand(1, 999999));
        $expiry_time = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        // Update session
        $_SESSION['password_reset']['code'] = $verification_code;
        $_SESSION['password_reset']['expiry'] = $expiry_time;
        $_SESSION['password_reset']['attempts'] = 0;
        
        // Send email
        $email_sent = sendVerificationCodeEmail($reset_data['email'], $verification_code, $reset_data['full_name'], true);
        
        if ($email_sent) {
            return ['success' => true, 'message' => 'New verification code has been sent to your email.'];
        } else {
            return ['success' => false, 'message' => 'Failed to send verification code. Please try again.'];
        }
    }
}
?>