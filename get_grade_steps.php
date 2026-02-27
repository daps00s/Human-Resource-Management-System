<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['grade_id']) || !is_numeric($_GET['grade_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid grade ID']);
    exit();
}

$grade_id = intval($_GET['grade_id']);

// Get all steps for this salary grade
$sql = "SELECT step, monthly_salary FROM salary_grades WHERE id = ? ORDER BY step";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $grade_id);
$stmt->execute();
$result = $stmt->get_result();

$steps = [];
while ($row = $result->fetch_assoc()) {
    $steps[] = [
        'step' => $row['step'],
        'monthly_salary' => floatval($row['monthly_salary'])
    ];
}

if (count($steps) > 0) {
    echo json_encode(['success' => true, 'steps' => $steps]);
} else {
    echo json_encode(['success' => false, 'message' => 'No steps found for this grade']);
}

$stmt->close();
$conn->close();
?>