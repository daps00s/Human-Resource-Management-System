<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();

// Get all birthdays
$query = "
    SELECT 
        u.id,
        u.first_name,
        u.last_name,
        u.birth_date,
        u.profile_picture,
        u.email,
        u.contact_number,
        o.office_name,
        ee.position,
        TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) as age,
        DATE_FORMAT(u.birth_date, '%M %d') as birthday_formatted,
        DATE_FORMAT(u.birth_date, '%W') as day_of_week,
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
        ) as days_until_birthday,
        CASE 
            WHEN DATEDIFF(
                DATE_ADD(
                    u.birth_date, 
                    INTERVAL TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) + 
                    CASE 
                        WHEN DATE_ADD(u.birth_date, INTERVAL TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) YEAR) < CURDATE() 
                        THEN 1 ELSE 0 
                    END YEAR
                ),
                CURDATE()
            ) = 0 THEN 'Today'
            WHEN DATEDIFF(
                DATE_ADD(
                    u.birth_date, 
                    INTERVAL TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) + 
                    CASE 
                        WHEN DATE_ADD(u.birth_date, INTERVAL TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) YEAR) < CURDATE() 
                        THEN 1 ELSE 0 
                    END YEAR
                ),
                CURDATE()
            ) <= 7 THEN 'This Week'
            WHEN MONTH(u.birth_date) = MONTH(CURDATE()) THEN 'This Month'
            ELSE 'Upcoming'
        END as category
    FROM users u
    LEFT JOIN employee_employment ee ON u.id = ee.user_id
    LEFT JOIN offices o ON ee.office_id = o.id
    WHERE u.role = 'employee' 
    AND u.birth_date IS NOT NULL
    ORDER BY 
        CASE 
            WHEN days_until_birthday < 0 THEN 999
            ELSE days_until_birthday
        END ASC
";

$result = $db->query($query);

if (!$result) {
    echo '<div style="text-align: center; padding: 40px; color: #999;">Error loading birthdays</div>';
    exit;
}
?>

<div style="padding: 10px;">
    <?php if ($result->num_rows > 0): ?>
        <!-- Summary Cards -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px;">
            <?php
            $today_count = 0;
            $week_count = 0;
            $month_count = 0;
            $result->data_seek(0);
            while ($row = $result->fetch_assoc()) {
                if ($row['days_until_birthday'] == 0) $today_count++;
                elseif ($row['days_until_birthday'] <= 7) $week_count++;
                elseif ($row['days_until_birthday'] <= 30) $month_count++;
            }
            $result->data_seek(0);
            ?>
            <div style="background: linear-gradient(135deg, #ff6b6b, #ee5253); padding: 15px; border-radius: 8px; color: white;">
                <div style="font-size: 24px; font-weight: 600;"><?php echo $today_count; ?></div>
                <div style="font-size: 11px; opacity: 0.9;">Today</div>
            </div>
            <div style="background: linear-gradient(135deg, #feca57, #ff9f43); padding: 15px; border-radius: 8px; color: white;">
                <div style="font-size: 24px; font-weight: 600;"><?php echo $week_count; ?></div>
                <div style="font-size: 11px; opacity: 0.9;">This Week</div>
            </div>
            <div style="background: linear-gradient(135deg, #54a0ff, #2e86de); padding: 15px; border-radius: 8px; color: white;">
                <div style="font-size: 24px; font-weight: 600;"><?php echo $month_count; ?></div>
                <div style="font-size: 11px; opacity: 0.9;">This Month</div>
            </div>
            <div style="background: linear-gradient(135deg, #5f27cd, #341f97); padding: 15px; border-radius: 8px; color: white;">
                <div style="font-size: 24px; font-weight: 600;"><?php echo $result->num_rows; ?></div>
                <div style="font-size: 11px; opacity: 0.9;">Total</div>
            </div>
        </div>

        <!-- Birthday List -->
        <div style="max-height: 400px; overflow-y: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 1;">
                    <tr>
                        <th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6; font-size: 12px;">Employee</th>
                        <th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6; font-size: 12px;">Birth Date</th>
                        <th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6; font-size: 12px;">Department</th>
                        <th style="padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6; font-size: 12px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): 
                        $days = $row['days_until_birthday'];
                        $bg_color = $days == 0 ? '#fff3cd' : ($days <= 7 ? '#e8f5e9' : '');
                    ?>
                    <tr style="border-bottom: 1px solid #f0f0f0; background: <?php echo $bg_color; ?>;">
                        <td style="padding: 10px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 32px; height: 32px; background: #3498db; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600;">
                                    <?php echo strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div style="font-weight: 500; font-size: 13px;"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                    <div style="font-size: 11px; color: #666;"><?php echo htmlspecialchars($row['position'] ?? 'Employee'); ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 10px; font-size: 13px;">
                            <?php echo date('F d, Y', strtotime($row['birth_date'])); ?>
                            <div style="font-size: 11px; color: #666;">Turning <?php echo $row['age'] + 1; ?></div>
                        </td>
                        <td style="padding: 10px; font-size: 12px;"><?php echo htmlspecialchars($row['office_name'] ?? 'N/A'); ?></td>
                        <td style="padding: 10px;">
                            <?php if ($days == 0): ?>
                                <span style="background: #ff6b6b; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">TODAY!</span>
                            <?php elseif ($days > 0): ?>
                                <span style="background: #3498db; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;"><?php echo $days; ?> days</span>
                            <?php else: ?>
                                <span style="background: #95a5a6; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">Passed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 50px; color: #999;">
            <i class="fas fa-birthday-cake" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
            <p style="margin: 0;">No birthdays found</p>
            <p style="font-size: 12px; margin-top: 10px;">Make sure employees have birth dates set in their profiles</p>
        </div>
    <?php endif; ?>
</div>