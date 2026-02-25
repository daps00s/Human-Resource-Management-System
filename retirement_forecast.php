<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

// Include header
include 'includes/header.php';
?>

<div class="retirement-forecast-container">
    <div class="page-header">
        <div class="header-left">
            <h1><i class="fas fa-user-clock"></i> Retirement Forecast</h1>
            <p>Comprehensive retirement planning and projection for the next decade</p>
        </div>
        <div class="header-actions">
            <button class="btn-primary"><i class="fas fa-file-pdf"></i> Export Report</button>
            <button class="btn-secondary"><i class="fas fa-chart-line"></i> View Trends</button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="forecast-summary">
        <div class="summary-card">
            <div class="summary-icon blue">
                <i class="fas fa-users"></i>
            </div>
            <div class="summary-content">
                <div class="summary-value">156</div>
                <div class="summary-label">Total Workforce</div>
                <div class="summary-trend">+12 this year</div>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-icon orange">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="summary-content">
                <div class="summary-value">23</div>
                <div class="summary-label">Retiring in 5 Years</div>
                <div class="summary-trend warning">15% of workforce</div>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-icon green">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="summary-content">
                <div class="summary-value">8</div>
                <div class="summary-label">Retiring This Year</div>
                <div class="summary-trend">2026</div>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-icon purple">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div class="summary-content">
                <div class="summary-value">65</div>
                <div class="summary-label">Avg Retirement Age</div>
                <div class="summary-trend">Mandatory</div>
            </div>
        </div>
    </div>

    <!-- Retirement Chart Card -->
    <div class="card chart-card">
        <div class="card-header">
            <div class="header-left">
                <h3><i class="fas fa-chart-bar"></i> Retirement Projection (2026-2035)</h3>
                <span class="badge badge-info">Next 10 Years</span>
            </div>
            <div class="chart-legend">
                <span class="legend-item"><span class="legend-color mandatory"></span> Mandatory Retirement</span>
                <span class="legend-item"><span class="legend-color optional"></span> Optional Retirement</span>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <div class="bar-chart">
                    <div class="bar-item">
                        <div class="bar-label">2026</div>
                        <div class="bar-group">
                            <div class="bar mandatory" style="height: 60px;" title="Mandatory: 6">
                                <span class="bar-value">6</span>
                            </div>
                            <div class="bar optional" style="height: 20px;" title="Optional: 2">
                                <span class="bar-value">2</span>
                            </div>
                        </div>
                        <div class="bar-total">8</div>
                    </div>
                    <div class="bar-item">
                        <div class="bar-label">2027</div>
                        <div class="bar-group">
                            <div class="bar mandatory" style="height: 45px;">4</div>
                            <div class="bar optional" style="height: 23px;">2</div>
                        </div>
                        <div class="bar-total">6</div>
                    </div>
                    <div class="bar-item">
                        <div class="bar-label">2028</div>
                        <div class="bar-group">
                            <div class="bar mandatory" style="height: 52px;">5</div>
                            <div class="bar optional" style="height: 21px;">2</div>
                        </div>
                        <div class="bar-total">7</div>
                    </div>
                    <div class="bar-item">
                        <div class="bar-label">2029</div>
                        <div class="bar-group">
                            <div class="bar mandatory" style="height: 38px;">4</div>
                            <div class="bar optional" style="height: 12px;">1</div>
                        </div>
                        <div class="bar-total">5</div>
                    </div>
                    <div class="bar-item">
                        <div class="bar-label">2030</div>
                        <div class="bar-group">
                            <div class="bar mandatory" style="height: 68px;">7</div>
                            <div class="bar optional" style="height: 20px;">2</div>
                        </div>
                        <div class="bar-total">9</div>
                    </div>
                    <div class="bar-item">
                        <div class="bar-label">2031</div>
                        <div class="bar-group">
                            <div class="bar mandatory" style="height: 75px;">8</div>
                            <div class="bar optional" style="height: 20px;">2</div>
                        </div>
                        <div class="bar-total">10</div>
                    </div>
                    <div class="bar-item">
                        <div class="bar-label">2032</div>
                        <div class="bar-group">
                            <div class="bar mandatory" style="height: 52px;">5</div>
                            <div class="bar optional" style="height: 21px;">2</div>
                        </div>
                        <div class="bar-total">7</div>
                    </div>
                    <div class="bar-item">
                        <div class="bar-label">2033</div>
                        <div class="bar-group">
                            <div class="bar mandatory" style="height: 60px;">6</div>
                            <div class="bar optional" style="height: 20px;">2</div>
                        </div>
                        <div class="bar-total">8</div>
                    </div>
                    <div class="bar-item">
                        <div class="bar-label">2034</div>
                        <div class="bar-group">
                            <div class="bar mandatory" style="height: 45px;">4</div>
                            <div class="bar optional" style="height: 23px;">2</div>
                        </div>
                        <div class="bar-total">6</div>
                    </div>
                    <div class="bar-item">
                        <div class="bar-label">2035</div>
                        <div class="bar-group">
                            <div class="bar mandatory" style="height: 83px;">9</div>
                            <div class="bar optional" style="height: 20px;">2</div>
                        </div>
                        <div class="bar-total">11</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Retiring This Year Section -->
    <div class="card">
        <div class="card-header">
            <div class="header-left">
                <h3><i class="fas fa-calendar-alt"></i> Retiring This Year (2026)</h3>
                <span class="badge badge-warning">8 Employees</span>
            </div>
            <div class="header-actions">
                <input type="text" class="search-box" placeholder="Search employees...">
                <select class="filter-select">
                    <option>All Departments</option>
                    <option>HR</option>
                    <option>Finance</option>
                    <option>IT</option>
                    <option>Operations</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Birth Date</th>
                        <th>Retirement Date</th>
                        <th>Years of Service</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="retirement-row">
                        <td>
                            <div class="employee-info">
                                <div class="employee-avatar">JD</div>
                                <div>
                                    <div class="employee-name">Juan Dela Cruz</div>
                                    <div class="employee-email">juan.delacruz@email.com</div>
                                </div>
                            </div>
                        </td>
                        <td>Operations</td>
                        <td>Senior Supervisor</td>
                        <td>Mar 15, 1961</td>
                        <td><span class="date-highlight">Mar 15, 2026</span></td>
                        <td>35 years</td>
                        <td><span class="status-badge pending">Pending</span></td>
                        <td>
                            <div class="action-buttons">
                                <a href="employee_profile.php?id=1" class="action-btn view" title="View Profile"><i class="fas fa-eye"></i></a>
                                <a href="#" class="action-btn process" title="Process Retirement"><i class="fas fa-gavel"></i></a>
                            </div>
                        </td>
                    </tr>
                    <tr class="retirement-row">
                        <td>
                            <div class="employee-info">
                                <div class="employee-avatar">MS</div>
                                <div>
                                    <div class="employee-name">Maria Santos</div>
                                    <div class="employee-email">maria.santos@email.com</div>
                                </div>
                            </div>
                        </td>
                        <td>Finance</td>
                        <td>Accountant III</td>
                        <td>Jun 20, 1961</td>
                        <td><span class="date-highlight">Jun 20, 2026</span></td>
                        <td>32 years</td>
                        <td><span class="status-badge approved">Approved</span></td>
                        <td>
                            <div class="action-buttons">
                                <a href="employee_profile.php?id=2" class="action-btn view" title="View Profile"><i class="fas fa-eye"></i></a>
                                <a href="#" class="action-btn process" title="Process Retirement"><i class="fas fa-gavel"></i></a>
                            </div>
                        </td>
                    </tr>
                    <tr class="retirement-row">
                        <td>
                            <div class="employee-info">
                                <div class="employee-avatar">PR</div>
                                <div>
                                    <div class="employee-name">Pedro Reyes</div>
                                    <div class="employee-email">pedro.reyes@email.com</div>
                                </div>
                            </div>
                        </td>
                        <td>IT</td>
                        <td>Systems Analyst</td>
                        <td>Sep 10, 1961</td>
                        <td><span class="date-highlight">Sep 10, 2026</span></td>
                        <td>30 years</td>
                        <td><span class="status-badge processing">Processing</span></td>
                        <td>
                            <div class="action-buttons">
                                <a href="employee_profile.php?id=3" class="action-btn view" title="View Profile"><i class="fas fa-eye"></i></a>
                                <a href="#" class="action-btn process" title="Process Retirement"><i class="fas fa-gavel"></i></a>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Retirement Analytics Grid -->
    <div class="analytics-grid">
        <!-- Retirement by Department -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-building"></i> Retirement by Department</h3>
                <span class="badge badge-info">Next 5 Years</span>
            </div>
            <div class="card-body">
                <div class="dept-retirement-list">
                    <div class="dept-retirement-item">
                        <div class="dept-info">
                            <div class="dept-name">Human Resources</div>
                            <div class="dept-stats">
                                <span class="stat"><i class="fas fa-users"></i> 12 Total</span>
                                <span class="stat"><i class="fas fa-hourglass-end"></i> 3 Retiring</span>
                            </div>
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 25%;">
                                    <span class="progress-text">25%</span>
                                </div>
                            </div>
                            <div class="progress-years">2026-2028</div>
                        </div>
                    </div>
                    
                    <div class="dept-retirement-item">
                        <div class="dept-info">
                            <div class="dept-name">Finance</div>
                            <div class="dept-stats">
                                <span class="stat"><i class="fas fa-users"></i> 15 Total</span>
                                <span class="stat"><i class="fas fa-hourglass-end"></i> 4 Retiring</span>
                            </div>
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 27%;">
                                    <span class="progress-text">27%</span>
                                </div>
                            </div>
                            <div class="progress-years">2026-2029</div>
                        </div>
                    </div>
                    
                    <div class="dept-retirement-item">
                        <div class="dept-info">
                            <div class="dept-name">Information Technology</div>
                            <div class="dept-stats">
                                <span class="stat"><i class="fas fa-users"></i> 20 Total</span>
                                <span class="stat"><i class="fas fa-hourglass-end"></i> 5 Retiring</span>
                            </div>
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 25%;">
                                    <span class="progress-text">25%</span>
                                </div>
                            </div>
                            <div class="progress-years">2026-2030</div>
                        </div>
                    </div>
                    
                    <div class="dept-retirement-item">
                        <div class="dept-info">
                            <div class="dept-name">Operations</div>
                            <div class="dept-stats">
                                <span class="stat"><i class="fas fa-users"></i> 25 Total</span>
                                <span class="stat"><i class="fas fa-hourglass-end"></i> 7 Retiring</span>
                            </div>
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 28%;">
                                    <span class="progress-text">28%</span>
                                </div>
                            </div>
                            <div class="progress-years">2026-2031</div>
                        </div>
                    </div>
                    
                    <div class="dept-retirement-item">
                        <div class="dept-info">
                            <div class="dept-name">Administration</div>
                            <div class="dept-stats">
                                <span class="stat"><i class="fas fa-users"></i> 18 Total</span>
                                <span class="stat"><i class="fas fa-hourglass-end"></i> 4 Retiring</span>
                            </div>
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 22%;">
                                    <span class="progress-text">22%</span>
                                </div>
                            </div>
                            <div class="progress-years">2027-2030</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Retirement Benefits Summary -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-piggy-bank"></i> Retirement Benefits Projection</h3>
            </div>
            <div class="card-body">
                <div class="benefits-summary">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="benefit-content">
                            <div class="benefit-label">Total Estimated Pensions</div>
                            <div class="benefit-value">₱ 2.5M</div>
                            <div class="benefit-period">Annual</div>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <div class="benefit-content">
                            <div class="benefit-label">Average Monthly Pension</div>
                            <div class="benefit-value">₱ 25,000</div>
                            <div class="benefit-period">Per Retiree</div>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-gift"></i>
                        </div>
                        <div class="benefit-content">
                            <div class="benefit-label">Retirement Benefits</div>
                            <div class="benefit-value">₱ 850,000</div>
                            <div class="benefit-period">One-time</div>
                        </div>
                    </div>
                </div>

                <div class="retirement-timeline">
                    <h4>Critical Years</h4>
                    <div class="timeline-years">
                        <div class="timeline-year warning">
                            <span class="year">2026</span>
                            <span class="count">8 retirees</span>
                        </div>
                        <div class="timeline-year">
                            <span class="year">2027</span>
                            <span class="count">6 retirees</span>
                        </div>
                        <div class="timeline-year">
                            <span class="year">2028</span>
                            <span class="count">7 retirees</span>
                        </div>
                        <div class="timeline-year danger">
                            <span class="year">2030</span>
                            <span class="count">9 retirees</span>
                        </div>
                        <div class="timeline-year danger">
                            <span class="year">2035</span>
                            <span class="count">11 retirees</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Retirement Forecast Container */
