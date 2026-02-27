<?php
require_once 'includes/config.php';

// Get annex parameter
$annex = isset($_GET['annex']) ? $_GET['annex'] : 'A-1';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="salary_grade_matrix_' . $annex . '_' . date('Y-m-d') . '.xls"');

// Fetch data for the selected annex
$sql = "SELECT * FROM salary_grades WHERE annex = ? ORDER BY 
        CAST(SUBSTRING(salary_grade, 4) AS UNSIGNED), step";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $annex);
$stmt->execute();
$result = $stmt->get_result();

// Get annex title
$annex_titles = [
    'A-1' => 'Executive & Administrative Offices',
    'A-2' => 'Finance & Revenue Department',
    'A-3' => 'Planning & Development',
    'A-4' => 'Engineering & Public Works',
    'A-5' => 'Health Services',
    'A-6' => 'Agriculture & Veterinary',
    'A-7' => 'Social Welfare & HR',
    'A-8' => 'Tourism & Culture',
    'A-9' => 'Library & Information',
    'A-10' => 'Civil Registry',
    'A-11' => 'Market & Public Services',
    'A-12' => 'Labor & Employment'
];
$title = isset($annex_titles[$annex]) ? $annex_titles[$annex] : 'Salary Grade Matrix';

// Create HTML table for Excel
echo '<html>';
echo '<head>';
echo '<title>Salary Grade Matrix ' . $annex . '</title>';
echo '<style>';
echo 'th { background: #1e2b3a; color: white; padding: 8px; }';
echo 'td { padding: 6px; border: 1px solid #ccc; }';
echo '.grade { font-weight: bold; background: #f0f0f0; }';
echo '.amount { text-align: right; }';
echo '</style>';
echo '</head>';
echo '<body>';

echo '<h2>' . $annex . ': ' . $title . '</h2>';
echo '<p>Generated on: ' . date('F d, Y h:i A') . '</p>';
echo '<p>Effectivity Date: Based on DBM Circular</p>';

echo '<table border="1" cellspacing="0" cellpadding="5">';
echo '<tr>';
echo '<th>ID</th>';
echo '<th>Salary Grade</th>';
echo '<th>Step</th>';
echo '<th>Monthly Salary</th>';
echo '<th>Annual Salary</th>';
echo '<th>Effective Date</th>';
echo '<th>Position Category</th>';
echo '</tr>';

while ($row = $result->fetch_assoc()) {
    echo '<tr>';
    echo '<td>' . $row['id'] . '</td>';
    echo '<td class="grade">' . $row['salary_grade'] . '</td>';
    echo '<td>Step ' . $row['step'] . '</td>';
    echo '<td class="amount">₱' . number_format($row['monthly_salary'], 2) . '</td>';
    echo '<td class="amount">₱' . number_format($row['annual_salary'], 2) . '</td>';
    echo '<td>' . date('M d, Y', strtotime($row['effective_date'])) . '</td>';
    echo '<td>' . ($row['position_category'] ?? '-') . '</td>';
    echo '</tr>';
}

echo '</table>';
echo '</body>';
echo '</html>';

$stmt->close();
?>