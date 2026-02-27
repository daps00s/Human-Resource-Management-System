<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Get current month and year
$current_month = date('m');
$current_year = date('Y');
$message = '';
$message_type = '';

if (isset($_GET['month']) && isset($_GET['year'])) {
    $current_month = $_GET['month'];
    $current_year = $_GET['year'];
}

// Handle payroll processing
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'process_payroll') {
        $payroll_date = $_POST['payroll_date'];
        $payroll_period = $_POST['payroll_period'];
        $approving_officer = $_POST['approving_officer'];
        $remarks = $_POST['remarks'];
        
        $message = "Payroll for " . date('F Y', strtotime($current_year . '-' . $current_month . '-01')) . " has been processed successfully!";
        $message_type = "success";
    }
    
    if ($_POST['action'] === 'generate_payslips') {
        $payslip_format = $_POST['payslip_format'];
        $include_deductions = isset($_POST['include_deductions']) ? 'Yes' : 'No';
        $include_contributions = isset($_POST['include_contributions']) ? 'Yes' : 'No';
        $delivery_method = $_POST['delivery_method'];
        
        $message = "Payslips are being generated in $payslip_format format. They will be sent via $delivery_method.";
        $message_type = "success";
    }
}

// Fetch employees for payroll
$payroll_sql = "
    SELECT 
        u.id,
        u.employee_id,
        u.first_name,
        u.last_name,
        u.email,
        o.office_name,
        ee.position,
        ee.monthly_salary as basic_salary,
        sg.salary_grade,
        ee.step,
        COALESCE((
            SELECT SUM(ABS(new_monthly_salary - old_monthly_salary)) FROM salary_history 
            WHERE user_id = u.id 
            AND adjustment_type IN ('merit_increase', 'salary_adjustment')
            AND MONTH(adjustment_date) = ? 
            AND YEAR(adjustment_date) = ?
        ), 0) as adjustments,
        COALESCE((
            SELECT SUM(ABS(new_monthly_salary - old_monthly_salary)) FROM salary_history 
            WHERE user_id = u.id 
            AND adjustment_type = 'demotion'
            AND MONTH(adjustment_date) = ? 
            AND YEAR(adjustment_date) = ?
        ), 0) as deductions
    FROM users u
    JOIN employee_employment ee ON u.id = ee.user_id
    JOIN salary_grades sg ON ee.salary_grade_id = sg.id
    LEFT JOIN offices o ON ee.office_id = o.id
    WHERE u.role != 'admin'
    ORDER BY o.office_name, u.last_name
";
$stmt = $conn->prepare($payroll_sql);
$stmt->bind_param("ssss", $current_month, $current_year, $current_month, $current_year);
$stmt->execute();
$employees = $stmt->get_result();

// Calculate totals
$total_basic = 0;
$total_adjustments = 0;
$total_deductions = 0;
$total_net = 0;
$employees->data_seek(0);
while ($emp = $employees->fetch_assoc()) {
    $total_basic += $emp['basic_salary'];
    $total_adjustments += $emp['adjustments'];
    $total_deductions += $emp['deductions'];
    $total_net += ($emp['basic_salary'] + $emp['adjustments'] - $emp['deductions']);
}
$employees->data_seek(0);

// Get contribution rates
$gsis_rate = 0.09;
$pagibig_rate = 0.02;
$philhealth_rate = 0.03;
$gsis_max = 90000;
$pagibig_max = 5000;
$philhealth_max = 80000;
?>

<!-- Dashboard Container -->
<div class="dashboard-container">

<!-- Content Header -->
<div class="content-header">
    <div class="welcome-banner">
        <div class="welcome-content">
            <h1><i class="fas fa-file-invoice-dollar"></i> Payroll Processing</h1>
            <p>Manage and process monthly payroll for <?php echo date('F Y', strtotime($current_year . '-' . $current_month . '-01')); ?></p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn-primary" onclick="openProcessPayrollModal()">
                <i class="fas fa-calculator"></i> Process Payroll
            </button>
            <button type="button" class="btn-primary" onclick="openGeneratePayslipsModal()">
                <i class="fas fa-file-pdf"></i> Generate Payslips
            </button>
            <button type="button" class="btn-primary" onclick="exportPayroll()">
                <i class="fas fa-file-excel"></i> Export
            </button>
        </div>
    </div>
    
    <div class="breadcrumb-wrapper">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="salary_grade.php">Salary & Compensation</a></li>
                <li class="breadcrumb-item active">Payroll Processing</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Alert Messages -->
