<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

$user = $auth->getCurrentUser();
$fullName = $auth->getUserFullName();
$userId = $user['id'];

// Get HRMO-specific stats
$db = getDB();

// Employee statistics - REAL DATA FROM DATABASE
$total_employees = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'employee'")->fetch_assoc()['total'];
$new_hires = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'employee' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetch_assoc()['total'];

// Leave statistics - REAL DATA
$pending_leaves_result = $db->query("SELECT COUNT(*) as total FROM leave_requests WHERE status = 'pending'");
$pending_leaves = $pending_leaves_result ? $pending_leaves_result->fetch_assoc()['total'] : 0;

$approved_leaves_result = $db->query("SELECT COUNT(*) as total FROM leave_requests WHERE status = 'approved' AND MONTH(created_at) = MONTH(CURRENT_DATE())");
$approved_leaves = $approved_leaves_result ? $approved_leaves_result->fetch_assoc()['total'] : 0;

// Attendance statistics for today - REAL DATA
$today = date('Y-m-d');
$present_today = $db->query("SELECT COUNT(*) as total FROM daily_time_record WHERE date = '$today' AND time_in IS NOT NULL")->fetch_assoc()['total'] ?? 0;
$absent_today = $db->query("SELECT COUNT(*) as total FROM users u LEFT JOIN daily_time_record d ON u.id = d.user_id AND d.date = '$today' WHERE u.role = 'employee' AND d.id IS NULL")->fetch_assoc()['total'] ?? 0;
$on_leave_today = $db->query("SELECT COUNT(*) as total FROM leave_requests WHERE status = 'approved' AND '$today' BETWEEN SUBSTRING_INDEX(inclusive_dates, ' to ', 1) AND SUBSTRING_INDEX(inclusive_dates, ' to ', -1)")->fetch_assoc()['total'] ?? 0;

// Payroll statistics
$payroll_period = date('F 1-15, Y');
$total_payroll_result = $db->query("SELECT SUM(monthly_salary) as total FROM employee_employment");
$total_payroll_amount = $total_payroll_result ? $total_payroll_result->fetch_assoc()['total'] : 0;
$total_payroll = '₱' . number_format($total_payroll_amount / 2, 2);

// Get upcoming birthdays from database for dashboard preview
$birthday_query = "
    SELECT 
        u.id,
        u.first_name,
        u.last_name,
        u.birth_date,
        u.profile_picture,
        o.office_name,
        TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) as age,
        DATE_FORMAT(u.birth_date, '%M %d') as birthday_formatted,
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
            ) = 0 THEN 'today'
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
            ) <= 7 THEN 'week'
            WHEN MONTH(u.birth_date) = MONTH(CURDATE()) THEN 'month'
            ELSE 'upcoming'
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
    LIMIT 5
";

$birthdays_result = $db->query($birthday_query);
$birthdays = [];
$birthday_categories = ['today' => [], 'week' => [], 'month' => [], 'upcoming' => []];

while ($row = $birthdays_result->fetch_assoc()) {
    $birthdays[] = $row;
    $birthday_categories[$row['category']][] = $row;
}

// Get calendar events for dashboard preview
$events_query = "
    SELECT 
        ce.*,
        CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
        DATEDIFF(ce.start_date, CURDATE()) as days_until,
        DAY(ce.start_date) as day,
        MONTH(ce.start_date) as month,
        YEAR(ce.start_date) as year,
        DATE_FORMAT(ce.start_date, '%M') as month_name,
        DATE_FORMAT(ce.start_date, '%d') as day_num,
        DATE_FORMAT(ce.start_date, '%b') as month_short
    FROM calendar_events ce
    LEFT JOIN users u ON ce.created_by = u.id
    WHERE ce.status = 'active' 
    AND ce.start_date >= CURDATE()
    ORDER BY ce.start_date ASC, ce.start_time ASC
    LIMIT 5
";
$events_result = $db->query($events_query);
$events = [];
while ($row = $events_result->fetch_assoc()) {
    $events[] = $row;
}

