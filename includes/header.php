<?php
// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
// Get current directory for module detection
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo defined('SITE_NAME') ? SITE_NAME : 'HRMS'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <script src="assets/js/header.js" defer></script>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="assets/images/hr-logo.png" alt="<?php echo defined('SITE_NAME') ? SITE_NAME : 'HRMS'; ?>" class="logo-image">
                    <h2>HRMS</h2>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-chevron-left"></i>
                </button>
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
                    <li class="nav-item has-submenu <?php echo (in_array($current_page, ['employee_list.php', 'add_employee.php', 'employee_profile.php']) || strpos($current_page, 'employee') !== false) ? 'open' : ''; ?>">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-users"></i>
                            <span>Employee Management</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="employee_list.php" class="<?php echo ($current_page == 'employee_list.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Employee List</a></li>
                            <li><a href="add_employee.php" class="<?php echo ($current_page == 'add_employee.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Add Employee</a></li>
                            <li><a href="employee_profile.php" class="<?php echo ($current_page == 'employee_profile.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Employee Profile</a></li>
                        </ul>
                    </li>

                    <!-- Office Management -->
                    <li class="nav-item has-submenu <?php echo (in_array($current_page, ['Offices.php', 'positions.php', 'plantilla.php']) || strpos($current_page, 'office') !== false || strpos($current_page, 'position') !== false || strpos($current_page, 'plantilla') !== false) ? 'open' : ''; ?>">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-building"></i>
                            <span>Office Management</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="Offices.php" class="<?php echo ($current_page == 'Offices.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Offices</a></li>
                            <li><a href="positions.php" class="<?php echo ($current_page == 'positions.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Positions</a></li>
                            <li><a href="plantilla.php" class="<?php echo ($current_page == 'plantilla.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Plantilla</a></li>
                        </ul>
                    </li>

                    <!-- Salary & Compensation -->
                    <li class="nav-item has-submenu <?php echo (in_array($current_page, ['salary_grade.php', 'step_increments.php', 'salary_adjustments.php', 'benefits.php', 'payroll.php']) || strpos($current_page, 'salary') !== false || strpos($current_page, 'benefit') !== false || strpos($current_page, 'payroll') !== false) ? 'open' : ''; ?>">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Salary & Compensation</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="salary_grade.php" class="<?php echo ($current_page == 'salary_grade.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Salary Grade Table</a></li>
                            <li><a href="step_increments.php" class="<?php echo ($current_page == 'step_increments.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Step Increments</a></li>
                            <li><a href="salary_adjustments.php" class="<?php echo ($current_page == 'salary_adjustments.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Salary Adjustments</a></li>
                            <li><a href="benefits.php" class="<?php echo ($current_page == 'benefits.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Benefits Management</a></li>
                            <li><a href="payroll.php" class="<?php echo ($current_page == 'payroll.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Payroll Processing</a></li>
                        </ul>
                    </li>
                    
                    <!-- Leave Management -->
                    <li class="nav-item has-submenu <?php echo (in_array($current_page, ['leave_requests.php', 'leave_types.php', 'leave_balances.php', 'approve_leaves.php']) || strpos($current_page, 'leave') !== false) ? 'open' : ''; ?>">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Leave Management</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="leave_requests.php" class="<?php echo ($current_page == 'leave_requests.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Leave Requests</a></li>
                            <li><a href="leave_types.php" class="<?php echo ($current_page == 'leave_types.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Leave Types</a></li>
                            <li><a href="leave_balances.php" class="<?php echo ($current_page == 'leave_balances.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Leave Balances</a></li>
                            <li><a href="approve_leaves.php" class="<?php echo ($current_page == 'approve_leaves.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Approve Leaves</a></li>
                        </ul>
                    </li>
                    
                    <!-- Attendance -->
                    <li class="nav-item has-submenu <?php echo (in_array($current_page, ['daily_time_record.php', 'overtime.php', 'undertime.php', 'attendance_report.php']) || strpos($current_page, 'attendance') !== false || strpos($current_page, 'overtime') !== false || strpos($current_page, 'undertime') !== false) ? 'open' : ''; ?>">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-clock"></i>
                            <span>Attendance</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="daily_time_record.php" class="<?php echo ($current_page == 'daily_time_record.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Daily Time Record</a></li>
                            <li><a href="overtime.php" class="<?php echo ($current_page == 'overtime.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Overtime</a></li>
                            <li><a href="undertime.php" class="<?php echo ($current_page == 'undertime.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Undertime</a></li>
                            <li><a href="attendance_report.php" class="<?php echo ($current_page == 'attendance_report.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Attendance Report</a></li>
                        </ul>
                    </li>
                    
                    <!-- Payroll -->
                    <li class="nav-item has-submenu <?php echo (in_array($current_page, ['salary_computation.php', 'deductions.php', 'benefits_payroll.php', 'payslips.php', '13th_month_pay.php']) || strpos($current_page, 'computation') !== false || strpos($current_page, 'deduction') !== false || strpos($current_page, 'payslip') !== false) ? 'open' : ''; ?>">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-money-bill"></i>
                            <span>Payroll</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="salary_computation.php" class="<?php echo ($current_page == 'salary_computation.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Salary Computation</a></li>
                            <li><a href="deductions.php" class="<?php echo ($current_page == 'deductions.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Deductions</a></li>
                            <li><a href="benefits_payroll.php" class="<?php echo ($current_page == 'benefits_payroll.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Benefits</a></li>
                            <li><a href="payslips.php" class="<?php echo ($current_page == 'payslips.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Payslips</a></li>
                            <li><a href="13th_month_pay.php" class="<?php echo ($current_page == '13th_month_pay.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> 13th Month Pay</a></li>
                        </ul>
                    </li>
                    
                    <!-- Recruitment -->
                    <li class="nav-item has-submenu <?php echo (in_array($current_page, ['job_postings.php', 'applicants.php', 'interviews.php', 'hiring.php']) || strpos($current_page, 'job') !== false || strpos($current_page, 'applicant') !== false || strpos($current_page, 'interview') !== false || strpos($current_page, 'hiring') !== false) ? 'open' : ''; ?>">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-user-plus"></i>
                            <span>Recruitment</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="job_postings.php" class="<?php echo ($current_page == 'job_postings.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Job Postings</a></li>
                            <li><a href="applicants.php" class="<?php echo ($current_page == 'applicants.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Applicants</a></li>
                            <li><a href="interviews.php" class="<?php echo ($current_page == 'interviews.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Interviews</a></li>
                            <li><a href="hiring.php" class="<?php echo ($current_page == 'hiring.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Hiring</a></li>
                        </ul>
                    </li>

                    <!-- Retirement & Benefits -->
                    <li class="nav-item has-submenu <?php echo (in_array($current_page, ['retirement_forecast.php', 'retirement_list.php', 'retirement_benefits.php', 'pension.php', 'gsis.php']) || strpos($current_page, 'retirement') !== false || strpos($current_page, 'pension') !== false || strpos($current_page, 'gsis') !== false) ? 'open' : ''; ?>">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-user-clock"></i>
                            <span>Retirement & Benefits</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="retirement_forecast.php" class="<?php echo ($current_page == 'retirement_forecast.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Retirement Forecast</a></li>
                            <li><a href="retirement_list.php" class="<?php echo ($current_page == 'retirement_list.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Retiring Employees</a></li>
                            <li><a href="retirement_benefits.php" class="<?php echo ($current_page == 'retirement_benefits.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Retirement Benefits</a></li>
                            <li><a href="pension.php" class="<?php echo ($current_page == 'pension.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Pension Management</a></li>
                            <li><a href="gsis.php" class="<?php echo ($current_page == 'gsis.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> GSIS/Pag-IBIG</a></li>
                        </ul>
                    </li>

                    <!-- Loyalty Awards -->
                    <li class="nav-item has-submenu <?php echo (in_array($current_page, ['loyalty_awards.php', 'loyalty_recipients.php', 'years_of_service.php', 'award_nomination.php', 'service_recognition.php']) || strpos($current_page, 'award') !== false || strpos($current_page, 'loyalty') !== false || strpos($current_page, 'recognition') !== false) ? 'open' : ''; ?>">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-award"></i>
                            <span>Loyalty Awards</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="loyalty_awards.php" class="<?php echo ($current_page == 'loyalty_awards.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Awards List</a></li>
                            <li><a href="loyalty_recipients.php" class="<?php echo ($current_page == 'loyalty_recipients.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Award Recipients</a></li>
                            <li><a href="years_of_service.php" class="<?php echo ($current_page == 'years_of_service.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Years of Service</a></li>
                            <li><a href="award_nomination.php" class="<?php echo ($current_page == 'award_nomination.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Award Nomination</a></li>
                            <li><a href="service_recognition.php" class="<?php echo ($current_page == 'service_recognition.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Service Recognition</a></li>
                        </ul>
                    </li>

                    <!-- Service History -->
                    <li class="nav-item has-submenu <?php echo (in_array($current_page, ['employment_history.php', 'position_history.php', 'salary_history.php', 'promotions.php', 'transfers.php']) || strpos($current_page, 'history') !== false || strpos($current_page, 'promotion') !== false || strpos($current_page, 'transfer') !== false) ? 'open' : ''; ?>">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-history"></i>
                            <span>Service History</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="employment_history.php" class="<?php echo ($current_page == 'employment_history.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Employment History</a></li>
                            <li><a href="position_history.php" class="<?php echo ($current_page == 'position_history.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Position History</a></li>
                            <li><a href="salary_history.php" class="<?php echo ($current_page == 'salary_history.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Salary History</a></li>
                            <li><a href="promotions.php" class="<?php echo ($current_page == 'promotions.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Promotions</a></li>
                            <li><a href="transfers.php" class="<?php echo ($current_page == 'transfers.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Transfers</a></li>
                        </ul>
                    </li>
                    
                    <!-- Training -->
                    <li class="nav-item has-submenu <?php echo (in_array($current_page, ['training_programs.php', 'training_nominations.php', 'training_certificates.php', 'training_evaluation.php']) || strpos($current_page, 'training') !== false || strpos($current_page, 'program') !== false) ? 'open' : ''; ?>">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Training</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="training_programs.php" class="<?php echo ($current_page == 'training_programs.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Training Programs</a></li>
                            <li><a href="training_nominations.php" class="<?php echo ($current_page == 'training_nominations.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Nominations</a></li>
                            <li><a href="training_certificates.php" class="<?php echo ($current_page == 'training_certificates.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Certificates</a></li>
                            <li><a href="training_evaluation.php" class="<?php echo ($current_page == 'training_evaluation.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Evaluation</a></li>
                        </ul>
                    </li>
                    
                    <!-- Reports -->
                    <li class="nav-item has-submenu <?php echo (in_array($current_page, ['employee_reports.php', 'leave_reports.php', 'attendance_reports.php', 'payroll_reports.php', 'retirement_reports.php', 'loyalty_reports.php', 'salary_grade_reports.php']) || strpos($current_page, 'report') !== false) ? 'open' : ''; ?>">
                        <a href="#" class="nav-link submenu-toggle">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reports</span>
                            <i class="fas fa-chevron-right submenu-icon"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="employee_reports.php" class="<?php echo ($current_page == 'employee_reports.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Employee Reports</a></li>
                            <li><a href="leave_reports.php" class="<?php echo ($current_page == 'leave_reports.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Leave Reports</a></li>
                            <li><a href="attendance_reports.php" class="<?php echo ($current_page == 'attendance_reports.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Attendance Reports</a></li>
                            <li><a href="payroll_reports.php" class="<?php echo ($current_page == 'payroll_reports.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Payroll Reports</a></li>
                            <li><a href="retirement_reports.php" class="<?php echo ($current_page == 'retirement_reports.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Retirement Reports</a></li>
                            <li><a href="loyalty_reports.php" class="<?php echo ($current_page == 'loyalty_reports.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Loyalty Award Reports</a></li>
                            <li><a href="salary_grade_reports.php" class="<?php echo ($current_page == 'salary_grade_reports.php') ? 'active' : ''; ?>"><i class="fas fa-circle"></i> Salary Grade Reports</a></li>
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
                    <div class="page-title" id="pageTitle">
                        <?php 
                        // Set page title based on current page
                        $page_titles = [
                            'dashboard.php' => 'Dashboard',
                            'employee_list.php' => 'Employee List',
                            'add_employee.php' => 'Add Employee',
                            'employee_profile.php' => 'Employee Profile',
                            'Offices.php' => 'Offices',
                            'positions.php' => 'Positions',
                            'plantilla.php' => 'Plantilla',
                            'salary_grade.php' => 'Salary Grade Table',
                            'step_increments.php' => 'Step Increments',
                            'salary_adjustments.php' => 'Salary Adjustments',
                            'benefits.php' => 'Benefits Management',
                            'payroll.php' => 'Payroll Processing',
                            'leave_requests.php' => 'Leave Requests',
                            'leave_types.php' => 'Leave Types',
                            'leave_balances.php' => 'Leave Balances',
                            'approve_leaves.php' => 'Approve Leaves',
                            'daily_time_record.php' => 'Daily Time Record',
                            'overtime.php' => 'Overtime',
                            'undertime.php' => 'Undertime',
                            'attendance_report.php' => 'Attendance Report',
                            'salary_computation.php' => 'Salary Computation',
                            'deductions.php' => 'Deductions',
                            'benefits_payroll.php' => 'Benefits',
                            'payslips.php' => 'Payslips',
                            '13th_month_pay.php' => '13th Month Pay',
                            'job_postings.php' => 'Job Postings',
                            'applicants.php' => 'Applicants',
                            'interviews.php' => 'Interviews',
                            'hiring.php' => 'Hiring',
                            'retirement_forecast.php' => 'Retirement Forecast',
                            'retirement_list.php' => 'Retiring Employees',
                            'retirement_benefits.php' => 'Retirement Benefits',
                            'pension.php' => 'Pension Management',
                            'gsis.php' => 'GSIS/Pag-IBIG',
                            'loyalty_awards.php' => 'Awards List',
                            'loyalty_recipients.php' => 'Award Recipients',
                            'years_of_service.php' => 'Years of Service',
                            'award_nomination.php' => 'Award Nomination',
                            'service_recognition.php' => 'Service Recognition',
                            'employment_history.php' => 'Employment History',
                            'position_history.php' => 'Position History',
                            'salary_history.php' => 'Salary History',
                            'promotions.php' => 'Promotions',
                            'transfers.php' => 'Transfers',
                            'training_programs.php' => 'Training Programs',
                            'training_nominations.php' => 'Nominations',
                            'training_certificates.php' => 'Certificates',
                            'training_evaluation.php' => 'Evaluation',
                            'employee_reports.php' => 'Employee Reports',
                            'leave_reports.php' => 'Leave Reports',
                            'attendance_reports.php' => 'Attendance Reports',
                            'payroll_reports.php' => 'Payroll Reports',
                            'retirement_reports.php' => 'Retirement Reports',
                            'loyalty_reports.php' => 'Loyalty Award Reports',
                            'salary_grade_reports.php' => 'Salary Grade Reports',
                            'Settings.php' => 'Settings'
                        ];
                        
                        echo isset($page_titles[$current_page]) ? $page_titles[$current_page] : 'HRMS';
                        ?>
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
                                <a href="notifications.php">View All Notifications</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Settings Dropdown -->
                    <div class="settings-dropdown">
                        <i class="fas fa-cog"></i>
                        <div class="settings-menu">
                            <a href="profile_settings.php"><i class="fas fa-user-cog"></i> Profile Settings</a>
                            <a href="change_password.php"><i class="fas fa-lock"></i> Change Password</a>
                            <a href="notification_settings.php"><i class="fas fa-bell"></i> Notification Settings</a>
                            <a href="theme_settings.php"><i class="fas fa-palette"></i> Theme Settings</a>
                            <div class="dropdown-divider"></div>
                            <a href="backup_restore.php"><i class="fas fa-database"></i> Backup & Restore</a>
                            <a href="security_settings.php"><i class="fas fa-shield-alt"></i> Security Settings</a>
                            <div class="dropdown-divider"></div>
                            <a href="user_management.php"><i class="fas fa-users-cog"></i> User Management</a>
                            <a href="system_settings.php"><i class="fas fa-sliders-h"></i> System Settings</a>
                        </div>
                    </div>
                    
                    <!-- User Dropdown -->
                    <div class="user-dropdown">
                        <div class="user-dropdown-toggle">
                            <div class="user-avatar-small">
                                <?php echo strtoupper(substr(($_SESSION['username'] ?? 'User'), 0, 1)); ?>
                            </div>
                            <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="dropdown-menu">
                            <a href="my_profile.php"><i class="fas fa-user"></i> My Profile</a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content Wrapper -->
            <div class="content-wrapper">