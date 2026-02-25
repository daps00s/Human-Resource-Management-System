<?php
// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="assets/images/hr-logo.png" alt="<?php echo SITE_NAME; ?>" class="logo-image">
                    <h2>HRMS</h2>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <!-- Dashboard -->
                    <li class="nav-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                        <a href="dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <!-- Employee Management -->
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-users"></i>
                            <span>Employee Management</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="employee_list.php"><i class="fas fa-circle"></i> Employee List</a></li>
                            <li><a href="add_employee.php"><i class="fas fa-circle"></i> Add Employee</a></li>
                            <li><a href="employee_profile.php"><i class="fas fa-circle"></i> Employee Profile</a></li>
                            <li><a href="offices.php"><i class="fas fa-circle"></i> Offices</a></li>
                        </ul>
                    </li>

                    <!-- Salary & Compensation -->
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Salary & Compensation</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="salary_grade.php"><i class="fas fa-circle"></i> Salary Grade Table</a></li>
                            <li><a href="step_increments.php"><i class="fas fa-circle"></i> Step Increments</a></li>
                            <li><a href="salary_adjustments.php"><i class="fas fa-circle"></i> Salary Adjustments</a></li>
                            <li><a href="benefits.php"><i class="fas fa-circle"></i> Benefits Management</a></li>
                            <li><a href="payroll.php"><i class="fas fa-circle"></i> Payroll Processing</a></li>
                        </ul>
                    </li>
                    
                    <!-- Leave Management -->
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Leave Management</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="#"><i class="fas fa-circle"></i> Leave Requests</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Leave Types</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Leave Balances</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Approve Leaves</a></li>
                        </ul>
                    </li>
                    
                    <!-- Attendance -->
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-clock"></i>
                            <span>Attendance</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="#"><i class="fas fa-circle"></i> Daily Time Record</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Overtime</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Undertime</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Attendance Report</a></li>
                        </ul>
                    </li>
                    
                    <!-- Payroll -->
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-money-bill"></i>
                            <span>Payroll</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="#"><i class="fas fa-circle"></i> Salary Computation</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Deductions</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Benefits</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Payslips</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> 13th Month Pay</a></li>
                        </ul>
                    </li>
                    
                    <!-- Recruitment -->
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-user-plus"></i>
                            <span>Recruitment</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="#"><i class="fas fa-circle"></i> Job Postings</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Applicants</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Interviews</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Hiring</a></li>
                        </ul>
                    </li>

                    <!-- Retirement & Benefits -->
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-user-clock"></i>
                            <span>Retirement & Benefits</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="retirement_forecast.php"><i class="fas fa-circle"></i> Retirement Forecast</a></li>
                            <li><a href="retirement_list.php"><i class="fas fa-circle"></i> Retiring Employees</a></li>
                            <li><a href="retirement_benefits.php"><i class="fas fa-circle"></i> Retirement Benefits</a></li>
                            <li><a href="pension.php"><i class="fas fa-circle"></i> Pension Management</a></li>
                            <li><a href="gsis.php"><i class="fas fa-circle"></i> GSIS/Pag-IBIG</a></li>
                        </ul>
                    </li>

                    <!-- Loyalty Awards -->
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-award"></i>
                            <span>Loyalty Awards</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="loyalty_awards.php"><i class="fas fa-circle"></i> Awards List</a></li>
                            <li><a href="loyalty_recipients.php"><i class="fas fa-circle"></i> Award Recipients</a></li>
                            <li><a href="years_of_service.php"><i class="fas fa-circle"></i> Years of Service</a></li>
                            <li><a href="award_nomination.php"><i class="fas fa-circle"></i> Award Nomination</a></li>
                            <li><a href="service_recognition.php"><i class="fas fa-circle"></i> Service Recognition</a></li>
                        </ul>
                    </li>

                    <!-- Service History -->
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-history"></i>
                            <span>Service History</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="employment_history.php"><i class="fas fa-circle"></i> Employment History</a></li>
                            <li><a href="position_history.php"><i class="fas fa-circle"></i> Position History</a></li>
                            <li><a href="salary_history.php"><i class="fas fa-circle"></i> Salary History</a></li>
                            <li><a href="promotions.php"><i class="fas fa-circle"></i> Promotions</a></li>
                            <li><a href="transfers.php"><i class="fas fa-circle"></i> Transfers</a></li>
                        </ul>
                    </li>
                    
                    <!-- Training -->
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Training</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="#"><i class="fas fa-circle"></i> Training Programs</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Nominations</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Certificates</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Evaluation</a></li>
                        </ul>
                    </li>
                    
                    <!-- Reports -->
                    <li class="nav-item has-submenu">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reports</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="#"><i class="fas fa-circle"></i> Employee Reports</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Leave Reports</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Attendance Reports</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Payroll Reports</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Retirement Reports</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Loyalty Award Reports</a></li>
                            <li><a href="#"><i class="fas fa-circle"></i> Salary Grade Reports</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
                
                <a href="Settings.php" class="logout-btn">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>

            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <!-- Top Navigation Bar -->
            <header class="top-navbar">
                <div class="top-nav-left">
                    <button class="mobile-toggle" id="mobileToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="page-title">
                        Dashboard
                    </div>
                </div>
                
                <div class="top-nav-right">
                    <!-- Notifications -->
                    <div class="notifications">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">5</span>
                        <div class="notifications-dropdown">
                            <div class="notifications-header">
                                <h4>Notifications</h4>
                                <span class="mark-read">Mark all as read</span>
                            </div>
                            <div class="notifications-list">
                                <div class="notification-item unread">
                                    <div class="notification-icon">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p class="notification-text">New leave request from Juan Dela Cruz</p>
                                        <span class="notification-time">5 minutes ago</span>
                                    </div>
                                </div>
                                <div class="notification-item unread">
                                    <div class="notification-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p class="notification-text">Overtime request pending approval</p>
                                        <span class="notification-time">1 hour ago</span>
                                    </div>
                                </div>
                                <div class="notification-item">
                                    <div class="notification-icon">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p class="notification-text">Training session scheduled for tomorrow</p>
                                        <span class="notification-time">3 hours ago</span>
                                    </div>
                                </div>
                                <div class="notification-item">
                                    <div class="notification-icon">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div class="notification-content">
                                        <p class="notification-text">Payroll report for March is ready</p>
                                        <span class="notification-time">Yesterday</span>
                                    </div>
                                </div>
                            </div>
                            <div class="notifications-footer">
                                <a href="#">View All Notifications</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Settings Dropdown -->
                    <div class="settings-dropdown">
                        <i class="fas fa-cog"></i>
                        <div class="settings-menu">
                            <a href="#"><i class="fas fa-user-cog"></i> Profile Settings</a>
                            <a href="#"><i class="fas fa-lock"></i> Change Password</a>
                            <a href="#"><i class="fas fa-bell"></i> Notification Settings</a>
                            <a href="#"><i class="fas fa-palette"></i> Theme Settings</a>
                            <div class="dropdown-divider"></div>
                            <a href="#"><i class="fas fa-database"></i> Backup & Restore</a>
                            <a href="#"><i class="fas fa-shield-alt"></i> Security Settings</a>
                            <div class="dropdown-divider"></div>
                            <a href="#"><i class="fas fa-users-cog"></i> User Management</a>
                            <a href="#"><i class="fas fa-sliders-h"></i> System Settings</a>
                        </div>
                    </div>
                    
                    <!-- User Dropdown -->
                    <div class="user-dropdown">
                        <div class="user-dropdown-toggle">
                            <div class="user-avatar-small">
                                <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                            </div>
                            <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="dropdown-menu">
                            <a href="#"><i class="fas fa-user"></i> My Profile</a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content Wrapper -->
            <div class="content-wrapper">