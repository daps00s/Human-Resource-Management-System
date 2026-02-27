<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Handle actions
$message = '';
$message_type = '';

// Get current date for calculations
$current_date = date('Y-m-d');

// Process step increment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'apply_increment') {
        $employee_id = $_POST['employee_id'];
        $new_step = $_POST['new_step'];
        $effective_date = $_POST['effective_date'];
        $remarks = $_POST['remarks'];
        $years_of_service = $_POST['years_of_service'];
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Get current salary details
            $stmt = $conn->prepare("
                SELECT ee.*, sg.monthly_salary as current_salary, sg.salary_grade, sg.id as salary_grade_id
                FROM employee_employment ee
                JOIN salary_grades sg ON ee.salary_grade_id = sg.id AND ee.step = sg.step
                WHERE ee.user_id = ?
            ");
            $stmt->bind_param("i", $employee_id);
            $stmt->execute();
            $current = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            // Get new salary details
            $stmt = $conn->prepare("
                SELECT id, monthly_salary 
                FROM salary_grades 
                WHERE salary_grade = ? AND step = ?
            ");
            $stmt->bind_param("si", $current['salary_grade'], $new_step);
            $stmt->execute();
            $new = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($new) {
                // Update employee employment
                $update = $conn->prepare("
                    UPDATE employee_employment 
                    SET step = ?, monthly_salary = ?, updated_at = NOW() 
                    WHERE user_id = ?
                ");
                $update->bind_param("idi", $new_step, $new['monthly_salary'], $employee_id);
                $update->execute();
                $update->close();
                
                // Record in salary history
                $history = $conn->prepare("
                    INSERT INTO salary_history (
                        user_id, old_salary_grade_id, new_salary_grade_id, 
                        old_step, new_step, old_monthly_salary, new_monthly_salary,
                        adjustment_type, adjustment_date, remarks, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'step_increment', ?, ?, NOW())
                ");
                $history->bind_param(
                    "iiiidddss",
                    $employee_id,
                    $current['salary_grade_id'],
                    $new['id'],
                    $current['step'],
                    $new_step,
                    $current['current_salary'],
                    $new['monthly_salary'],
                    $effective_date,
                    $remarks
                );
                $history->execute();
                $history->close();
                
                // Record in service history for tracking
                $service = $conn->prepare("
                    INSERT INTO service_history (
                        user_id, position, office_id, salary_grade_id, step,
                        start_date, is_current, remarks, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW())
                ");
                $service->bind_param(
                    "isiiss",
                    $employee_id,
                    $current['position'],
                    $current['office_id'],
                    $new['id'],
                    $new_step,
                    $effective_date,
                    $remarks
                );
                $service->execute();
                $service->close();
                
                // Update the last increment date in employee_employment
                $update_increment = $conn->prepare("
                    UPDATE employee_employment 
                    SET last_increment_date = ? 
                    WHERE user_id = ?
                ");
                $update_increment->bind_param("si", $effective_date, $employee_id);
                $update_increment->execute();
                $update_increment->close();
                
                $conn->commit();
                $message = "Step increment applied successfully! Employee is now at Step $new_step with salary ₱" . number_format($new['monthly_salary'], 2);
                $message_type = "success";
            } else {
                throw new Exception("Salary grade not found for step $new_step");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error applying increment: " . $e->getMessage();
            $message_type = "error";
        }
    }
    
    if ($_POST['action'] === 'bulk_increment') {
        $employee_ids = $_POST['employee_ids'];
        $effective_date = $_POST['effective_date'];
        $remarks = $_POST['remarks'];
        
        $success_count = 0;
        $error_count = 0;
        
        $conn->begin_transaction();
        
        try {
            foreach ($employee_ids as $employee_id) {
                // Get current employee details
                $stmt = $conn->prepare("
                    SELECT ee.*, sg.monthly_salary as current_salary, sg.salary_grade, sg.id as salary_grade_id
                    FROM employee_employment ee
                    JOIN salary_grades sg ON ee.salary_grade_id = sg.id AND ee.step = sg.step
                    WHERE ee.user_id = ? AND ee.step < 8
                ");
                $stmt->bind_param("i", $employee_id);
                $stmt->execute();
                $current = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                if ($current) {
                    $new_step = $current['step'] + 1;
                    
                    // Get new salary
                    $stmt = $conn->prepare("
                        SELECT id, monthly_salary 
                        FROM salary_grades 
                        WHERE salary_grade = ? AND step = ?
                    ");
                    $stmt->bind_param("si", $current['salary_grade'], $new_step);
                    $stmt->execute();
                    $new = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    if ($new) {
                        // Update employee
                        $update = $conn->prepare("
                            UPDATE employee_employment 
                            SET step = ?, monthly_salary = ?, last_increment_date = ?, updated_at = NOW() 
                            WHERE user_id = ?
                        ");
                        $update->bind_param("idsi", $new_step, $new['monthly_salary'], $effective_date, $employee_id);
                        $update->execute();
                        $update->close();
                        
                        // Record history
                        $history = $conn->prepare("
                            INSERT INTO salary_history (
                                user_id, old_salary_grade_id, new_salary_grade_id, 
                                old_step, new_step, old_monthly_salary, new_monthly_salary,
                                adjustment_type, adjustment_date, remarks, created_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'step_increment', ?, ?, NOW())
                        ");
                        $history->bind_param(
                            "iiiidddss",
                            $employee_id,
                            $current['salary_grade_id'],
                            $new['id'],
                            $current['step'],
                            $new_step,
                            $current['current_salary'],
                            $new['monthly_salary'],
                            $effective_date,
                            $remarks
                        );
                        $history->execute();
                        $history->close();
                        
                        $success_count++;
                    }
                }
            }
            
            $conn->commit();
            $message = "Bulk increment completed! $success_count employees incremented successfully.";
            $message_type = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error in bulk increment: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Get eligible employees for step increment (Government Rule: Every 3 years of service)
$eligible_sql = "
    SELECT 
        u.id,
        u.employee_id,
        u.first_name,
        u.last_name,
        u.username,
        ee.position,
        o.office_name,
        ee.date_hired,
        ee.step as current_step,
        sg.salary_grade,
        sg.monthly_salary as current_salary,
        TIMESTAMPDIFF(YEAR, ee.date_hired, CURDATE()) as years_of_service,
        TIMESTAMPDIFF(MONTH, ee.date_hired, CURDATE()) as months_of_service,
        ee.last_increment_date,
        CASE 
            WHEN ee.last_increment_date IS NULL THEN 
                TIMESTAMPDIFF(MONTH, ee.date_hired, CURDATE()) >= 36
            ELSE 
                TIMESTAMPDIFF(MONTH, ee.last_increment_date, CURDATE()) >= 36
        END as eligible_by_years,
        ee.step < 8 as can_increment,
        CASE 
            WHEN ee.last_increment_date IS NULL THEN 
                DATE_ADD(ee.date_hired, INTERVAL 3 YEAR)
            ELSE 
                DATE_ADD(ee.last_increment_date, INTERVAL 3 YEAR)
        END as next_increment_date
    FROM users u
    JOIN employee_employment ee ON u.id = ee.user_id
    JOIN salary_grades sg ON ee.salary_grade_id = sg.id AND ee.step = sg.step
    JOIN offices o ON ee.office_id = o.id
    WHERE u.role != 'admin'
    ORDER BY 
        CASE 
            WHEN ee.last_increment_date IS NULL THEN ee.date_hired
            ELSE ee.last_increment_date
        END ASC
";
$eligible_result = $conn->query($eligible_sql);

// Get statistics
$total_employees = $eligible_result->num_rows;
$eligible_count = 0;
$upcoming_count = 0;
$eligible_result->data_seek(0);
while ($row = $eligible_result->fetch_assoc()) {
    if ($row['eligible_by_years'] && $row['can_increment']) {
        $eligible_count++;
    }
    // Check if eligible within next 3 months
    $next_date = strtotime($row['next_increment_date']);
    $three_months = strtotime('+3 months');
    if ($next_date <= $three_months && !$row['eligible_by_years'] && $row['can_increment']) {
        $upcoming_count++;
    }
}
?>

<!-- Content Header -->
<div class="content-header">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e2b3a 0%, #2c3e50 100%);">
        <div class="welcome-content">
            <h1><i class="fas fa-arrow-circle-up"></i> Step Increment Management</h1>
            <p>Government Service: Step increments are granted every 3 years of satisfactory service (CSC MC No. 40, s. 1998)</p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn-primary" onclick="showBulkIncrementModal()">
                <i class="fas fa-users"></i> Bulk Increment
            </button>
            <button type="button" class="btn-primary" onclick="generateReport()">
                <i class="fas fa-file-pdf"></i> Generate Report
            </button>
            <button type="button" class="btn-primary" onclick="showIncrementSchedule()">
                <i class="fas fa-calendar"></i> View Schedule
            </button>
        </div>
    </div>
    

</div>

<!-- Alert Messages -->
<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>



<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon bg-info">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-details">
            <h3><?php echo $total_employees; ?></h3>
            <p>Total Employees</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon bg-success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-details">
            <h3><?php echo $eligible_count; ?></h3>
            <p>Currently Eligible</p>
            <small>Completed 3 years</small>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon bg-warning">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-details">
            <h3><?php echo $upcoming_count; ?></h3>
            <p>Eligible in 3 Months</p>
            <small>Next quarter</small>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon bg-primary">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-details">
            <h3>3 Years</h3>
            <p>Service Requirement</p>
            <small>Per CSC规则</small>
        </div>
    </div>
</div>

<!-- Eligible Employees Table -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-user-check"></i> Employees Eligible for Step Increment</h3>
        <div class="card-actions">
            <div class="filter-group">
                <select class="filter-select" id="eligibilityFilter">
                    <option value="all">All Employees</option>
                    <option value="eligible">Currently Eligible</option>
                    <option value="upcoming">Eligible in 3 Months</option>
                    <option value="not-eligible">Not Yet Eligible</option>
                </select>
                <select class="filter-select" id="officeFilter">
                    <option value="">All Offices</option>
                    <?php
                    $offices = $conn->query("SELECT id, office_name FROM offices WHERE status = 'active'");
                    while ($office = $offices->fetch_assoc()) {
                        echo "<option value='{$office['id']}'>{$office['office_name']}</option>";
                    }
                    ?>
                </select>
                <input type="text" class="search-box" id="searchEmployee" placeholder="Search employee...">
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table" id="eligibleTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
                        <th>Employee ID</th>
                        <th>Employee Name</th>
                        <th>Position</th>
                        <th>Office</th>
                        <th>Date Hired</th>
                        <th>Years of Service</th>
                        <th>Last Increment</th>
                        <th>Next Increment</th>
                        <th>Current Grade/Step</th>
                        <th>Current Salary</th>
                        <th>Next Step</th>
                        <th>Next Salary</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $eligible_result->data_seek(0);
                    while ($employee = $eligible_result->fetch_assoc()): 
                        // Get next step salary if available
                        $next_step = $employee['current_step'] + 1;
                        $next_salary = null;
                        $next_salary_amount = 0;
                        if ($next_step <= 8) {
                            $stmt = $conn->prepare("SELECT monthly_salary FROM salary_grades WHERE salary_grade = ? AND step = ?");
                            $stmt->bind_param("si", $employee['salary_grade'], $next_step);
                            $stmt->execute();
                            $next = $stmt->get_result()->fetch_assoc();
                            $next_salary = $next ? $next['monthly_salary'] : null;
                            $next_salary_amount = $next_salary ?: 0;
                        }
                        
                        // Determine status
                        $status = '';
                        $status_class = '';
                        if ($employee['eligible_by_years'] && $employee['can_increment']) {
                            $status = 'ELIGIBLE NOW';
                            $status_class = 'status-eligible';
                        } elseif (!$employee['can_increment']) {
                            $status = 'MAX STEP';
                            $status_class = 'status-max';
                        } else {
                            $months_remaining = 36 - (date_diff(date_create($employee['last_increment_date'] ?? $employee['date_hired']), date_create('now'))->m + 
                                                      (date_diff(date_create($employee['last_increment_date'] ?? $employee['date_hired']), date_create('now'))->y * 12));
                            if ($months_remaining <= 3 && $months_remaining > 0) {
                                $status = 'ELIGIBLE SOON';
                                $status_class = 'status-upcoming';
                            } else {
                                $status = 'NOT ELIGIBLE';
                                $status_class = 'status-not';
                            }
                        }
                        
                        $row_class = $employee['eligible_by_years'] && $employee['can_increment'] ? 'eligible-row' : '';
                    ?>
                    <tr class="<?php echo $row_class; ?>" data-status="<?php echo $status_class; ?>">
                        <td>
                            <?php if ($employee['eligible_by_years'] && $employee['can_increment']): ?>
                            <input type="checkbox" class="employee-checkbox" value="<?php echo $employee['id']; ?>">
                            <?php endif; ?>
                        </td>
                        <td><?php echo $employee['employee_id']; ?></td>
                        <td>
                            <div class="employee-info">
                                <div class="employee-avatar">
                                    <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                                    <small><?php echo $employee['username']; ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?php echo $employee['position']; ?></td>
                        <td><?php echo $employee['office_name']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($employee['date_hired'])); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $employee['years_of_service'] >= 3 ? 'success' : 'secondary'; ?>">
                                <?php echo $employee['years_of_service'] . ' yrs, ' . ($employee['months_of_service'] % 12) . ' mos'; ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $employee['last_increment_date'] ? date('M d, Y', strtotime($employee['last_increment_date'])) : 'Initial hire'; ?>
                        </td>
                        <td>
                            <?php if ($employee['can_increment']): ?>
                                <span class="next-date <?php echo strtotime($employee['next_increment_date']) <= time() ? 'text-success' : ''; ?>">
                                    <?php echo date('M d, Y', strtotime($employee['next_increment_date'])); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">Max step reached</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $employee['salary_grade'] . ' Step ' . $employee['current_step']; ?></td>
                        <td>₱<?php echo number_format($employee['current_salary'], 2); ?></td>
                        <td>
                            <?php if ($next_step <= 8): ?>
                                Step <?php echo $next_step; ?>
                            <?php else: ?>
                                <span class="badge badge-warning">Max Step</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($next_salary): ?>
                                ₱<?php echo number_format($next_salary, 2); ?>
                                <small class="text-success">(+₱<?php echo number_format($next_salary - $employee['current_salary'], 2); ?>)</small>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo $status; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($next_salary && $employee['eligible_by_years'] && $employee['can_increment']): ?>
                                <button class="btn-icon" onclick="showIncrementModal(
                                    <?php echo $employee['id']; ?>, 
                                    '<?php echo addslashes($employee['first_name'] . ' ' . $employee['last_name']); ?>', 
                                    <?php echo $next_step; ?>, 
                                    <?php echo $next_salary; ?>,
                                    <?php echo $employee['years_of_service']; ?>
                                )" title="Apply Increment">
                                    <i class="fas fa-arrow-up"></i>
                                </button>
                            <?php endif; ?>
                            <button class="btn-icon" onclick="viewHistory(<?php echo $employee['id']; ?>)" title="View History">
                                <i class="fas fa-history"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Increment Modal -->
<div class="modal" id="incrementModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Apply Step Increment</h5>
                <button type="button" class="btn-close" onclick="closeModal('incrementModal')"></button>
            </div>
            <form method="POST" id="incrementForm">
                <input type="hidden" name="action" value="apply_increment">
                <input type="hidden" name="employee_id" id="modal_employee_id">
                <input type="hidden" name="years_of_service" id="modal_years">
                
                <div class="modal-body">
                    <div class="increment-info">
                        <i class="fas fa-info-circle"></i>
                        <p>Step increment is being applied based on 3 years of satisfactory service.</p>
                    </div>
                    
                    <div class="form-group">
                        <label>Employee</label>
                        <input type="text" class="form-control" id="modal_employee_name" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>New Step</label>
                        <input type="text" class="form-control" name="new_step" id="modal_new_step" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>New Monthly Salary</label>
                        <input type="text" class="form-control" id="modal_new_salary" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="effective_date">Effectivity Date <span class="required">*</span></label>
                        <input type="date" class="form-control" name="effective_date" id="effective_date" required value="<?php echo date('Y-m-d'); ?>">
                        <small class="form-text text-muted">Should be the date after completing 3 years of service</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="remarks">Remarks / CSC Approval</label>
                        <textarea class="form-control" name="remarks" id="remarks" rows="3" placeholder="Enter CSC approval number or remarks..."></textarea>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="certify" required>
                        <label class="form-check-label" for="certify">
                            I certify that the employee has rendered at least three (3) years of satisfactory service and has no pending administrative case.
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('incrementModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Apply Increment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Increment Modal -->
<div class="modal" id="bulkIncrementModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Step Increment</h5>
                <button type="button" class="btn-close" onclick="closeModal('bulkIncrementModal')"></button>
            </div>
            <form method="POST" id="bulkIncrementForm">
                <input type="hidden" name="action" value="bulk_increment">
                <input type="hidden" name="employee_ids" id="selected_employees">
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        You are about to apply step increments to <span id="selectedCount">0</span> eligible employees.
                    </div>
                    
                    <div class="form-group">
                        <label for="bulk_effective_date">Effectivity Date <span class="required">*</span></label>
                        <input type="date" class="form-control" name="effective_date" id="bulk_effective_date" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="bulk_remarks">Remarks / CSC Authority</label>
                        <textarea class="form-control" name="remarks" id="bulk_remarks" rows="3" placeholder="Enter CSC authority or resolution number..."></textarea>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="bulk_certify" required>
                        <label class="form-check-label" for="bulk_certify">
                            I certify that all selected employees have rendered at least three (3) years of satisfactory service and have no pending administrative cases.
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('bulkIncrementModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Process Bulk Increment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let selectedEmployees = [];

// Modal functions
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

function showIncrementModal(id, name, step, salary, years) {
    document.getElementById('modal_employee_id').value = id;
    document.getElementById('modal_employee_name').value = name;
    document.getElementById('modal_new_step').value = 'Step ' + step;
    document.getElementById('modal_new_salary').value = '₱' + salary.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    document.getElementById('modal_years').value = years;
    document.getElementById('incrementModal').classList.add('show');
}

function showBulkIncrementModal() {
    // Get all checked employees
    selectedEmployees = [];
    document.querySelectorAll('.employee-checkbox:checked').forEach(cb => {
        selectedEmployees.push(cb.value);
    });
    
    if (selectedEmployees.length === 0) {
        alert('Please select at least one employee from the eligible list.');
        return;
    }
    
    document.getElementById('selectedCount').textContent = selectedEmployees.length;
    document.getElementById('selected_employees').value = JSON.stringify(selectedEmployees);
    document.getElementById('bulkIncrementModal').classList.add('show');
}

function toggleAll(source) {
    document.querySelectorAll('.employee-checkbox').forEach(cb => {
        cb.checked = source.checked;
    });
}

function viewHistory(employeeId) {
    window.location.href = 'employee_increment_history.php?id=' + employeeId;
}

function generateReport() {
    window.location.href = 'generate_step_increment_report.php';
}

function showIncrementSchedule() {
    window.location.href = 'increment_schedule.php';
}

// Filter functionality
document.getElementById('searchEmployee').addEventListener('keyup', filterTable);
document.getElementById('officeFilter').addEventListener('change', filterTable);
document.getElementById('eligibilityFilter').addEventListener('change', filterTable);

function filterTable() {
    const search = document.getElementById('searchEmployee').value.toLowerCase();
    const office = document.getElementById('officeFilter').value;
    const eligibility = document.getElementById('eligibilityFilter').value;
    const table = document.getElementById('eligibleTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const employeeName = row.cells[2]?.innerText.toLowerCase() || '';
        const employeeId = row.cells[1]?.innerText.toLowerCase() || '';
        const officeCell = row.cells[4]?.innerText || '';
        const status = row.getAttribute('data-status') || '';
        
        let show = true;
        
        // Search filter
        if (search && !employeeName.includes(search) && !employeeId.includes(search)) {
            show = false;
        }
        
        // Office filter
        if (office && officeCell !== office && !officeCell.includes(office)) {
            show = false;
        }
        
        // Eligibility filter
        if (eligibility !== 'all') {
            if (eligibility === 'eligible' && !status.includes('eligible')) {
                show = false;
            } else if (eligibility === 'upcoming' && !status.includes('upcoming')) {
                show = false;
            } else if (eligibility === 'not-eligible' && (status.includes('eligible') || status.includes('max'))) {
                show = false;
            }
        }
        
        row.style.display = show ? '' : 'none';
    }
}

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});
</script>

<style>
/* Government Guidelines Banner */
.guidelines-banner {
    background: #fff3cd;
    border-left: 5px solid #ffc107;
    padding: 20px;
    margin: 20px 0;
    display: flex;
    gap: 20px;
    border-radius: 5px;
}

.guidelines-icon {
    font-size: 40px;
    color: #856404;
}

.guidelines-content h4 {
    margin: 0 0 10px;
    color: #856404;
    font-weight: 600;
}

.guidelines-content ul {
    list-style: none;
    padding: 0;
    margin: 10px 0 0;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.guidelines-content ul li {
    font-size: 12px;
    color: #856404;
    display: flex;
    align-items: center;
    gap: 5px;
}

.guidelines-content ul li i {
    color: #28a745;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.stat-icon i {
    font-size: 24px;
    color: white;
}

.stat-details h3 {
    margin: 0;
    font-size: 24px;
    font-weight: 600;
}

.stat-details p {
    margin: 0;
    font-size: 13px;
    color: #666;
}

.stat-details small {
    font-size: 11px;
    color: #999;
}

/* Status Badges */
.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-eligible {
    background: #d4edda;
    color: #155724;
}

.status-upcoming {
    background: #fff3cd;
    color: #856404;
}

.status-not {
    background: #f8d7da;
    color: #721c24;
}

.status-max {
    background: #e2e3e5;
    color: #383d41;
}

/* Filter Group */
.filter-group {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-select, .search-box {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
    min-width: 150px;
}

/* Table Styles */
.table {
    width: 100%;
    border-collapse: collapse;
}

.table th {
    background: #f8f9fa;
    padding: 12px;
    font-size: 12px;
    font-weight: 600;
    color: #666;
    border-bottom: 2px solid #dee2e6;
    text-align: left;
}

.table td {
    padding: 12px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 13px;
}

.employee-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.employee-avatar {
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 600;
}

.eligible-row {
    background-color: #f0f9ff;
}

/* Badges */
.badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-secondary {
    background: #e2e3e5;
    color: #383d41;
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
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
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
    font-weight: 600;
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

.increment-info {
    background: #e7f3ff;
    border-left: 3px solid #2196F3;
    padding: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.increment-info i {
    color: #2196F3;
    font-size: 20px;
}

.increment-info p {
    margin: 0;
    font-size: 12px;
    color: #0c5460;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-size: 12px;
    font-weight: 500;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
}

.form-control:focus {
    border-color: #667eea;
    outline: none;
}

.form-control[readonly] {
    background: #f5f5f5;
}

.form-text {
    font-size: 11px;
    color: #666;
    margin-top: 3px;
}

.form-check {
    margin: 15px 0;
}

.form-check-input {
    margin-right: 8px;
}

.required {
    color: #e74c3c;
}

.btn-primary {
    background: #667eea;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
}

.btn-secondary {
    background: #95a5a6;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-icon {
    background: none;
    border: none;
    padding: 5px;
    cursor: pointer;
    color: #667eea;
    transition: color 0.3s;
}

.btn-icon:hover {
    color: #5a67d8;
}

.text-success {
    color: #155724;
}

.text-muted {
    color: #6c757d;
}

/* Responsive */
@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .guidelines-banner {
        flex-direction: column;
    }
    
    .filter-group {
        flex-direction: column;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .card-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .table {
        font-size: 12px;
    }
    
    .employee-avatar {
        width: 30px;
        height: 30px;
        font-size: 12px;
    }
}
</style>

