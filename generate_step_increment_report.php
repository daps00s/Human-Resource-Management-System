<?php
require_once 'includes/config.php';

// Set headers for PDF download (you'll need to implement PDF generation)
// This is a simplified version that generates HTML for printing

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Step Increment Report</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 30px; }
        .date { text-align: right; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #667eea; color: white; padding: 10px; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
        .eligible { background: #d4edda; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Step Increment Eligibility Report</h1>
        <h3><?php echo SITE_NAME; ?></h3>
    </div>
    
    <div class="date">
        Generated on: <?php echo date('F d, Y h:i A'); ?>
    </div>
    
    <?php
    $sql = "
        SELECT 
            u.employee_id,
            u.first_name,
            u.last_name,
            ee.position,
            o.office_name,
            ee.date_hired,
            ee.step as current_step,
            sg.salary_grade,
            sg.monthly_salary as current_salary,
            TIMESTAMPDIFF(YEAR, ee.date_hired, CURDATE()) as years_of_service
        FROM users u
        JOIN employee_employment ee ON u.id = ee.user_id
        JOIN salary_grades sg ON ee.salary_grade_id = sg.id AND ee.step = sg.step
        JOIN offices o ON ee.office_id = o.id
        WHERE u.role != 'admin'
        ORDER BY o.office_name, years_of_service DESC
    ";
    
    $result = $conn->query($sql);
    $current_office = '';
    
    while ($row = $result->fetch_assoc()):
        if ($current_office != $row['office_name']):
            if ($current_office != '') echo '</tbody></table>';
            $current_office = $row['office_name'];
            echo '<h2>' . $current_office . '</h2>';
            echo '<table>';
            echo '<tr>';
            echo '<th>Employee ID</th>';
            echo '<th>Name</th>';
            echo '<th>Position</th>';
            echo '<th>Date Hired</th>';
            echo '<th>Years of Service</th>';
            echo '<th>Grade</th>';
            echo '<th>Step</th>';
            echo '<th>Current Salary</th>';
            echo '<th>Next Step Salary</th>';
            echo '</tr>';
        endif;
        
        // Get next step salary
        $next_step = $row['current_step'] + 1;
        $next_salary = null;
        if ($next_step <= 8) {
            $stmt = $conn->prepare("SELECT monthly_salary FROM salary_grades WHERE salary_grade = ? AND step = ?");
            $stmt->bind_param("si", $row['salary_grade'], $next_step);
            $stmt->execute();
            $next = $stmt->get_result()->fetch_assoc();
            $next_salary = $next ? $next['monthly_salary'] : null;
        }
    ?>
    <tr class="<?php echo $next_salary ? 'eligible' : ''; ?>">
        <td><?php echo $row['employee_id']; ?></td>
        <td><?php echo $row['last_name'] . ', ' . $row['first_name']; ?></td>
        <td><?php echo $row['position']; ?></td>
        <td><?php echo date('M d, Y', strtotime($row['date_hired'])); ?></td>
        <td><?php echo $row['years_of_service']; ?> years</td>
        <td><?php echo $row['salary_grade']; ?></td>
        <td>Step <?php echo $row['current_step']; ?></td>
        <td>₱<?php echo number_format($row['current_salary'], 2); ?></td>
        <td>
            <?php if ($next_salary): ?>
                ₱<?php echo number_format($next_salary, 2); ?>
                (+₱<?php echo number_format($next_salary - $row['current_salary'], 2); ?>)
            <?php else: ?>
                Max step reached
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
    </table>
    
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>