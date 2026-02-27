<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Check if sss_number column exists
    $check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'sss_number'");
    $has_sss = $check_column->num_rows > 0;
    
    $sql = "
        SELECT 
            u.id,
            u.employee_id,
            u.first_name,
            u.last_name,
            u.gsis_number,
            u.pagibig_number,
            u.philhealth_number,
            u.tin_number" .
            ($has_sss ? ", u.sss_number" : "") . ",
            ee.position,
            o.office_name,
            ee.monthly_salary,
            sg.salary_grade,
            ee.step
        FROM users u
        LEFT JOIN employee_employment ee ON u.id = ee.user_id
        LEFT JOIN salary_grades sg ON ee.salary_grade_id = sg.id
        LEFT JOIN offices o ON ee.office_id = o.id
        WHERE u.id = ? AND u.role != 'admin'
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
}
$conn->close();
?>