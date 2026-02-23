<?php
require_once 'config.php';
require_once 'db_connection.php';
require_once 'vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class UserFunctions {
    private $db;
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
    
    public function sendPasswordResetCode($email) {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Valid email is required'];
        }
        
        // Check if email exists
        $stmt = $this->conn->prepare("SELECT id, username, first_name, email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Don't reveal that email doesn't exist for security
            return ['success' => true, 'message' => 'If the email exists in our system, a reset link has been sent'];
        }
        
        $user = $result->fetch_assoc();
        
        // Generate reset token
        $reset_token = bin2hex(random_bytes(32));
        $reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Begin transaction
        $this->conn->begin_transaction();
        
        try {
            // Store token in password_resets table
            $insert_stmt = $this->conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("iss", $user['id'], $reset_token, $reset_expires);
            
            if (!$insert_stmt->execute()) {
                throw new Exception("Failed to create reset token");
            }
            
            // Update users table with token (for backward compatibility)
            $update_stmt = $this->conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $update_stmt->bind_param("ssi", $reset_token, $reset_expires, $user['id']);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update user token");
            }
            
            $this->conn->commit();
            
            // Send email using PHPMailer
            if ($this->sendResetEmail($user, $reset_token)) {
                return ['success' => true, 'message' => 'Password reset link has been sent to your email'];
            } else {
                throw new Exception("Failed to send email");
            }
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Password reset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to process request. Please try again later.'];
        }
    }
    
    private function sendResetEmail($user, $token) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Recipients
            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($user['email'], $user['first_name'] ?: $user['username']);
            $mail->addReplyTo(SMTP_FROM, SMTP_FROM_NAME);
            
            // Content
            $reset_link = BASE_URL . "reset_password.php?token=" . $token;
            
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request - ' . SITE_NAME;
            $mail->Body    = $this->getResetEmailTemplate($user, $reset_link);
            $mail->AltBody = $this->getResetEmailPlainText($user, $reset_link);
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    private function getResetEmailTemplate($user, $reset_link) {
        $name = $user['first_name'] ?: $user['username'];
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f9f9f9; }
                .button { display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>" . SITE_NAME . "</h2>
                </div>
                <div class='content'>
                    <h3>Hello {$name},</h3>
                    <p>We received a request to reset your password. Click the button below to create a new password:</p>
                    <p style='text-align: center;'>
                        <a href='{$reset_link}' class='button'>Reset Password</a>
                    </p>
                    <p>This link will expire in <strong>1 hour</strong>.</p>
                    <p>If you didn't request this, please ignore this email or contact your HR administrator.</p>
                    <p>For security, please don't forward this email to anyone.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getResetEmailPlainText($user, $reset_link) {
        $name = $user['first_name'] ?: $user['username'];
        return "Hello {$name},\n\n" .
               "We received a request to reset your password. Use the link below to create a new password:\n\n" .
               "{$reset_link}\n\n" .
               "This link will expire in 1 hour.\n\n" .
               "If you didn't request this, please ignore this email.\n\n" .
               "Thank you,\n" . SITE_NAME;
    }
    
    public function validateResetToken($token) {
        if (empty($token)) {
            return false;
        }
        
        // Check in password_resets table first
        $stmt = $this->conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW() AND used_at IS NULL");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return true;
        }
        
        // Fallback to users table
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
    
    public function resetPassword($token, $new_password) {
        if (empty($token) || empty($new_password)) {
            return ['success' => false, 'message' => 'Token and password are required'];
        }
        
        // Validate password strength
        $password_errors = $this->validatePasswordStrength($new_password);
        if (!empty($password_errors)) {
            return ['success' => false, 'message' => implode(' ', $password_errors)];
        }
        
        // Validate token
        if (!$this->validateResetToken($token)) {
            return ['success' => false, 'message' => 'Invalid or expired token'];
        }
        
        // Begin transaction
        $this->conn->begin_transaction();
        
        try {
            // Get user ID from token
            $user_id = null;
            
            // Check password_resets table
            $stmt = $this->conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW() AND used_at IS NULL");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $user_id = $row['user_id'];
                
                // Mark token as used
                $update_stmt = $this->conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE token = ?");
                $update_stmt->bind_param("s", $token);
                $update_stmt->execute();
            } else {
                // Fallback to users table
                $stmt = $this->conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $user_id = $row['id'];
            }
            
            if (!$user_id) {
                throw new Exception("User not found");
            }
            
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            $update_stmt = $this->conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update password");
            }
            
            $this->conn->commit();
            
            // Send confirmation email
            $this->sendPasswordChangedConfirmation($user_id);
            
            return ['success' => true, 'message' => 'Password has been reset successfully. Please login with your new password.'];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Password reset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reset password. Please try again.'];
        }
    }
    
    private function sendPasswordChangedConfirmation($user_id) {
        // Get user email
        $stmt = $this->conn->prepare("SELECT email, first_name FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USER;
                $mail->Password = SMTP_PASS;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = SMTP_PORT;
                
                $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
                $mail->addAddress($user['email']);
                
                $mail->isHTML(true);
                $mail->Subject = 'Your password has been changed - ' . SITE_NAME;
                $mail->Body = "
                    <h3>Password Changed Successfully</h3>
                    <p>Hello " . ($user['first_name'] ?: 'User') . ",</p>
                    <p>Your password has been successfully changed.</p>
                    <p>If you did not make this change, please contact your HR administrator immediately.</p>
                ";
                
                $mail->send();
            } catch (Exception $e) {
                error_log("Password change confirmation email failed: " . $e->getMessage());
            }
        }
    }
}
?>