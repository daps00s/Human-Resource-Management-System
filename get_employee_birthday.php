<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();
$employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$employee_id) {
    echo '<div class="empty-state">Employee not found</div>';
    exit;
}

$query = "
    SELECT 
        u.id,
        u.first_name,
        u.last_name,
        u.birth_date,
        u.email,
        u.contact_number,
        u.profile_picture,
        o.office_name,
        ee.position,
        TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) as age,
        DATE_FORMAT(u.birth_date, '%M %d, %Y') as formatted_birthday,
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
        ) as days_until_birthday
    FROM users u
    LEFT JOIN employee_employment ee ON u.id = ee.user_id
    LEFT JOIN offices o ON ee.office_id = o.id
    WHERE u.id = ? AND u.role = 'employee'
";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$emp = $result->fetch_assoc();

if (!$emp) {
    echo '<div class="empty-state">Employee not found</div>';
    exit;
}

$is_today = $emp['days_until_birthday'] == 0;
$next_age = $emp['age'] + 1;
?>

<div style="padding: 20px;">
    <!-- Header -->
    <div style="text-align: center; margin-bottom: 25px;">
        <div style="width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 15px; background: <?php echo $is_today ? 'linear-gradient(135deg, #ff6b6b, #ee5253)' : 'linear-gradient(135deg, #667eea, #764ba2)'; ?>; display: flex; align-items: center; justify-content: center; color: white; font-size: 32px; font-weight: 600; overflow: hidden;">
            <?php if (!empty($emp['profile_picture'])): ?>
                <img src="uploads/profiles/<?php echo $emp['profile_picture']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <?php echo strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1)); ?>
            <?php endif; ?>
        </div>
        <h2 style="margin: 0 0 5px; color: #333;"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></h2>
        <p style="margin: 0; color: #666; font-size: 13px;">
            <?php echo htmlspecialchars($emp['position'] ?? 'Employee'); ?> • 
            <?php echo htmlspecialchars($emp['office_name'] ?? 'No Department'); ?>
        </p>
        <?php if ($is_today): ?>
            <div style="margin-top: 10px; background: #ff6b6b; color: white; padding: 5px 15px; border-radius: 20px; display: inline-block; font-size: 12px; font-weight: 600;">
                <i class="fas fa-birthday-cake"></i> HAPPY BIRTHDAY TODAY!
            </div>
        <?php endif; ?>
    </div>

    <!-- Details Grid -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px;">
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
            <i class="fas fa-calendar" style="color: #3498db; font-size: 20px; margin-bottom: 8px;"></i>
            <h4 style="margin: 0 0 5px; font-size: 12px; color: #666;">Birth Date</h4>
            <p style="margin: 0; font-size: 15px; font-weight: 600; color: #333;">
                <?php echo date('F d', strtotime($emp['birth_date'])); ?>
            </p>
            <small style="color: #999;"><?php echo $emp['day_of_week']; ?></small>
        </div>

        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
            <i class="fas fa-birthday-cake" style="color: #ff6b6b; font-size: 20px; margin-bottom: 8px;"></i>
            <h4 style="margin: 0 0 5px; font-size: 12px; color: #666;">Turning</h4>
            <p style="margin: 0; font-size: 15px; font-weight: 600; color: #333;">
                <?php echo $next_age; ?> years old
            </p>
            <small style="color: #999;">on their next birthday</small>
        </div>
    </div>

    <!-- Countdown -->
    <?php if (!$is_today): ?>
    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
        <i class="fas fa-clock" style="color: #f39c12; margin-right: 5px;"></i>
        <strong>Countdown to Birthday:</strong>
        <div style="font-size: 24px; font-weight: 700; color: #3498db; margin: 10px 0;">
            <?php echo $emp['days_until_birthday']; ?> days
        </div>
        <small style="color: #666;"><?php echo date('F d', strtotime('+' . $emp['days_until_birthday'] . ' days')); ?></small>
    </div>
    <?php endif; ?>

    <!-- Contact Info -->
    <div style="border-top: 1px solid #f0f0f0; padding-top: 15px;">
        <div style="display: flex; gap: 15px; justify-content: center;">
            <?php if ($emp['email']): ?>
            <a href="mailto:<?php echo $emp['email']; ?>" style="background: #3498db; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 12px;">
                <i class="fas fa-envelope"></i> Send Greeting
            </a>
            <?php endif; ?>
            <?php if ($emp['contact_number']): ?>
            <a href="tel:<?php echo $emp['contact_number']; ?>" style="background: #27ae60; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 12px;">
                <i class="fas fa-phone"></i> Call
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>