<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>">
        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<!-- Month Selector -->
<div class="month-selector">
    <form method="GET" class="month-form">
        <div class="form-group">
            <label for="month">Month</label>
            <select name="month" id="month" class="form-control">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" 
                        <?php echo $m == $current_month ? 'selected' : ''; ?>>
                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="year">Year</label>
            <select name="year" id="year" class="form-control">
                <?php for ($y = $current_year - 2; $y <= $current_year + 1; $y++): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y == $current_year ? 'selected' : ''; ?>>
                        <?php echo $y; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        
        <button type="submit" class="btn-primary" style="margin-top: 24px;">
            <i class="fas fa-search"></i> View
        </button>
    </form>
</div>

<!-- Payroll Summary Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon bg-primary">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $employees->num_rows; ?></div>
            <div class="stat-label">Total Employees</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon bg-success">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">₱<?php echo number_format($total_basic, 2); ?></div>
            <div class="stat-label">Total Basic Salary</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon bg-warning">
            <i class="fas fa-plus-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">₱<?php echo number_format($total_adjustments, 2); ?></div>
            <div class="stat-label">Total Adjustments</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon bg-danger">
            <i class="fas fa-minus-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">₱<?php echo number_format($total_deductions, 2); ?></div>
            <div class="stat-label">Total Deductions</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon bg-info">
            <i class="fas fa-file-invoice"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value">₱<?php echo number_format($total_net, 2); ?></div>
            <div class="stat-label">Net Payroll</div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="quick-stats">
    <div class="quick-stat-item">
        <span class="quick-stat-label">Average Salary</span>
        <span class="quick-stat-value">₱<?php echo $employees->num_rows > 0 ? number_format($total_basic / $employees->num_rows, 2) : '0.00'; ?></span>
    </div>
    <div class="quick-stat-item">
        <span class="quick-stat-label">Payroll Period</span>
        <span class="quick-stat-value"><?php echo date('F 1-', strtotime($current_year . '-' . $current_month . '-01')) . date('t, Y', strtotime($current_year . '-' . $current_month . '-01')); ?></span>
    </div>
    <div class="quick-stat-item">
        <span class="quick-stat-label">Status</span>
        <span class="quick-stat-value status-badge status-pending">Pending</span>
    </div>
</div>

