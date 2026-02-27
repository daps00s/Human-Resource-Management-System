<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_office'])) {
    $id = (int)$_POST['id'];
    $confirm = $_POST['confirm_delete'] ?? '';
    
    if ($confirm !== 'confirm') {
        header("Location: offices.php?error=" . urlencode("Please type 'confirm' to delete the office"));
        exit();
    }
    
    // Check if office has employees
    $check_employees = $db->query("SELECT id FROM employee_employment WHERE office_id = $id LIMIT 1");
    if ($check_employees->num_rows > 0) {
        header("Location: offices.php?error=" . urlencode("Cannot delete office with assigned employees"));
        exit();
    }
    
    // Get office logo to delete file
    $office = $db->query("SELECT logo FROM offices WHERE id = $id")->fetch_assoc();
    
    if ($office) {
        // Delete logo file if exists
        if (!empty($office['logo'])) {
            $logo_path = "uploads/offices/" . $office['logo'];
            if (file_exists($logo_path)) {
                unlink($logo_path);
            }
        }
        
        // Delete office from database
        $db->query("DELETE FROM offices WHERE id = $id");
        header("Location: offices.php?success=" . urlencode("Office deleted successfully"));
        exit();
    } else {
        header("Location: offices.php?error=" . urlencode("Office not found"));
        exit();
    }
} else {
    header("Location: offices.php");
    exit();
}
?>