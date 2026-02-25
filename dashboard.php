<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

$user = $auth->getCurrentUser();
$fullName = $auth->getUserFullName();

// Get HRMO-specific stats
$db = getDB();

// Employee statistics
$total_employees = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'employee'")->fetch_assoc()['total'];
$new_hires = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'employee' AND MONTH(created_at) = MONTH(CURRENT_DATE())")->fetch_assoc()['total'];

// Leave statistics
$pending_leaves = 12; // Placeholder - would come from leave table
$approved_leaves = 8; // Placeholder

// Attendance statistics
$present_today = 42; // Placeholder
$absent_today = 3; // Placeholder
$on_leave_today = 5; // Placeholder

// Payroll statistics
$payroll_period = "March 1-15, 2026";
$total_payroll = "₱1,245,000";

// Upcoming birthdays
$birthdays = [
    ['name' => 'Maria Santos', 'date' => 'Mar 15', 'dept' => 'HR'],
    ['name' => 'Juan Dela Cruz', 'date' => 'Mar 18', 'dept' => 'Engineering'],
    ['name' => 'Ana Gonzales', 'date' => 'Mar 22', 'dept' => 'Finance']
];

// Recent activities
$activities = [
    ['action' => 'New employee onboarded', 'user' => 'John Smith', 'time' => '2 hours ago'],
    ['action' => 'Leave request approved', 'user' => 'Maria Santos', 'time' => '3 hours ago'],
    ['action' => 'Overtime request', 'user' => 'Pedro Reyes', 'time' => '5 hours ago'],
    ['action' => 'Training completed', 'user' => 'HR Department', 'time' => 'Yesterday'],
    ['action' => 'Promotion completed', 'user' => 'HR Department', 'time' => '1 hour ago']
];

// Upcoming events
$events = [
    ['title' => 'HR Planning Meeting', 'date' => 'Mar 15', 'time' => '10:00 AM', 'venue' => 'Conference Room A'],
    ['title' => 'Employee Orientation', 'date' => 'Mar 18', 'time' => '9:00 AM', 'venue' => 'Training Room'],
    ['title' => 'Payroll Processing', 'date' => 'Mar 20', 'time' => 'All Day', 'venue' => 'Finance Dept'],
    ['title' => 'Performance Review', 'date' => 'Mar 25', 'time' => '1:00 PM', 'venue' => 'HR Office']
];

// Department distribution
$dept_stats = [
    ['dept' => 'Human Resources', 'count' => 8],
    ['dept' => 'Planning and Development', 'count' => 15],
    ['dept' => 'Finance', 'count' => 10],
    ['dept' => 'Treasury', 'count' => 12],
    ['dept' => 'Engineering', 'count' => 20]
];

// Include header
include 'includes/header.php';
?>

