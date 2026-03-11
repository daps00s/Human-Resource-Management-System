<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();

// Get all events
$query = "
    SELECT 
        ce.*,
        CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
        DATEDIFF(ce.start_date, CURDATE()) as days_until,
        CASE 
            WHEN ce.start_date = CURDATE() THEN 'Today'
            WHEN ce.start_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 'Tomorrow'
            WHEN ce.start_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'This Week'
            WHEN MONTH(ce.start_date) = MONTH(CURDATE()) AND YEAR(ce.start_date) = YEAR(CURDATE()) THEN 'This Month'
            ELSE 'Upcoming'
        END as category
    FROM calendar_events ce
    LEFT JOIN users u ON ce.created_by = u.id
    WHERE ce.status = 'active' 
    AND ce.start_date >= CURDATE()
    ORDER BY ce.start_date ASC, ce.start_time ASC
";

$result = $db->query($query);

if (!$result) {
    echo '<div style="text-align: center; padding: 40px; color: #999;">Error loading events</div>';
    exit;
}
?>

<div style="padding: 10px;">
    <?php if ($result->num_rows > 0): ?>
        <?php while ($event = $result->fetch_assoc()): 
            $start_date = new DateTime($event['start_date']);
            $today = new DateTime();
            $is_today = $start_date->format('Y-m-d') == $today->format('Y-m-d');
            $is_tomorrow = $start_date->format('Y-m-d') == $today->modify('+1 day')->format('Y-m-d');
            $today = new DateTime(); // Reset
        ?>
        <div onclick="parent.viewEventDetails(<?php echo $event['id']; ?>)" 
             style="cursor: pointer; margin-bottom: 15px; padding: 15px; background: <?php echo $is_today ? '#fff3cd' : '#f8f9fa'; ?>; border-radius: 8px; border-left: 4px solid <?php echo $event['color']; ?>; transition: all 0.3s; box-shadow: 0 2px 5px rgba(0,0,0,0.05);"
             onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 5px 15px rgba(0,0,0,0.1)';"
             onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 5px rgba(0,0,0,0.05)';">
            
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                <h4 style="margin: 0; font-size: 15px; font-weight: 600; color: #333;">
                    <?php echo htmlspecialchars($event['title']); ?>
                </h4>
                <?php if ($is_today): ?>
                    <span style="background: #ff6b6b; color: white; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 600;">TODAY</span>
                <?php elseif ($is_tomorrow): ?>
                    <span style="background: #f39c12; color: white; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 600;">TOMORROW</span>
                <?php endif; ?>
            </div>
            
            <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 8px;">
                <span style="font-size: 12px; color: #666;">
                    <i class="fas fa-calendar" style="width: 14px; color: #3498db;"></i>
                    <?php echo $start_date->format('M d, Y'); ?>
                </span>
                <?php if ($event['start_time']): ?>
                    <span style="font-size: 12px; color: #666;">
                        <i class="fas fa-clock" style="width: 14px; color: #27ae60;"></i>
                        <?php echo date('h:i A', strtotime($event['start_time'])); ?>
                    </span>
                <?php endif; ?>
                <span style="font-size: 12px; color: #666;">
                    <i class="fas fa-tag" style="width: 14px; color: <?php echo $event['color']; ?>;"></i>
                    <?php echo ucfirst($event['event_type']); ?>
                </span>
            </div>
            
            <?php if ($event['venue']): ?>
                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">
                    <i class="fas fa-map-marker-alt" style="width: 14px; color: #e67e22;"></i>
                    <?php echo htmlspecialchars($event['venue']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($event['days_until'] > 0 && !$is_today): ?>
                <div style="margin-top: 8px;">
                    <span style="background: #e8f5e9; color: #27ae60; padding: 3px 8px; border-radius: 12px; font-size: 10px;">
                        <i class="fas fa-hourglass-half"></i> <?php echo $event['days_until']; ?> days left
                    </span>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 8px; font-size: 10px; color: #999;">
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($event['created_by_name'] ?? 'System'); ?>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div style="text-align: center; padding: 50px; color: #999;">
            <i class="fas fa-calendar-times" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
            <p style="margin: 0 0 10px;">No upcoming events</p>
            <p style="font-size: 12px; margin-bottom: 20px;">Click the "Add Event" button to create one</p>
            <button onclick="parent.addEvent()" style="background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 13px;">
                <i class="fas fa-plus"></i> Add Event
            </button>
        </div>
    <?php endif; ?>
</div>