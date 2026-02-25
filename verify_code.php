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

// Check if reset session exists
if (!isset($_SESSION['password_reset'])) {
    header("Location: forgot_password.php");
    exit();
}

$error = '';
$success = '';

$functions = new UserFunctions();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_code'])) {
        $input_code = trim($_POST['verification_code'] ?? '');
        
        $result = $functions->verifyCode($input_code);
        
        if ($result['success']) {
            header("Location: " . $result['redirect']);
            exit();
        } else {
            $error = $result['message'];
            if (isset($result['redirect'])) {
                // If session expired, redirect after showing message
                echo '<meta http-equiv="refresh" content="2;url=' . $result['redirect'] . '">';
            }
        }
    } elseif (isset($_POST['resend_code'])) {
        $result = $functions->resendVerificationCode();
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
            if (isset($result['redirect'])) {
                echo '<meta http-equiv="refresh" content="2;url=' . $result['redirect'] . '">';
            }
        }
    }
}

$reset_data = $_SESSION['password_reset'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/verify_code.css">
</head>
<body>
    <div class="container">
        <div class="verify-container">
            <div class="logo">
                <img src="assets/images/hr-logo.png" alt="<?php echo SITE_NAME; ?>" class="logo-image">
                <h1><?php echo SITE_NAME; ?></h1>
            </div>
            <h2>Verify Code</h2>
            
            <?php if ($reset_data): ?>
            <div class="user-info">
                <p><i class="fas fa-user"></i> Username: <?php echo htmlspecialchars($reset_data['username']); ?></p>
                <p><i class="fas fa-envelope"></i> Code sent to: <?php echo htmlspecialchars($reset_data['email']); ?></p>
                <p class="expiry"><i class="fas fa-clock"></i> Code expires at: <?php echo date('h:i A', strtotime($reset_data['expiry'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="verifyForm" autocomplete="off">
                <div class="form-group">
                    <label for="verification_code">Verification Code</label>
                    <input type="text" id="verification_code" name="verification_code" 
                           placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]{6}" 
                           inputmode="numeric" autocomplete="off" required>
                    <small style="display: block; color: #666; font-size: 12px; margin-top: 5px;">Enter the 6-digit code sent to your email</small>
                </div>
                
                <?php if ($reset_data): ?>
                <div class="attempts-info">
                    <p style="color: #666; font-size: 13px; text-align: right;">
                        Attempts: <?php echo $reset_data['attempts']; ?>/3 remaining
                    </p>
                </div>
                <?php endif; ?>
                
                <div class="button-group">
                    <button type="submit" name="verify_code" class="btn-verify">Verify Code</button>
                    <button type="submit" name="resend_code" class="btn-resend">Resend Code</button>
                </div>
            </form>
            
            <div class="links">
                <a href="forgot_password.php" class="back-link">← Back to Forgot Password</a>
                <a href="login.php" class="login-link">Back to Login</a>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-submit when 6 digits are entered
        document.getElementById('verification_code').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length === 6) {
                // Optional: auto-submit
                // document.querySelector('button[name="verify_code"]').click();
            }
        });
    </script>
    <script src="assets/js/verify_code.js"></script>
</body>
</html>