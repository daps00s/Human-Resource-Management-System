<?php
// Database configuration for Human Resource Management System
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'human_resources_management_system');

// Application configuration - UPDATED to include /GitHub/ in the path
define('BASE_URL', 'http://localhost/GitHub/Human-Resource-Management-System/');
define('SITE_NAME', 'Human Resource Management System');

// Email configuration (for PHPMailer)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'karlpatrickmandapat940@gmail.com'); // Replace with your HR email
define('SMTP_PASS', 'tgbyxgqcifynegg'); // Replace with your app password
define('SMTP_FROM', 'noreply@hrms.com');
define('SMTP_FROM_NAME', 'HRMS Support');

// Security settings
define('BCRYPT_COST', 12);
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Strict');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone
date_default_timezone_set('Asia/Manila'); // Adjust based on your location

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");
?>