.retirement-forecast-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px 0;
}

/* Page Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}

.header-left h1 {
    font-size: 28px;
    color: #2c3e50;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.header-left h1 i {
    color: #3498db;
    font-size: 32px;
}

.header-left p {
    color: #7f8c8d;
    font-size: 14px;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.btn-primary {
    background: #3498db;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-primary:hover {
    background: #2980b9;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
}

.btn-secondary {
    background: #ecf0f1;
    color: #34495e;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-secondary:hover {
    background: #bdc3c7;
    transform: translateY(-2px);
}

/* Forecast Summary Cards */
.forecast-summary {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

.summary-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(to bottom, #3498db, #2ecc71);
}

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.summary-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.summary-icon.blue {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
}

.summary-icon.orange {
    background: linear-gradient(135deg, #e67e22, #d35400);
    color: white;
}

.summary-icon.green {
    background: linear-gradient(135deg, #27ae60, #229954);
    color: white;
}

.summary-icon.purple {
    background: linear-gradient(135deg, #9b59b6, #8e44ad);
    color: white;
}

.summary-content {
    flex: 1;
}

.summary-value {
    font-size: 32px;
    font-weight: 700;
    color: #2c3e50;
    line-height: 1.2;
    margin-bottom: 5px;
}

.summary-label {
    font-size: 13px;
    color: #7f8c8d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-trend {
    font-size: 11px;
    margin-top: 5px;
    color: #27ae60;
}

.summary-trend.warning {
    color: #e67e22;
}

/* Chart Card */
.chart-card {
    margin-bottom: 30px;
}

.card-header {
    padding: 20px;
    border-bottom: 1px solid #ecf0f1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.card-header h3 {
    font-size: 18px;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-header h3 i {
    color: #3498db;
}

.badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.badge-info {
    background: #d4edda;
    color: #155724;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
}

.chart-legend {
    display: flex;
    gap: 20px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #7f8c8d;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}

.legend-color.mandatory {
    background: #e74c3c;
}

.legend-color.optional {
    background: #f39c12;
}

/* Bar Chart */
.chart-container {
    padding: 30px 20px;
    background: #f8fafc;
    border-radius: 8px;
}

.bar-chart {
    display: flex;
    align-items: flex-end;
    justify-content: space-around;
    gap: 15px;
    height: 250px;
}

.bar-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    min-width: 50px;
}

.bar-label {
    font-size: 12px;
    color: #7f8c8d;
    font-weight: 500;
}

.bar-group {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    height: 180px;
    justify-content: flex-end;
}

.bar {
    width: 35px;
    border-radius: 5px 5px 0 0;
    position: relative;
    transition: height 0.3s;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    color: white;
    font-size: 11px;
    font-weight: 600;
    padding-top: 5px;
}

.bar.mandatory {
    background: linear-gradient(to top, #e74c3c, #c0392b);
}

.bar.optional {
    background: linear-gradient(to top, #f39c12, #e67e22);
}

.bar-value {
    position: relative;
    top: -20px;
    color: #2c3e50;
    font-size: 11px;
}

.bar-total {
    font-size: 13px;
    font-weight: 600;
    color: #2c3e50;
    background: #ecf0f1;
    padding: 2px 8px;
    border-radius: 12px;
}

/* Search and Filter */
.search-box {
    padding: 8px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    font-size: 13px;
    min-width: 200px;
}

.search-box:focus {
    outline: none;
    border-color: #3498db;
}

.filter-select {
    padding: 8px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    font-size: 13px;
    background: white;
}

/* Data Table */
.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    text-align: left;
    padding: 15px 12px;
    background: #f8f9fa;
    font-size: 12px;
    color: #7f8c8d;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.data-table td {
    padding: 15px 12px;
    border-bottom: 1px solid #ecf0f1;
    font-size: 13px;
    color: #2c3e50;
}

.data-table tr:hover td {
    background: #f8f9fa;
}

/* Employee Info */
.employee-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.employee-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #3498db, #2980b9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 14px;
    text-transform: uppercase;
}

.employee-name {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 3px;
}

.employee-email {
    font-size: 11px;
    color: #7f8c8d;
}

/* Date Highlight */
.date-highlight {
    font-weight: 600;
    color: #e67e22;
    background: #fff3e0;
    padding: 3px 8px;
    border-radius: 3px;
}

/* Status Badges */
.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
    display: inline-block;
}

.status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.status-badge.approved {
    background: #d4edda;
    color: #155724;
}

.status-badge.processing {
    background: #cce5ff;
    color: #004085;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 8px;
}

.action-btn {
    width: 32px;
    height: 32px;
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-decoration: none;
    transition: all 0.3s;
}

.action-btn.view {
    background: #3498db;
}

.action-btn.view:hover {
    background: #2980b9;
    transform: translateY(-2px);
}

.action-btn.process {
    background: #27ae60;
}

.action-btn.process:hover {
    background: #229954;
    transform: translateY(-2px);
}

/* Analytics Grid */
.analytics-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-top: 25px;
}

/* Department Retirement List */
.dept-retirement-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.dept-retirement-item {
    padding: 10px 0;
    border-bottom: 1px solid #ecf0f1;
}

.dept-retirement-item:last-child {
    border-bottom: none;
}

.dept-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    flex-wrap: wrap;
    gap: 10px;
}

.dept-name {
    font-weight: 600;
    color: #2c3e50;
    font-size: 14px;
}

.dept-stats {
    display: flex;
    gap: 15px;
}

.stat {
    font-size: 12px;
    color: #7f8c8d;
    display: flex;
    align-items: center;
    gap: 5px;
}

.stat i {
    color: #3498db;
}

.progress-container {
    display: flex;
    align-items: center;
    gap: 15px;
}

.progress-bar {
    flex: 1;
    height: 8px;
    background: #ecf0f1;
    border-radius: 4px;
    overflow: hidden;
    position: relative;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3498db, #2ecc71);
    border-radius: 4px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 5px;
}

.progress-text {
    font-size: 10px;
    color: white;
    font-weight: 600;
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
}

.progress-years {
    font-size: 11px;
    color: #7f8c8d;
    min-width: 80px;
}

/* Benefits Summary */
.benefits-summary {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 25px;
}

.benefit-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    transition: transform 0.3s;
}

.benefit-item:hover {
    transform: translateX(5px);
    background: #f0f7ff;
}

.benefit-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #3498db, #2980b9);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 22px;
}