<!-- Payroll Table -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> Payroll Details - <?php echo date('F Y', strtotime($current_year . '-' . $current_month . '-01')); ?></h3>
        <div class="card-actions">
            <div class="filter-group">
                <select class="filter-select" id="officeFilter">
                    <option value="">All Offices</option>
                    <?php
                    $offices = $conn->query("SELECT id, office_name FROM offices WHERE status = 'active' ORDER BY office_name");
                    while ($office = $offices->fetch_assoc()) {
                        echo "<option value='{$office['office_name']}'>{$office['office_name']}</option>";
                    }
                    ?>
                </select>
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-box" id="searchEmployee" placeholder="Search employee...">
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table" id="payrollTable">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Office</th>
                        <th>Position</th>
                        <th>Grade/Step</th>
                        <th>Basic Salary</th>
                        <th>Adjustments</th>
                        <th>Gross Pay</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $employees->data_seek(0);
                    $row_count = 0;
                    while ($emp = $employees->fetch_assoc()): 
                        $row_count++;
                        $gross = $emp['basic_salary'] + $emp['adjustments'];
                        
                        // Calculate government contributions
                        $gsis_contribution = min($emp['basic_salary'], $gsis_max) * $gsis_rate;
                        $pagibig_contribution = min($emp['basic_salary'], $pagibig_max) * $pagibig_rate;
                        $philhealth_contribution = min($emp['basic_salary'], $philhealth_max) * $philhealth_rate;
                        $total_deductions = $gsis_contribution + $pagibig_contribution + $philhealth_contribution + $emp['deductions'];
                        $net = $gross - $total_deductions;
                        
                        $row_class = $row_count % 2 == 0 ? 'even-row' : 'odd-row';
                    ?>
                    <tr class="<?php echo $row_class; ?>" data-office="<?php echo htmlspecialchars($emp['office_name']); ?>">
                        <td>
                            <div class="employee-info">
                                <div class="employee-avatar">
                                    <?php 
                                    $initial = strtoupper(substr($emp['first_name'] ?? 'U', 0, 1) . substr($emp['last_name'] ?? 'N', 0, 1));
                                    $colors = ['#3498db', '#27ae60', '#f39c12', '#e74c3c', '#9b59b6'];
                                    $color_index = $row_count % count($colors);
                                    ?>
                                    <div class="avatar-circle" style="background: <?php echo $colors[$color_index]; ?>;">
                                        <?php echo $initial; ?>
                                    </div>
                                </div>
                                <div>
                                    <div class="employee-name"><?php echo htmlspecialchars($emp['last_name'] . ', ' . $emp['first_name']); ?></div>
                                    <div class="employee-id"><?php echo htmlspecialchars($emp['employee_id']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($emp['office_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($emp['position'] ?? 'N/A'); ?></td>
                        <td><span class="grade-badge"><?php echo $emp['salary_grade'] . ' Step ' . $emp['step']; ?></span></td>
                        <td class="amount">₱<?php echo number_format($emp['basic_salary'], 2); ?></td>
                        <td class="amount <?php echo $emp['adjustments'] > 0 ? 'text-success' : ''; ?>">
                            <?php echo $emp['adjustments'] > 0 ? '+' : ''; ?>₱<?php echo number_format($emp['adjustments'], 2); ?>
                        </td>
                        <td class="amount">₱<?php echo number_format($gross, 2); ?></td>
                        <td class="amount text-danger">₱<?php echo number_format($total_deductions, 2); ?></td>
                        <td class="amount"><strong>₱<?php echo number_format($net, 2); ?></strong></td>
                        <td>
                            <button class="btn-icon" onclick="viewPayslip(<?php echo $emp['id']; ?>)" title="View Payslip">
                                <i class="fas fa-file-invoice"></i>
                            </button>
                            <button class="btn-icon" onclick="viewBreakdown(<?php echo $emp['id']; ?>)" title="View Breakdown">
                                <i class="fas fa-chart-pie"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    
                    <?php if ($employees->num_rows == 0): ?>
                    <tr>
                        <td colspan="10" class="empty-table">
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <h3>No Employees Found</h3>
                                <p>There are no employees in the payroll for this period.</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Table Footer with Summary -->
        <div class="table-footer">
            <div class="summary-info">
                <span><i class="fas fa-users"></i> Total Employees: <strong><?php echo $employees->num_rows; ?></strong></span>
                <span class="separator">|</span>
                <span><i class="fas fa-money-bill-wave"></i> Gross Payroll: <strong>₱<?php echo number_format($total_basic + $total_adjustments, 2); ?></strong></span>
                <span class="separator">|</span>
                <span><i class="fas fa-file-invoice"></i> Net Payroll: <strong>₱<?php echo number_format($total_net, 2); ?></strong></span>
            </div>
        </div>
    </div>
</div>

<!-- Contribution Rates Card -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-percent"></i> Government Contribution Rates</h3>
    </div>
    <div class="card-body">
        <div class="rates-grid">
            <div class="rate-item">
                <span class="rate-label">GSIS</span>
                <span class="rate-value"><?php echo ($gsis_rate * 100); ?>%</span>
                <span class="rate-max">Max: ₱<?php echo number_format($gsis_max, 2); ?></span>
            </div>
            <div class="rate-item">
                <span class="rate-label">Pag-IBIG</span>
                <span class="rate-value"><?php echo ($pagibig_rate * 100); ?>%</span>
                <span class="rate-max">Max: ₱<?php echo number_format($pagibig_max, 2); ?></span>
            </div>
            <div class="rate-item">
                <span class="rate-label">PhilHealth</span>
                <span class="rate-value"><?php echo ($philhealth_rate * 100); ?>%</span>
                <span class="rate-max">Max: ₱<?php echo number_format($philhealth_max, 2); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Process Payroll Modal -->
<div class="modal" id="processPayrollModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calculator"></i> Process Payroll</h5>
                <button type="button" class="btn-close" onclick="closeModal('processPayrollModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="process_payroll">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Payroll Period</label>
                        <input type="text" class="form-control" value="<?php echo date('F Y', strtotime($current_year . '-' . $current_month . '-01')); ?>" readonly>
                        <input type="hidden" name="payroll_period" value="<?php echo $current_year . '-' . $current_month; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="payroll_date">Payroll Date <span class="required">*</span></label>
                        <input type="date" class="form-control" id="payroll_date" name="payroll_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="approving_officer">Approving Officer <span class="required">*</span></label>
                        <select class="form-control" id="approving_officer" name="approving_officer" required>
                            <option value="">Select Approving Officer</option>
                            <option value="HR Manager">HR Manager</option>
                            <option value="Finance Director">Finance Director</option>
                            <option value="CEO">CEO</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="remarks">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3" placeholder="Enter any remarks about this payroll..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <p>This will process payroll for <strong><?php echo $employees->num_rows; ?></strong> employees with a total net amount of <strong>₱<?php echo number_format($total_net, 2); ?></strong>.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('processPayrollModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Process Payroll</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Generate Payslips Modal -->
<div class="modal" id="generatePayslipsModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-pdf"></i> Generate Payslips</h5>
                <button type="button" class="btn-close" onclick="closeModal('generatePayslipsModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="generate_payslips">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Payroll Period</label>
                        <input type="text" class="form-control" value="<?php echo date('F Y', strtotime($current_year . '-' . $current_month . '-01')); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="payslip_format">Payslip Format <span class="required">*</span></label>
                        <select class="form-control" id="payslip_format" name="payslip_format" required>
                            <option value="">Select Format</option>
                            <option value="PDF">PDF Document</option>
                            <option value="Excel">Excel Spreadsheet</option>
                            <option value="CSV">CSV File</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="delivery_method">Delivery Method <span class="required">*</span></label>
                        <select class="form-control" id="delivery_method" name="delivery_method" required>
                            <option value="">Select Delivery Method</option>
                            <option value="Email">Send via Email</option>
                            <option value="Download">Download Only</option>
                        </select>
                    </div>
                    
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="include_deductions" value="1" checked>
                            <span>Include detailed deductions</span>
                        </label>
                    </div>
                    
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="include_contributions" value="1" checked>
                            <span>Include government contributions</span>
                        </label>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <p>Payslips will be generated for <strong><?php echo $employees->num_rows; ?></strong> employees.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('generatePayslipsModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Generate Payslips</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Breakdown Modal -->
<div class="modal" id="breakdownModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-chart-pie"></i> Salary Breakdown</h5>
                <button type="button" class="btn-close" onclick="closeModal('breakdownModal')">&times;</button>
            </div>
            <div class="modal-body" id="breakdownContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('breakdownModal')">Close</button>
                <button type="button" class="btn-primary" onclick="printBreakdown()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

</div>

<script>
// Modal functions
function openProcessPayrollModal() {
    document.getElementById('processPayrollModal').classList.add('show');
}

function openGeneratePayslipsModal() {
    document.getElementById('generatePayslipsModal').classList.add('show');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

function exportPayroll() {
    window.location.href = 'export_payroll.php?month=<?php echo $current_month; ?>&year=<?php echo $current_year; ?>';
}

function viewPayslip(id) {
    window.location.href = 'view_payslip.php?id=' + id + '&month=<?php echo $current_month; ?>&year=<?php echo $current_year; ?>';
}

function viewBreakdown(id) {
    // Find the employee data
    <?php 
    $employees->data_seek(0);
    $employee_data = [];
    while ($emp = $employees->fetch_assoc()) {
        $employee_data[] = $emp;
    }
    $employees->data_seek(0);
    ?>
    
    const employees = <?php echo json_encode($employee_data); ?>;
    const emp = employees.find(e => e.id == id);
    
    if (emp) {
        const salary = parseFloat(emp.basic_salary);
        const adjustments = parseFloat(emp.adjustments);
        const deductions = parseFloat(emp.deductions);
        const gross = salary + adjustments;
        
        // Calculate contributions
        const gsisRate = <?php echo $gsis_rate; ?>;
        const pagibigRate = <?php echo $pagibig_rate; ?>;
        const philhealthRate = <?php echo $philhealth_rate; ?>;
        const gsisMax = <?php echo $gsis_max; ?>;
        const pagibigMax = <?php echo $pagibig_max; ?>;
        const philhealthMax = <?php echo $philhealth_max; ?>;
        
        const gsisCont = Math.min(salary, gsisMax) * gsisRate;
        const pagibigCont = Math.min(salary, pagibigMax) * pagibigRate;
        const philhealthCont = Math.min(salary, philhealthMax) * philhealthRate;
        const totalDeductions = gsisCont + pagibigCont + philhealthCont + deductions;
        const net = gross - totalDeductions;
        
        const breakdown = `
            <div class="breakdown-container">
                <div class="breakdown-header">
                    <h4>${emp.last_name}, ${emp.first_name}</h4>
                    <p class="text-muted">Employee ID: ${emp.employee_id} | ${emp.position} | ${emp.office_name || 'N/A'}</p>
                    <p class="text-muted">Payroll Period: <?php echo date('F Y', strtotime($current_year . '-' . $current_month . '-01')); ?></p>
                </div>
                
                <div class="breakdown-section">
                    <h6>Earnings</h6>
                    <table class="breakdown-table">
                        <tr>
                            <td>Basic Salary (${emp.salary_grade} Step ${emp.step})</td>
                            <td class="amount">₱${salary.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}</td>
                        </tr>
                        <tr>
                            <td>Adjustments</td>
                            <td class="amount text-success">+₱${adjustments.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}</td>
                        </tr>
                        <tr class="total-row">
                            <td>Gross Pay</td>
                            <td class="amount">₱${gross.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="breakdown-section">
                    <h6>Deductions</h6>
                    <table class="breakdown-table">
                        <tr>
                            <td>GSIS (${gsisRate * 100}%)</td>
                            <td class="amount text-danger">-₱${gsisCont.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}</td>
                        </tr>
                        <tr>
                            <td>Pag-IBIG (${pagibigRate * 100}%)</td>
                            <td class="amount text-danger">-₱${pagibigCont.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}</td>
                        </tr>
                        <tr>
                            <td>PhilHealth (${philhealthRate * 100}%)</td>
                            <td class="amount text-danger">-₱${philhealthCont.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}</td>
                        </tr>
                        <tr>
                            <td>Other Deductions</td>
                            <td class="amount text-danger">-₱${deductions.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}</td>
                        </tr>
                        <tr class="total-row">
                            <td>Total Deductions</td>
                            <td class="amount text-danger">-₱${totalDeductions.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="breakdown-total">
                    <span>Net Pay</span>
                    <span class="net-amount">₱${net.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}</span>
                </div>
            </div>
        `;
        
        document.getElementById('breakdownContent').innerHTML = breakdown;
        document.getElementById('breakdownModal').classList.add('show');
    }
}

function printBreakdown() {
    const printContent = document.getElementById('breakdownContent').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Salary Breakdown</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    .breakdown-container { max-width: 600px; margin: 0 auto; }
                    .breakdown-header { margin-bottom: 20px; }
                    .breakdown-section { margin-bottom: 20px; }
                    .breakdown-table { width: 100%; border-collapse: collapse; }
                    .breakdown-table td { padding: 8px; border-bottom: 1px solid #ddd; }
                    .total-row { font-weight: bold; background: #f5f5f5; }
                    .amount { text-align: right; }
                    .text-success { color: #27ae60; }
                    .text-danger { color: #e74c3c; }
                    .breakdown-total { 
                        margin-top: 20px; 
                        padding: 15px; 
                        background: #2c3e50; 
                        color: white; 
                        display: flex; 
                        justify-content: space-between;
                        font-size: 18px;
                        font-weight: bold;
                        border-radius: 4px;
                    }
                    .net-amount { color: #27ae60; }
                </style>
            </head>
            <body>
                ${printContent}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Search and filter functionality
document.getElementById('searchEmployee').addEventListener('keyup', filterTable);
document.getElementById('officeFilter').addEventListener('change', filterTable);

function filterTable() {
    const search = document.getElementById('searchEmployee').value.toLowerCase();
    const office = document.getElementById('officeFilter').value;
    const table = document.getElementById('payrollTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        if (row.classList.contains('empty-table-row')) continue;
        
        const employeeName = row.cells[0]?.innerText.toLowerCase() || '';
        const employeeId = row.querySelector('.employee-id')?.innerText.toLowerCase() || '';
        const officeCell = row.getAttribute('data-office') || '';
        
        let show = true;
        
        if (search && !employeeName.includes(search) && !employeeId.includes(search)) {
            show = false;
        }
        
        if (office && officeCell !== office) {
            show = false;
        }
        
        row.style.display = show ? '' : 'none';
    }
}

// Close modals when clicking outside
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});

// Prevent modal from closing when clicking inside modal content
document.querySelectorAll('.modal-content').forEach(content => {
    content.addEventListener('click', function(e) {
        e.stopPropagation();
    });
});
</script>

<style>
/* Dashboard Container */
.dashboard-container {
    max-width: 1300px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
}

/* Welcome Banner */
.welcome-banner {
    background: linear-gradient(135deg, #1e2b3a 0%, #2c3e50 100%);
    border-radius: 8px;
    padding: 20px 25px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.welcome-content h1 {
    font-size: 24px;
    margin: 0 0 5px;
    font-weight: 500;
}

.welcome-content p {
    font-size: 13px;
    margin: 0;
    opacity: 0.8;
}

.btn-primary {
    background: rgba(255,255,255,0.15);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.btn-primary:hover {
    background: rgba(255,255,255,0.25);
    border-color: rgba(255,255,255,0.5);
}

/* Breadcrumb */
.breadcrumb-wrapper {
    margin-bottom: 15px;
}

.breadcrumb {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    font-size: 13px;
}

.breadcrumb-item a {
    color: #3498db;
    text-decoration: none;
}

.breadcrumb-item.active {
    color: #7f8c8d;
}

.breadcrumb-item + .breadcrumb-item:before {
    content: "/";
    padding: 0 8px;
    color: #bdc3c7;
}

/* Month Selector */
.month-selector {
    background: white;
    border-radius: 6px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.month-form {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.month-form .form-group {
    margin-bottom: 0;
    min-width: 150px;
}

.month-form label {
    display: block;
    font-size: 12px;
    color: #7f8c8d;
    margin-bottom: 5px;
}

.month-form .form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 15px;
    margin-bottom: 15px;
}

.stat-card {
    background: white;
    border-radius: 6px;
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 45px;
    height: 45px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
}

.bg-primary { background: #3498db; }
.bg-success { background: #27ae60; }
.bg-warning { background: #f39c12; }
.bg-danger { background: #e74c3c; }
.bg-info { background: #3498db; }

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 20px;
    font-weight: 600;
    color: #2c3e50;
    line-height: 1.2;
}

.stat-label {
    font-size: 11px;
    color: #7f8c8d;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* Quick Stats */
.quick-stats {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    background: white;
    padding: 12px 15px;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.quick-stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-right: 15px;
    border-right: 1px solid #ecf0f1;
}

.quick-stat-item:last-child {
    border-right: none;
}

.quick-stat-label {
    font-size: 12px;
    color: #7f8c8d;
}

.quick-stat-value {
    font-size: 14px;
    font-weight: 500;
    color: #2c3e50;
}

.status-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.status-pending {
    background: #fef9e7;
    color: #f39c12;
}

/* Card */
.card {
    background: white;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ecf0f1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.card-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.card-header h3 i {
    color: #3498db;
}

.card-actions {
    display: flex;
    gap: 10px;
}

.filter-group {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-select {
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 12px;
    min-width: 140px;
}

.search-wrapper {
    position: relative;
}

.search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #95a5a6;
    font-size: 12px;
}

.search-box {
    padding: 6px 10px 6px 30px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 12px;
    width: 200px;
}

.card-body {
    padding: 20px;
}

/* Table */
.table-responsive {
    overflow-x: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.table th {
    background: #f8f9fa;
    padding: 12px 10px;
    font-weight: 500;
    color: #2c3e50;
    border-bottom: 2px solid #ecf0f1;
    text-align: left;
    white-space: nowrap;
}

.table td {
    padding: 12px 10px;
    border-bottom: 1px solid #ecf0f1;
    vertical-align: middle;
}

.table tr.even-row {
    background: #fafafa;
}

.table tr:hover {
    background: #f5f6fa;
}

/* Empty State */
.empty-table {
    text-align: center;
    padding: 40px !important;
}

.empty-state {
    text-align: center;
    color: #95a5a6;
}

.empty-state i {
    font-size: 40px;
    margin-bottom: 10px;
    color: #d0d7de;
}

.empty-state h3 {
    font-size: 16px;
    color: #7f8c8d;
    margin: 0 0 5px;
}

.empty-state p {
    font-size: 13px;
    margin: 0;
}

/* Employee Info */
.employee-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.avatar-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 13px;
}

.employee-name {
    font-weight: 500;
    color: #2c3e50;
}

.employee-id {
    font-size: 11px;
    color: #95a5a6;
}

.grade-badge {
    background: #ecf0f1;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    color: #2c3e50;
    white-space: nowrap;
}

/* Amount columns */
.amount {
    text-align: right;
    font-family: 'Courier New', monospace;
}

.text-success {
    color: #27ae60;
}

.text-danger {
    color: #e74c3c;
}

/* Action Buttons */
.btn-icon {
    background: none;
    border: none;
    padding: 5px;
    cursor: pointer;
    color: #95a5a6;
    border-radius: 3px;
    margin: 0 2px;
}

.btn-icon:hover {
    color: #3498db;
    background: #f0f7ff;
}

/* Table Footer */
.table-footer {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #ecf0f1;
}

.summary-info {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 13px;
    color: #7f8c8d;
    flex-wrap: wrap;
}

.summary-info .separator {
    color: #d0d7de;
}

.summary-info strong {
    color: #2c3e50;
}

/* Rates Grid */
.rates-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.rate-item {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.rate-label {
    display: block;
    font-size: 12px;
    color: #7f8c8d;
    margin-bottom: 5px;
}

.rate-value {
    display: block;
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
}

.rate-max {
    display: block;
    font-size: 11px;
    color: #95a5a6;
}

/* Alert */
.alert {
    padding: 12px 15px;
    border-radius: 4px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.alert-success {
    background: #e8f5e9;
    color: #27ae60;
    border: 1px solid #c8e6c9;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-dialog {
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-dialog.modal-lg {
    max-width: 700px;
}

.modal-content {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        transform: translateY(-30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h5 {
    margin: 0;
    font-size: 16px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-header h5 i {
    color: #3498db;
}

.btn-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #95a5a6;
    padding: 0 5px;
}

.btn-close:hover {
    color: #e74c3c;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* Form Styles */
.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-size: 12px;
    color: #2c3e50;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
    box-sizing: border-box;
}

.form-control:focus {
    border-color: #3498db;
    outline: none;
}

.form-control[readonly] {
    background: #f8f9fa;
    cursor: not-allowed;
}

.required {
    color: #e74c3c;
    margin-left: 3px;
}

.checkbox-group {
    margin-bottom: 10px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 13px;
}

.checkbox-label input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

/* Alert in Modal */
.alert-info {
    background: #e1f0fa;
    color: #3498db;
    border: 1px solid #bbdefb;
}

/* Breakdown Modal Styles */
.breakdown-container {
    padding: 10px;
}

.breakdown-header {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ecf0f1;
}

.breakdown-header h4 {
    margin: 0 0 5px;
    font-size: 16px;
    font-weight: 500;
}

.breakdown-section {
    margin-bottom: 20px;
}

.breakdown-section h6 {
    margin: 0 0 10px;
    font-size: 14px;
    font-weight: 500;
    color: #2c3e50;
}

.breakdown-table {
    width: 100%;
    border-collapse: collapse;
}

.breakdown-table td {
    padding: 8px 0;
    border-bottom: 1px solid #ecf0f1;
    font-size: 13px;
}

.breakdown-table .total-row {
    font-weight: 600;
    background: #f8f9fa;
}

.breakdown-table .total-row td {
    padding-top: 10px;
    padding-bottom: 10px;
}

.breakdown-table .amount {
    text-align: right;
}

.breakdown-total {
    margin-top: 20px;
    padding: 15px;
    background: #2c3e50;
    color: white;
    display: flex;
    justify-content: space-between;
    font-size: 16px;
    font-weight: 600;
    border-radius: 4px;
}

.net-amount {
    color: #27ae60;
}

/* Responsive */
@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 992px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .welcome-banner {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-stats {
        flex-direction: column;
        gap: 10px;
    }
    
    .quick-stat-item {
        border-right: none;
        border-bottom: 1px solid #ecf0f1;
        padding-bottom: 10px;
    }
    
    .quick-stat-item:last-child {
        border-bottom: none;
    }
    
    .month-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-box {
        width: 100%;
    }
    
    .rates-grid {
        grid-template-columns: 1fr;
    }
    
    .summary-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .summary-info .separator {
        display: none;
    }
}

@media (max-width: 480px) {
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
    
    .employee-info {
        flex-direction: column;
        text-align: center;
    }
}
</style>

