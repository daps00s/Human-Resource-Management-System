<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_position'])) {
    $id = (int)$_POST['id'];
    $confirm = $_POST['confirm_delete'] ?? '';
    
    if ($confirm !== 'confirm') {
        header("Location: positions.php?error=" . urlencode("Please type 'confirm' to delete the position"));
        exit();
    }
    
    // Check if position has child positions
    $check_children = $db->query("SELECT id FROM positions WHERE parent_position_id = $id LIMIT 1");
    if ($check_children->num_rows > 0) {
        header("Location: positions.php?error=" . urlencode("Cannot delete position with subordinate positions"));
        exit();
    }
    
    // Check if position has employees assigned
    $check_employees = $db->query("SELECT id FROM employee_employment WHERE position = 
                                   (SELECT position_title FROM positions WHERE id = $id) LIMIT 1");
    if ($check_employees->num_rows > 0) {
        header("Location: positions.php?error=" . urlencode("Cannot delete position with assigned employees"));
        exit();
    }
    
    // Delete position
    $db->query("DELETE FROM positions WHERE id = $id");
    header("Location: positions.php?success=" . urlencode("Position deleted successfully"));
    exit();
} else {
    header("Location: positions.php");
    exit();
}
?>