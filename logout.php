<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';

$auth = new AuthCheck();
$auth->logout();
?>