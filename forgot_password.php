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
    $result = $functions->sendPasswordResetCode($_POST['email']);
    
    if ($result['success']) {
        $success = $result['message'];
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
                <h1><?php echo SITE_NAME; ?></h1>
            </div>
            <h2>Forgot Password</h2>
            <p class="instruction">Enter your email address and we'll send you a link to reset your password.</p>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="forgotForm" autocomplete="off">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your registered email" required>
                </div>
                
                <button type="submit" class="btn-submit">Send Reset Link</button>
            </form>
            
            <div class="links">
                <a href="login.php" class="back-link">← Back to Login</a>
            </div>
        </div>
    </div>
    
    <script src="assets/js/forgot_password.js"></script>
</body>
</html>