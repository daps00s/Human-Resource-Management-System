<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

$user = $auth->getCurrentUser();
$fullName = $auth->getUserFullName();

// Get additional stats for HR dashboard
$stats = [];
if ($user['role'] === 'admin' || $user['role'] === 'hr_manager') {
    $db = getDB();
    
    // Get total employees
    $result = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'employee'");
    $stats['total_employees'] = $result->fetch_assoc()['total'];
    
    // Get new employees this month
    $result = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'employee' AND MONTH(created_at) = MONTH(CURRENT_DATE())");
    $stats['new_employees'] = $result->fetch_assoc()['total'];
    
    // Get department counts
    $result = $db->query("SELECT department, COUNT(*) as count FROM users WHERE department IS NOT NULL GROUP BY department");
    $stats['departments'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['departments'][$row['department']] = $row['count'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <div class="container">
        <!-- Navigation Bar -->
        <nav class="navbar">
            <div class="nav-brand">
                <h2><?php echo SITE_NAME; ?></h2>
            </div>
            <div class="nav-menu">
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($fullName ?: $user['username']); ?>!</span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </nav>
        
        <!-- Main Content -->
        <div class="dashboard-container">
            <!-- User Profile Section -->
            <div class="profile-section">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['first_name'] ?? $user['username'], 0, 1)); ?>
                    </div>
                    <div class="profile-title">
                        <h3><?php echo htmlspecialchars($fullName ?: $user['username']); ?></h3>
                        <p class="role-badge role-<?php echo $user['role']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                        </p>
                    </div>
                </div>
                
                <div class="profile-details">
                    <div class="detail-item">
                        <span class="detail-label">Employee ID:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['employee_id'] ?? 'Not assigned'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Department:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['department'] ?? 'Not assigned'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Position:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($user['position'] ?? 'Not assigned'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Member Since:</span>
                        <span class="detail-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- HR Statistics Section (visible to admin/HR only) -->
            <?php if ($user['role'] === 'admin' || $user['role'] === 'hr_manager'): ?>
            <div class="stats-section">
                <h3>HR Dashboard</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['total_employees'] ?? 0; ?></div>
                        <div class="stat-label">Total Employees</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['new_employees'] ?? 0; ?></div>
                        <div class="stat-label">New This Month</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo count($stats['departments'] ?? []); ?></div>
                        <div class="stat-label">Departments</div>
                    </div>
                </div>
                
                <?php if (!empty($stats['departments'])): ?>
                <div class="department-section">
                    <h4>Department Distribution</h4>
                    <div class="department-list">
                        <?php foreach ($stats['departments'] as $dept => $count): ?>
                        <div class="department-item">
                            <span class="dept-name"><?php echo htmlspecialchars($dept ?: 'Unassigned'); ?></span>
                            <span class="dept-count"><?php echo $count; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Quick Actions -->
            <div class="actions-section">
                <h3>Quick Actions</h3>
                <div class="actions-grid">
                    <a href="#" class="action-card">
                        <span class="action-icon">👤</span>
                        <span class="action-label">View Profile</span>
                    </a>
                    <a href="#" class="action-card">
                        <span class="action-icon">📅</span>
                        <span class="action-label">Leave Requests</span>
                    </a>
                    <a href="#" class="action-card">
                        <span class="action-icon">💰</span>
                        <span class="action-label">Payroll</span>
                    </a>
                    <a href="#" class="action-card">
                        <span class="action-icon">📊</span>
                        <span class="action-label">Reports</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/dashboard.js"></script>
</body>
</html>