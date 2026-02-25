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

// Check if reset session exists and is verified
if (!isset($_SESSION['password_reset']) || !isset($_SESSION['password_reset']['verified'])) {
    header("Location: forgot_password.php");
    exit();
}

$error = '';
$success = '';

$functions = new UserFunctions();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $result = $functions->resetPassword($new_password, $confirm_password);
    
    if ($result['success']) {
        $success = $result['message'];
        // Redirect after 2 seconds
        echo '<meta http-equiv="refresh" content="2;url=' . $result['redirect'] . '">';
    } else {
        $error = $result['message'];
    }
}

$reset_data = $_SESSION['password_reset'] ?? null;
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
                <img src="assets/images/hr-logo.png" alt="<?php echo SITE_NAME; ?>" class="logo-image">
                <h1><?php echo SITE_NAME; ?></h1>
            </div>
            <h2>Reset Password</h2>
            
            <?php if ($reset_data): ?>
            <div class="user-info">
                <p><i class="fas fa-user"></i> User: <?php echo htmlspecialchars($reset_data['username']); ?></p>
                <p><i class="fas fa-envelope"></i> Email: <?php echo htmlspecialchars($reset_data['email']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (empty($success)): ?>
            <form method="POST" action="" id="resetForm" autocomplete="off">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" 
                           placeholder="Enter new password" minlength="6" required>
                    <div class="password-requirements">
                        <small>Password must be at least 6 characters long</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           placeholder="Confirm new password" minlength="6" required>
                    <div id="password-match" class="password-match"></div>
                </div>
                
                <button type="submit" name="reset_password" class="btn-reset">Reset Password</button>
            </form>
            <?php endif; ?>
            
            <div class="links">
                <a href="login.php" class="back-link">← Back to Login</a>
            </div>
        </div>
    </div>
    
    <script src="assets/js/reset_password.js"></script>
</body>
</html>