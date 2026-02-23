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
$timeout = isset($_GET['timeout']) ? 'Your session has expired. Please login again.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $functions = new UserFunctions();
    $result = $functions->loginUser($_POST['username'], $_POST['password']);
    
    if ($result['success']) {
        // Check for redirect URL
        $redirect = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'dashboard.php';
        unset($_SESSION['redirect_url']);
        header("Location: " . $redirect);
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
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="logo">
                <!-- Logo Image - 50x50 circle -->
                <img src="assets/images/hr-logo.png" alt="<?php echo SITE_NAME; ?>" class="logo-image">
                <h1><?php echo SITE_NAME; ?></h1>
            </div>
            
            <?php if ($timeout): ?>
                <div class="info-message"><?php echo htmlspecialchars($timeout); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm" autocomplete="off">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username or email" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn-login">Sign In</button>
            </form>
            
        </div>
    </div>
    
    <script src="assets/js/login.js"></script>
</body>
</html>