.benefit-content {
    flex: 1;
}

.benefit-label {
    font-size: 12px;
    color: #7f8c8d;
    margin-bottom: 3px;
}

.benefit-value {
    font-size: 20px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 3px;
}

.benefit-period {
    font-size: 11px;
    color: #95a5a6;
}

/* Retirement Timeline */
.retirement-timeline {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #ecf0f1;
}

.retirement-timeline h4 {
    font-size: 14px;
    color: #2c3e50;
    margin-bottom: 15px;
}

.timeline-years {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.timeline-year {
    flex: 1;
    min-width: 80px;
    background: #f8f9fa;
    padding: 12px;
    border-radius: 8px;
    text-align: center;
    border-left: 3px solid #3498db;
}

.timeline-year.warning {
    border-left-color: #f39c12;
    background: #fff3e0;
}

.timeline-year.danger {
    border-left-color: #e74c3c;
    background: #fbe9e7;
}

.timeline-year .year {
    display: block;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
}

.timeline-year .count {
    font-size: 11px;
    color: #7f8c8d;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .forecast-summary {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .analytics-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 992px) {
    .bar-chart {
        overflow-x: auto;
        padding-bottom: 15px;
    }
    
    .bar-item {
        min-width: 60px;
    }
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .header-actions {
        width: 100%;
        flex-direction: column;
    }
    
    .btn-primary, .btn-secondary {
        width: 100%;
        justify-content: center;
    }
    
    .forecast-summary {
        grid-template-columns: 1fr;
    }
    
    .card-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .chart-legend {
        flex-direction: column;
        gap: 5px;
    }
    
    .data-table {
        display: block;
        overflow-x: auto;
    }
    
    .dept-info {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .dept-stats {
        flex-wrap: wrap;
    }
    
    .progress-container {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .progress-bar {
        width: 100%;
    }
    
    .timeline-years {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .summary-card {
        flex-direction: column;
        text-align: center;
        padding: 20px;
    }
    
    .summary-icon {
        margin: 0 auto;
    }
    
    .employee-info {
        flex-direction: column;
        text-align: center;
    }
    
    .action-buttons {
        justify-content: center;
    }
    
    .benefit-item {
        flex-direction: column;
        text-align: center;
    }
}
</style>
     