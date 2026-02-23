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
$token = isset($_GET['token']) ? $_GET['token'] : '';

$functions = new UserFunctions();

// Validate token
if (!empty($token) && !$functions->validateResetToken($token)) {
    $error = 'Invalid or expired reset link. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $functions->resetPassword($_POST['token'], $_POST['password']);
    
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
    <title>Reset Password - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/reset_password.css">
</head>
<body>
    <div class="container">
        <div class="reset-container">
            <div class="logo">
                <h1><?php echo SITE_NAME; ?></h1>
            </div>
            <h2>Reset Password</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (empty($error) && empty($success) && !empty($token)): ?>
                <form method="POST" action="" id="resetForm" autocomplete="off">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter new password" required>
                        <div class="password-requirements">
                            <small>Password must contain: 8+ chars, uppercase, lowercase, number, special char</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                    </div>
                    
                    <button type="submit" class="btn-reset">Reset Password</button>
                </form>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="links">
                    <a href="login.php" class="btn-login-link">Go to Login</a>
                </div>
            <?php elseif (empty($error) && empty($token)): ?>
                <div class="error-message">No reset token provided.</div>
                <div class="links">
                    <a href="forgot_password.php">Request New Reset Link</a>
                </div>
            <?php elseif ($error): ?>
                <div class="links">
                    <a href="forgot_password.php">Request New Reset Link</a>
                    <a href="login.php">Back to Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="assets/js/reset_password.js"></script>
</body>
</html>