<!-- Dashboard Content -->
<div class="dashboard-container">
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div class="welcome-content">
            <h1>Administrator</h1>
            <p><?php echo date('l, F j, Y'); ?> | Municipal Human Resource Management Office</p>
        </div>
        <div class="welcome-actions">
            <button class="btn-primary">
                <i class="fas fa-file-alt"></i> Generate Report
            </button>
        </div>
    </div>
    
    <!-- Key HR Metrics -->
    <div class="metrics-section">
        <h3><i class="fas fa-chart-line"></i> Key HR Metrics</h3>
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $total_employees; ?></div>
                    <div class="stat-label">Total Employees</div>
                    <div class="stat-trend positive">+<?php echo $new_hires; ?> this month</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $present_today; ?></div>
                    <div class="stat-label">Present Today</div>
                    <div class="stat-detail">Absent: <?php echo $absent_today; ?> | On Leave: <?php echo $on_leave_today; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $pending_leaves; ?></div>
                    <div class="stat-label">Pending Leaves</div>
                    <div class="stat-detail">Approved: <?php echo $approved_leaves; ?></div>
                </div>
            </div>
            
            <div class="stat-card">
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
                        <a href="#" class="quick-action-item">
                            <i class="fas fa-user-plus"></i>
                            <span>New Employee</span>
                        </a>
                        <a href="#" class="quick-action-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Process Leave</span>
                        </a>
                        <a href="#" class="quick-action-item">
                            <i class="fas fa-clock"></i>
                            <span>Approve Overtime</span>
                        </a>
                        <a href="#" class="quick-action-item">
                            <i class="fas fa-file-invoice"></i>
                            <span>Generate Payslip</span>
                        </a>
                        <a href="#" class="quick-action-item">
                            <i class="fas fa-chart-pie"></i>
                            <span>HR Reports</span>
                        </a>
                        <a href="#" class="quick-action-item">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Training</span>
                        </a>
                    </div>
                </div>
            </div>

                        
            <!-- Recent HR Activities -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Recent HR Activities</h3>
                    <a href="#" class="card-link">View All</a>
                </div>
                <div class="card-body">
                    <div class="activity-list">
                        <?php foreach ($activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-circle"></i>
                            </div>
                            <div class="activity-content">
                                <p class="activity-text">
                                    <strong><?php echo htmlspecialchars($activity['action']); ?></strong>
                                    <span>by <?php echo htmlspecialchars($activity['user']); ?></span>
                                </p>
                                <span class="activity-time"><?php echo $activity['time']; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
                        
            <!-- Department Distribution -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-sitemap"></i> Department & Office Distribution</h3>
                    <a href="#" class="card-link">View All</a>
                </div>
                <div class="card-body">
                    <div class="department-stats">
                        <?php foreach ($dept_stats as $dept): ?>
                        <div class="dept-stat-item">
                            <div class="dept-info">
                                <span class="dept-name"><?php echo $dept['dept']; ?></span>
                                <span class="dept-count"><?php echo $dept['count']; ?> employees</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo ($dept['count'] / 20) * 100; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
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
                    <span class="badge">12</span>
                </div>
                <div class="card-body">
                    <div class="request-list">
                        <div class="request-item">
                            <div class="request-info">
                                <p class="request-title">Leave Request</p>
                                <p class="request-meta">Juan Dela Cruz • 3 days</p>
                            </div>
                            <span class="request-status pending">Pending</span>
                        </div>
                        <div class="request-item">
                            <div class="request-info">
                                <p class="request-title">Overtime</p>
                                <p class="request-meta">Maria Santos • 5 hours</p>
                            </div>
                            <span class="request-status pending">Pending</span>
                        </div>
                        <div class="request-item">
                            <div class="request-info">
                                <p class="request-title">Training Nomination</p>
                                <p class="request-meta">Pedro Reyes • Leadership</p>
                            </div>
                            <span class="request-status pending">Pending</span>
                        </div>
                        <div class="request-item">
                            <div class="request-info">
                                <p class="request-title">Salary Adjustment</p>
                                <p class="request-meta">Ana Gonzales • Promotion</p>
                            </div>
                            <span class="request-status pending">Pending</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-alt"></i> HR Calendar</h3>
                    <a href="#" class="card-link">View Calendar</a>
                </div>
                <div class="card-body">
                    <div class="event-list">
                        <?php foreach ($events as $event): ?>
                        <div class="event-item">
                            <div class="event-date">
                                <span class="event-day"><?php echo explode(' ', $event['date'])[1]; ?></span>
                                <span class="event-month"><?php echo explode(' ', $event['date'])[0]; ?></span>
                            </div>
                            <div class="event-content">
                                <p class="event-title"><?php echo $event['title']; ?></p>
                                <p class="event-details">
                                    <i class="fas fa-clock"></i> <?php echo $event['time']; ?>
                                    <i class="fas fa-map-marker-alt"></i> <?php echo $event['venue']; ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Upcoming Birthdays -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-birthday-cake"></i> Upcoming Birthdays</h3>
                    <span class="badge">This Week</span>
                </div>
                <div class="card-body">
                    <div class="birthday-list">
                        <?php foreach ($birthdays as $bday): ?>
                        <div class="birthday-item">
                            <div class="birthday-icon">
                                <i class="fas fa-gift"></i>
                            </div>
                            <div class="birthday-content">
                                <p class="birthday-name"><?php echo $bday['name']; ?></p>
                                <p class="birthday-details">
                                    <span><?php echo $bday['date']; ?></span>
                                    <span><?php echo $bday['dept']; ?> Department</span>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
// Include footer (closing tags from header.php)
?>
            </div>
        </main>
    </div>
    
    <script src="assets/js/header.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>