<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();

// Get all birthdays
$query = "
    SELECT 
        CONCAT(u.first_name, ' ', u.last_name) as employee_name,
        u.email,
        u.contact_number,
        DATE_FORMAT(u.birth_date, '%M %d, %Y') as birth_date,
        TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) as age,
        o.office_name as department,
        ee.position,
        DATEDIFF(
            DATE_ADD(
                u.birth_date, 
                INTERVAL TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) + 
                CASE 
                    WHEN DATE_ADD(u.birth_date, INTERVAL TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) YEAR) < CURDATE() 
                    THEN 1 ELSE 0 
                END YEAR
            ),
            CURDATE()
        ) as days_until_birthday
    FROM users u
    LEFT JOIN employee_employment ee ON u.id = ee.user_id
    LEFT JOIN offices o ON ee.office_id = o.id
    WHERE u.role = 'employee' AND u.birth_date IS NOT NULL
    ORDER BY days_until_birthday ASC
";

$result = $db->query($query);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="employee_birthdays_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, ['Employee Name', 'Position', 'Department', 'Birth Date', 'Age', 'Days Until Birthday', 'Email', 'Contact Number']);

// Add data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['employee_name'],
        $row['position'] ?? 'N/A',
        $row['department'] ?? 'N/A',
        $row['birth_date'],
        $row['age'] . ' years',
        $row['days_until_birthday'] >= 0 ? $row['days_until_birthday'] . ' days' : 'Passed',
        $row['email'] ?? 'N/A',
        $row['contact_number'] ?? 'N/A'
    ]);
}

fclose($output);
exit;