// Get pending requests
$pending_requests = [];
$leave_pending = $db->query("
    SELECT 
        lr.id,
        CONCAT(u.first_name, ' ', u.last_name) as employee_name,
        lt.leave_name,
        lr.created_at,
        DATEDIFF(NOW(), lr.created_at) as days_pending
    FROM leave_requests lr
    JOIN users u ON lr.user_id = u.id
    JOIN leave_types lt ON lr.leave_type_id = lt.id
    WHERE lr.status = 'pending'
    ORDER BY lr.created_at ASC
    LIMIT 5
");
while ($row = $leave_pending->fetch_assoc()) {
    $pending_requests[] = [
        'id' => $row['id'],
        'type' => 'Leave Request',
        'title' => $row['leave_name'],
        'employee' => $row['employee_name'],
        'days' => $row['days_pending']
    ];
}

// Get department distribution
$dept_stats_query = "
    SELECT 
        o.office_name as dept,
        COUNT(ee.user_id) as count
    FROM offices o
    LEFT JOIN employee_employment ee ON o.id = ee.office_id
    WHERE o.status = 'active'
    GROUP BY o.id, o.office_name
    ORDER BY count DESC
    LIMIT 8
";
$dept_stats_result = $db->query($dept_stats_query);
$dept_stats = [];
$max_count = 0;
while ($row = $dept_stats_result->fetch_assoc()) {
    $dept_stats[] = $row;
    if ($row['count'] > $max_count) $max_count = $row['count'];
}

// Get recent activities
$activities_query = "
    SELECT 
        al.action,
        al.description,
        al.created_at,
        CONCAT(u.first_name, ' ', u.last_name) as user_name
    FROM activity_logs al
    JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 10
";
$activities_result = $db->query($activities_query);
$activities = [];
while ($row = $activities_result->fetch_assoc()) {
    $time_diff = time() - strtotime($row['created_at']);
    if ($time_diff < 60) {
        $time_ago = 'just now';
    } elseif ($time_diff < 3600) {
        $minutes = floor($time_diff / 60);
        $time_ago = $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($time_diff < 86400) {
        $hours = floor($time_diff / 3600);
        $time_ago = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($time_diff / 86400);
        $time_ago = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
    
    $activities[] = [
        'action' => $row['action'],
        'description' => $row['description'],
        'user' => $row['user_name'],
        'time' => $time_ago
    ];
}

// Include header
include 'includes/header.php';
?>

<!-- Dashboard CSS -->
<style>
/* Dashboard Container */
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

/* Welcome Banner */
.welcome-banner {
    background: linear-gradient(135deg, #1e2b3a 0%, #2c3e50 100%);
    border-radius: 10px;
    padding: 25px 30px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.welcome-content h1 {
    font-size: 24px;
    margin-bottom: 5px;
    font-weight: 500;
}

.welcome-content p {
    font-size: 13px;
    opacity: 0.8;
}

.btn-primary {
    background: rgba(255,255,255,0.15);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-primary:hover {
    background: rgba(255,255,255,0.25);
    border-color: rgba(255,255,255,0.5);
}

.btn-primary i {
    font-size: 14px;
}

/* Metrics Section */
.metrics-section {
    margin-bottom: 25px;
}

.metrics-section h3 {
    font-size: 16px;
    color: #333;
    margin-bottom: 15px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.metrics-section h3 i {
    color: #3498db;
    font-size: 18px;
}

/* Stats Row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    display: flex;
    align-items: flex-start;
    gap: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    transition: transform 0.3s, box-shadow 0.3s;
    cursor: pointer;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 50px;
    height: 50px;
    background: #f0f7ff;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #3498db;
    font-size: 24px;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 24px;
    font-weight: 600;
    color: #333;
    line-height: 1.2;
    margin-bottom: 3px;
}

.stat-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-trend {
    font-size: 11px;
    color: #27ae60;
}

.stat-trend.positive {
    color: #27ae60;
}

.stat-trend.negative {
    color: #e74c3c;
}

.stat-detail {
    font-size: 11px;
    color: #999;
}

/* Dashboard Grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}

/* Cards */
.card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    margin-bottom: 25px;
    overflow: hidden;
}

.card-header {
    padding: 18px 20px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    font-size: 15px;
    color: #333;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

.card-header h3 i {
    color: #3498db;
    font-size: 16px;
}

.card-link {
    color: #3498db;
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    background: none;
    border: none;
    cursor: pointer;
}

.card-link:hover {
    text-decoration: underline;
}

.card-body {
    padding: 20px;
}

.badge {
    background: #e74c3c;
    color: white;
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
}

/* Quick Actions Grid */
.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.quick-action-item {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px 8px;
    text-align: center;
    text-decoration: none;
    transition: all 0.3s;
    border: 1px solid transparent;
    color: #333;
}

.quick-action-item:hover {
    background: white;
    border-color: #3498db;
    transform: translateY(-2px);
    box-shadow: 0 3px 10px rgba(52, 152, 219, 0.1);
}

.quick-action-item i {
    font-size: 20px;
    color: #3498db;
    display: block;
    margin-bottom: 8px;
}

.quick-action-item span {
    font-size: 11px;
    font-weight: 500;
}

/* Activity List */
.activity-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.activity-item {
    display: flex;
    gap: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f0f0;
}

.activity-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.activity-icon {
    width: 8px;
    height: 8px;
    background: #3498db;
    border-radius: 50%;
    margin-top: 6px;
}

.activity-content {
    flex: 1;
}

.activity-text {
    color: #333;
    font-size: 13px;
    margin-bottom: 3px;
    line-height: 1.4;
}

.activity-text strong {
    font-weight: 600;
    color: #2c3e50;
}

.activity-text span {
    color: #666;
    font-size: 12px;
}

.activity-time {
    color: #999;
    font-size: 11px;
}

/* Department Stats */
.department-stats {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.dept-stat-item {
    width: 100%;
}

.dept-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
    font-size: 13px;
}

.dept-name {
    color: #333;
    font-weight: 500;
}

.dept-count {
    color: #666;
}

.progress-bar {
    height: 6px;
    background: #f0f0f0;
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #3498db;
    border-radius: 3px;
    transition: width 0.3s;
}

/* Request List */
.request-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.request-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f0f0;
}

.request-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.request-info {
    flex: 1;
}

.request-title {
    font-weight: 500;
    color: #333;
    font-size: 13px;
    margin-bottom: 3px;
}

.request-meta {
    color: #666;
    font-size: 11px;
}

.request-status {
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 12px;
    font-weight: 500;
    text-decoration: none;
}

.request-status.pending {
    background: #fff3e0;
    color: #e67e22;
}

.request-status.approved {
    background: #e8f5e9;
    color: #27ae60;
}

.request-status.rejected {
    background: #ffebee;
    color: #e74c3c;
}

/* Event List */
.event-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.event-item {
    display: flex;
    gap: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background 0.3s;
    padding: 10px;
    border-radius: 5px;
}

.event-item:hover {
    background: #f8f9fa;
}

.event-item:last-child {
    border-bottom: none;
    padding-bottom: 10px;
}

.event-date {
    min-width: 50px;
    text-align: center;
    background: #f0f7ff;
    border-radius: 6px;
    padding: 8px 0;
}

.event-day {
    display: block;
    font-size: 16px;
    font-weight: 700;
    color: #3498db;
}

.event-month {
    font-size: 10px;
    color: #666;
    text-transform: uppercase;
}

.event-content {
    flex: 1;
}

.event-title {
    font-weight: 600;
    color: #333;
    font-size: 13px;
    margin-bottom: 5px;
}

.event-details {
    color: #666;
    font-size: 11px;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.event-details i {
    margin-right: 3px;
    color: #999;
}

/* Birthday List */
.birthday-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.birthday-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background 0.3s;
    padding: 8px;
    border-radius: 5px;
}

.birthday-item:hover {
    background: #f8f9fa;
}

.birthday-item:last-child {
    border-bottom: none;
    padding-bottom: 8px;
}

.birthday-icon {
    width: 35px;
    height: 35px;
    background: #fdf2e9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #e67e22;
    font-size: 16px;
}

.birthday-content {
    flex: 1;
}

.birthday-name {
    font-weight: 600;
    color: #333;
    font-size: 13px;
    margin-bottom: 3px;
}

.birthday-details {
    display: flex;
    gap: 10px;
    color: #666;
    font-size: 11px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px;
    color: #999;
}

.empty-state i {
    font-size: 48px;
    color: #ddd;
    margin-bottom: 15px;
}

/* Responsive */
@media (max-width: 1200px) {
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .quick-actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .welcome-banner {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .stats-row {
        grid-template-columns: 1fr;
    }
    
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }
    
    .event-details {
        flex-direction: column;
        gap: 5px;
    }
}

@media (max-width: 480px) {
    .stat-card {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .stat-icon {
        margin-bottom: 10px;
    }
    
    .birthday-details {
        flex-direction: column;
        gap: 3px;
    }
    
    .request-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}
</style>

<!-- Dashboard Content -->
<div class="dashboard-container">
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div class="welcome-content">
            <h1>Welcome back, <?php echo htmlspecialchars($fullName); ?>!</h1>
            <p><?php echo date('l, F j, Y'); ?> | Municipal Human Resource Management Office</p>
        </div>
        <div class="welcome-actions">
            <button class="btn-primary" onclick="window.location.href='reports_dashboard.php'">
                <i class="fas fa-file-alt"></i> Generate Report
            </button>
        </div>
    </div>
    
    <!-- Key HR Metrics -->
    <div class="metrics-section">
        <h3><i class="fas fa-chart-line"></i> Key HR Metrics</h3>
        <div class="stats-row">
            <div class="stat-card" onclick="window.location.href='employee_list.php'">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $total_employees; ?></div>
                    <div class="stat-label">Total Employees</div>
                    <div class="stat-trend positive">+<?php echo $new_hires; ?> this month</div>
                </div>
            </div>
            
            <div class="stat-card" onclick="window.location.href='attendance.php'">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $present_today; ?></div>
                    <div class="stat-label">Present Today</div>
                    <div class="stat-detail">Absent: <?php echo $absent_today; ?> | On Leave: <?php echo $on_leave_today; ?></div>
                </div>
            </div>
            
            <div class="stat-card" onclick="window.location.href='leave_requests.php'">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $pending_leaves; ?></div>
                    <div class="stat-label">Pending Leaves</div>
                    <div class="stat-detail">Approved: <?php echo $approved_leaves; ?></div>
                </div>
            </div>
            
            <div class="stat-card" onclick="window.location.href='payroll.php'">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $total_payroll; ?></div>
                    <div class="stat-label">Current Payroll</div>
                    <div class="stat-detail">Period: <?php echo $payroll_period; ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Two Column Layout -->
    <div class="dashboard-grid">
        <!-- Left Column -->
        <div class="grid-left">
            <!-- Quick Actions for HR -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-bolt"></i> HR Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="quick-actions-grid">
                        <a href="add_employee.php" class="quick-action-item">
                            <i class="fas fa-user-plus"></i>
                            <span>New Employee</span>
                        </a>
                        <a href="leave_requests.php?action=new" class="quick-action-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Process Leave</span>
                        </a>
                        <a href="overtime.php" class="quick-action-item">
                            <i class="fas fa-clock"></i>
                            <span>Approve Overtime</span>
                        </a>
                        <a href="payroll.php" class="quick-action-item">
                            <i class="fas fa-file-invoice"></i>
                            <span>Generate Payslip</span>
                        </a>
                        <a href="reports_dashboard.php" class="quick-action-item">
                            <i class="fas fa-chart-pie"></i>
                            <span>HR Reports</span>
                        </a>
                        <a href="training.php" class="quick-action-item">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Training</span>
                        </a>
                        <a href="step_increments.php" class="quick-action-item">
                            <i class="fas fa-arrow-circle-up"></i>
                            <span>Step Increment</span>
                        </a>
                        <a href="plantilla.php" class="quick-action-item">
                            <i class="fas fa-sitemap"></i>
                            <span>Plantilla</span>
                        </a>
                        <a href="settings.php" class="quick-action-item">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent HR Activities -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Recent HR Activities</h3>
                    <a href="activity_logs.php" class="card-link">View All</a>
                </div>
                <div class="card-body">
                    <div class="activity-list">
                        <?php if (!empty($activities)): ?>
                            <?php foreach ($activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon"></div>
                                <div class="activity-content">
                                    <p class="activity-text">
                                        <strong><?php echo htmlspecialchars($activity['action']); ?></strong>
                                        <span> - <?php echo htmlspecialchars($activity['description']); ?></span>
                                        <br>
                                        <span>by <?php echo htmlspecialchars($activity['user']); ?></span>
                                    </p>
                                    <span class="activity-time"><?php echo $activity['time']; ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-history"></i>
                                <p>No recent activities</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
                        
            <!-- Department Distribution -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-sitemap"></i> Department & Office Distribution</h3>
                    <a href="offices.php" class="card-link">View All</a>
                </div>
                <div class="card-body">
                    <div class="department-stats">
                        <?php if (!empty($dept_stats)): ?>
                            <?php foreach ($dept_stats as $dept): ?>
                            <div class="dept-stat-item">
                                <div class="dept-info">
                                    <span class="dept-name"><?php echo htmlspecialchars($dept['dept']); ?></span>
                                    <span class="dept-count"><?php echo $dept['count']; ?> employees</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $max_count > 0 ? ($dept['count'] / $max_count) * 100 : 0; ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-building"></i>
                                <p>No department data available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="grid-right">
            <!-- Pending Requests -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-clock"></i> Pending Requests</h3>
                    <span class="badge"><?php echo count($pending_requests); ?></span>
                </div>
                <div class="card-body">
                    <div class="request-list">
                        <?php if (!empty($pending_requests)): ?>
                            <?php foreach ($pending_requests as $request): ?>
                            <div class="request-item">
                                <div class="request-info">
                                    <p class="request-title"><?php echo htmlspecialchars($request['type']); ?></p>
                                    <p class="request-meta">
                                        <?php echo htmlspecialchars($request['employee']); ?> • 
                                        <?php echo $request['title']; ?> • 
                                        <?php echo $request['days']; ?> days ago
                                    </p>
                                </div>
                                <a href="leave_requests.php?id=<?php echo $request['id']; ?>" class="request-status pending">Review</a>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle" style="color: #27ae60;"></i>
                                <p>No pending requests</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events / HR Calendar -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-alt"></i> HR Calendar</h3>
                    <a href="calendar_page.php" class="card-link">View Calendar</a>
                </div>
                <div class="card-body">
                    <div class="event-list">
                        <?php if (!empty($events)): ?>
                            <?php foreach ($events as $event): 
                                $event_date = new DateTime($event['start_date']);
                                $today = new DateTime();
                                $is_today = $event_date->format('Y-m-d') == date('Y-m-d');
                                $is_tomorrow = $event_date->format('Y-m-d') == date('Y-m-d', strtotime('+1 day'));
                            ?>
                            <div class="event-item" onclick="window.location.href='event_details.php?id=<?php echo $event['id']; ?>'">
                                <div class="event-date">
                                    <span class="event-day"><?php echo $event['day_num']; ?></span>
                                    <span class="event-month"><?php echo $event['month_short']; ?></span>
                                </div>
                                <div class="event-content">
                                    <p class="event-title">
                                        <?php echo htmlspecialchars($event['title']); ?>
                                        <?php if ($is_today): ?>
                                            <span style="background: #e74c3c; color: white; padding: 2px 6px; border-radius: 3px; font-size: 9px; margin-left: 5px;">TODAY</span>
                                        <?php elseif ($is_tomorrow): ?>
                                            <span style="background: #f39c12; color: white; padding: 2px 6px; border-radius: 3px; font-size: 9px; margin-left: 5px;">TOMORROW</span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="event-details">
                                        <?php if ($event['start_time']): ?>
                                            <span><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($event['start_time'])); ?></span>
                                        <?php endif; ?>
                                        <?php if ($event['venue']): ?>
                                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['venue']); ?></span>
                                        <?php endif; ?>
                                        <span><i class="fas fa-tag"></i> <?php echo ucfirst($event['event_type']); ?></span>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                <p>No upcoming events</p>
                                <a href="add_event.php" class="btn-primary" style="margin-top: 10px; background: #3498db; color: white; border: none; text-decoration: none; display: inline-block;">
                                    <i class="fas fa-plus"></i> Add Event
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Upcoming Birthdays -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-birthday-cake"></i> Upcoming Birthdays</h3>
                    <a href="birthday_page.php" class="card-link">View All</a>
                </div>
                <div class="card-body">
                    <div class="birthday-list">
                        <?php 
                        $display_birthdays = array_merge($birthday_categories['today'], $birthday_categories['week'], $birthday_categories['month']);
                        $display_birthdays = array_slice($display_birthdays, 0, 5);
                        ?>
                        <?php if (!empty($display_birthdays)): ?>
                            <?php foreach ($display_birthdays as $bday): ?>
                            <div class="birthday-item" onclick="window.location.href='employee_profile.php?id=<?php echo $bday['id']; ?>'">
                                <div class="birthday-icon">
                                    <i class="fas fa-gift"></i>
                                </div>
                                <div class="birthday-content">
                                    <p class="birthday-name">
                                        <?php echo htmlspecialchars($bday['first_name'] . ' ' . $bday['last_name']); ?>
                                        <?php if ($bday['category'] == 'today'): ?>
                                            <span style="background: #27ae60; color: white; padding: 2px 6px; border-radius: 3px; font-size: 9px; margin-left: 5px;">TODAY</span>
                                        <?php elseif ($bday['category'] == 'week'): ?>
                                            <span style="background: #f39c12; color: white; padding: 2px 6px; border-radius: 3px; font-size: 9px; margin-left: 5px;">SOON</span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="birthday-details">
                                        <span><?php echo date('M d', strtotime($bday['birth_date'])); ?></span>
                                        <span><?php echo htmlspecialchars($bday['office_name'] ?? 'No Department'); ?></span>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-birthday-cake"></i>
                                <p>No upcoming birthdays</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Close modal when clicking outside (if any modals exist)
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>