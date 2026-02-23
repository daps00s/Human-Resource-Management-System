<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

$auth = new AuthCheck();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $functions = new UserFunctions();
    $result = $functions->registerUser($_POST);
    
    if ($result['success']) {
        $success = $result['message'];
        // Clear form data on success
        $_POST = array();
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/register.css">
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="logo">
                <h1><?php echo SITE_NAME; ?></h1>
            </div>
            <h2>Create New Account</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm" autocomplete="off">
                <div class="form-row">
                    <div class="form-group half">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group half">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="username">Username <span class="required">*</span></label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="department">Department</label>
                        <select id="department" name="department">
                            <option value="">Select Department</option>
                            <option value="IT">IT</option>
                            <option value="HR">Human Resources</option>
                            <option value="Finance">Finance</option>
                            <option value="Marketing">Marketing</option>
                            <option value="Sales">Sales</option>
                            <option value="Operations">Operations</option>
                        </select>
                    </div>
                    
                    <div class="form-group half">
                        <label for="position">Position</label>
                        <input type="text" id="position" name="position" value="<?php echo htmlspecialchars($_POST['position'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required>
                    <div class="password-requirements">
                        <small>Password must contain: 8+ chars, uppercase, lowercase, number, special char</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group terms">
                    <label>
                        <input type="checkbox" name="terms" required> I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" class="btn-register">Create Account</button>
            </form>
            
            <div class="links">
                <p>Already have an account? <a href="login.php">Sign in</a></p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/register.js"></script>
</body>
</html>