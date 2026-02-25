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
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    $result = $functions->verifyUserAndSendCode($username, $email);
    
    if ($result['success']) {
        // Redirect to verify code page
        header("Location: verify_code.php");
        exit();
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
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/forgot_password.css">
</head>
<body>
    <div class="container">
        <div class="forgot-container">
            <div class="logo">
                <img src="assets/images/hr-logo.png" alt="<?php echo SITE_NAME; ?>" class="logo-image">
                <h1><?php echo SITE_NAME; ?></h1>
            </div>
            <h2>Forgot Password</h2>
            <p class="instruction">Enter your username and email address to receive a verification code.</p>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="forgotForm" autocomplete="off">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                           placeholder="Enter your username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           placeholder="Enter your registered email" required>
                    <small style="display: block; color: #666; font-size: 12px; margin-top: 5px;">Must match the email associated with your account</small>
                </div>
                
                <button type="submit" class="btn-submit">Send Verification Code</button>
            </form>
            
            <div class="links">
                <a href="login.php" class="back-link">← Back to Login</a>
            </div>
        </div>
    </div>
    
    <script src="assets/js/forgot_password.js"></script>
</body>
</html>