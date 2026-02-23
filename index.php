<?php
require_once 'includes/auth_check.php';
$auth = new AuthCheck();

if ($auth->isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>