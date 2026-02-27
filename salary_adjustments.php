<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Handle actions
$message = '';
$message_type = '';

// Process adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_adjustment':
                $user_id = $_POST['user_id'];
                $adjustment_type = $_POST['adjustment_type'];
                
                // First, verify the employee exists and get their current salary grade
                $check_employee_sql = "SELECT u.id, u.first_name, u.last_name 
                                      FROM users u 
                                      WHERE u.id = ? AND u.role != 'admin'";
                $check_employee = $conn->prepare($check_employee_sql);
                $check_employee->bind_param("i", $user_id);
                $check_employee->execute();
                $employee_result = $check_employee->get_result();
                
                if ($employee_result->num_rows == 0) {
                    $message = "Employee not found!";
                    $message_type = "error";
                    break;
                }
                $check_employee->close();
                
                // Get current employee employment details with salary grade validation
                $current_sql = "SELECT ee.*, sg.id as salary_grade_id, sg.salary_grade, sg.step, sg.monthly_salary 
                               FROM employee_employment ee
                               LEFT JOIN salary_grades sg ON ee.salary_grade_id = sg.id
                               WHERE ee.user_id = ?";
                $current_stmt = $conn->prepare($current_sql);
                $current_stmt->bind_param("i", $user_id);
                $current_stmt->execute();
                $current = $current_stmt->get_result()->fetch_assoc();
                $current_stmt->close();
                
                if (!$current) {
                    $message = "Employee employment record not found!";
                    $message_type = "error";
                    break;
                }
                
                // Verify that the current salary_grade_id exists in salary_grades table
                if ($current['salary_grade_id']) {
                    $verify_sg_sql = "SELECT id FROM salary_grades WHERE id = ?";
                    $verify_sg = $conn->prepare($verify_sg_sql);
                    $verify_sg->bind_param("i", $current['salary_grade_id']);
                    $verify_sg->execute();
                    $sg_result = $verify_sg->get_result();
                    
                    if ($sg_result->num_rows == 0) {
                        $message = "Current salary grade (ID: {$current['salary_grade_id']}) not found in database. Please update employee record first.";
                        $message_type = "error";
                        $verify_sg->close();
                        break;
                    }
                    $verify_sg->close();
                } else {
                    $message = "Employee has no assigned salary grade. Please set up their salary grade first.";
                    $message_type = "error";
                    break;
                }
                
                $old_salary_grade_id = $current['salary_grade_id'];
                $old_step = $current['step'];
                $old_salary = $current['monthly_salary'];
                
                // Get new salary grade ID based on adjustment type
                $new_salary_grade_id = null;
                $new_step = null;
                $new_salary = null;
                
                if (in_array($adjustment_type, ['promotion', 'reclassification', 'demotion', 'transfer'])) {
                    $new_salary_grade_id = $_POST['new_salary_grade_id'];
                    $new_step = $_POST['new_step'];
                    
                    // Validate new salary grade exists
                    $check_sql = "SELECT id, monthly_salary FROM salary_grades WHERE id = ? AND step = ?";
                    $check_stmt = $conn->prepare($check_sql);
                    $check_stmt->bind_param("ii", $new_salary_grade_id, $new_step);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows == 0) {
                        $message = "Invalid new salary grade or step selected!";
                        $message_type = "error";
                        $check_stmt->close();
                        break;
                    }
                    
                    $new_grade = $check_result->fetch_assoc();
                    $new_salary = $new_grade['monthly_salary'];
                    $check_stmt->close();
                    
                } else {
                    // For salary adjustments without grade change
                    $new_salary = $_POST['new_salary'];
                    $new_salary_grade_id = $old_salary_grade_id;
                    $new_step = $old_step;
                }
                
                $adjustment_date = $_POST['adjustment_date'];
                $csc_approval = isset($_POST['csc_approval']) ? $_POST['csc_approval'] : null;
                $authority = isset($_POST['authority']) ? $_POST['authority'] : null;
                $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : null;
                
                // Begin transaction
                $conn->begin_transaction();
                
                try {
                    // Insert into salary_history with proper IDs
                    $stmt = $conn->prepare("
                        INSERT INTO salary_history (
                            user_id, old_salary_grade_id, new_salary_grade_id,
                            old_step, new_step, old_monthly_salary, new_monthly_salary,
                            adjustment_type, adjustment_date, csc_approval, authority, remarks, status, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                    ");
                    
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    
                    $stmt->bind_param(
                        "iiiidddsssss",
                        $user_id,
                        $old_salary_grade_id,
                        $new_salary_grade_id,
                        $old_step,
                        $new_step,
                        $old_salary,
                        $new_salary,
                        $adjustment_type,
                        $adjustment_date,
                        $csc_approval,
                        $authority,
                        $remarks
                    );
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Execute failed: " . $stmt->error);
                    }
                    
                    $adjustment_id = $stmt->insert_id;
                    $stmt->close();
                    
                    // If approved immediately, update employee employment
                    if (isset($_POST['approve_immediately']) && $_POST['approve_immediately'] == '1') {
                        // Check if new salary grade exists
                        $verify_new_sg = $conn->prepare("SELECT id FROM salary_grades WHERE id = ?");
                        $verify_new_sg->bind_param("i", $new_salary_grade_id);
                        $verify_new_sg->execute();
                        $new_sg_result = $verify_new_sg->get_result();
                        
                        if ($new_sg_result->num_rows == 0) {
                            throw new Exception("New salary grade ID $new_salary_grade_id not found in database");
                        }
                        $verify_new_sg->close();
                        
                        $update = $conn->prepare("
                            UPDATE employee_employment 
                            SET salary_grade_id = ?, step = ?, monthly_salary = ?, updated_at = NOW() 
                            WHERE user_id = ?
                        ");
                        
                        if (!$update) {
                            throw new Exception("Update prepare failed: " . $conn->error);
                        }
                        
                        $update->bind_param("iidi", $new_salary_grade_id, $new_step, $new_salary, $user_id);
                        
                        if (!$update->execute()) {
                            throw new Exception("Update failed: " . $update->error);
                        }
                        $update->close();
                        
                        // Update status to approved
                        $update_status = $conn->prepare("UPDATE salary_history SET status = 'approved', approved_at = NOW() WHERE id = ?");
                        $update_status->bind_param("i", $adjustment_id);
                        $update_status->execute();
                        $update_status->close();
                        
                        $message = "Salary adjustment approved and applied immediately!";
                    } else {
                        $message = "Salary adjustment submitted for approval!";
                    }
                    
                    $conn->commit();
                    $message_type = "success";
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = "Error adding adjustment: " . $e->getMessage();
                    $message_type = "error";
                }
                break;
                
            case 'approve_adjustment':
                $adjustment_id = $_POST['adjustment_id'];
                
                // Begin transaction
                $conn->begin_transaction();
                
                try {
                    // Get adjustment details
                    $get_sql = "SELECT * FROM salary_history WHERE id = ?";
                    $get_stmt = $conn->prepare($get_sql);
                    $get_stmt->bind_param("i", $adjustment_id);
                    $get_stmt->execute();
                    $adjustment = $get_stmt->get_result()->fetch_assoc();
                    $get_stmt->close();
                    
                    if (!$adjustment) {
                        throw new Exception("Adjustment not found");
                    }
                    
                    // Verify new salary grade exists
                    $check_sql = "SELECT id FROM salary_grades WHERE id = ?";
                    $check_stmt = $conn->prepare($check_sql);
                    $check_stmt->bind_param("i", $adjustment['new_salary_grade_id']);
                    $check_stmt->execute();
                    if ($check_stmt->get_result()->num_rows == 0) {
                        throw new Exception("New salary grade not found in database");
                    }
                    $check_stmt->close();
                    
                    // Update employee employment
                    $update = $conn->prepare("
                        UPDATE employee_employment 
                        SET salary_grade_id = ?, step = ?, monthly_salary = ?, updated_at = NOW() 
                        WHERE user_id = ?
                    ");
                    $update->bind_param("iidi", 
                        $adjustment['new_salary_grade_id'], 
                        $adjustment['new_step'], 
                        $adjustment['new_monthly_salary'], 
                        $adjustment['user_id']
                    );
                    
                    if (!$update->execute()) {
                        throw new Exception("Failed to update employee: " . $update->error);
                    }
                    $update->close();
                    
                    // Update status
                    $status_sql = "UPDATE salary_history SET status = 'approved', approved_at = NOW() WHERE id = ?";
                    $status_stmt = $conn->prepare($status_sql);
                    $status_stmt->bind_param("i", $adjustment_id);
                    $status_stmt->execute();
                    $status_stmt->close();
                    
                    $conn->commit();
                    $message = "Adjustment approved successfully!";
                    $message_type = "success";
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = "Error approving adjustment: " . $e->getMessage();
                    $message_type = "error";
                }
                break;
                
            case 'reject_adjustment':
                $adjustment_id = $_POST['adjustment_id'];
                $rejection_reason = $_POST['rejection_reason'];
                
                $stmt = $conn->prepare("
                    UPDATE salary_history 
                    SET status = 'rejected', rejection_reason = ?, rejected_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->bind_param("si", $rejection_reason, $adjustment_id);
                
                if ($stmt->execute()) {
                    $message = "Adjustment rejected successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error rejecting adjustment: " . $conn->error;
                    $message_type = "error";
                }
                $stmt->close();
                break;
        }
    }
}

// Get pending adjustments with proper joins
$pending_sql = "
    SELECT 
        sh.*, 
        u.first_name, u.last_name, u.employee_id,
        old_sg.salary_grade as old_grade_name,
        new_sg.salary_grade as new_grade_name,
        o.office_name,
        ee.position,
        old_sg.monthly_salary as old_sg_salary,
        new_sg.monthly_salary as new_sg_salary
    FROM salary_history sh
    JOIN users u ON sh.user_id = u.id
    LEFT JOIN salary_grades old_sg ON sh.old_salary_grade_id = old_sg.id
    LEFT JOIN salary_grades new_sg ON sh.new_salary_grade_id = new_sg.id
    LEFT JOIN employee_employment ee ON u.id = ee.user_id
    LEFT JOIN offices o ON ee.office_id = o.id
    WHERE sh.adjustment_type != 'step_increment'
    ORDER BY sh.created_at DESC
";
$pending_result = $conn->query($pending_sql);

// Get statistics
$stats_sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN adjustment_type = 'promotion' THEN 1 ELSE 0 END) as promotions,
        SUM(CASE WHEN adjustment_type = 'reclassification' THEN 1 ELSE 0 END) as reclassifications
    FROM salary_history 
    WHERE adjustment_type != 'step_increment'
";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Get list of employees with valid salary grades for the dropdown
$employees_sql = "
    SELECT 
        u.id, u.first_name, u.last_name, u.employee_id,
        ee.position, o.office_name,
        ee.salary_grade_id, ee.step, ee.monthly_salary,
        sg.salary_grade
    FROM users u
    JOIN employee_employment ee ON u.id = ee.user_id
    JOIN salary_grades sg ON ee.salary_grade_id = sg.id
    LEFT JOIN offices o ON ee.office_id = o.id
    WHERE u.role != 'admin'
    ORDER BY u.last_name
";
$employees_result = $conn->query($employees_sql);
?>

<!-- Dashboard Container -->
<div class="dashboard-container">

<!-- Content Header -->
<div class="content-header">
    <div class="welcome-banner">
        <div class="welcome-content">
            <h1><i class="fas fa-adjust"></i> Salary Adjustments</h1>
            <p>Manage promotions, reclassifications, transfers, and other salary adjustments based on CSC and DBM rules</p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> New Adjustment
            </button>
            <button type="button" class="btn-primary" onclick="exportAdjustments()">
                <i class="fas fa-file-excel"></i> Export
            </button>
            <button type="button" class="btn-primary" onclick="generateReport()">
                <i class="fas fa-file-pdf"></i> Report
            </button>
        </div>
    </div>
    
    <div class="breadcrumb-wrapper">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="salary_grade.php">Salary & Compensation</a></li>
                <li class="breadcrumb-item active" aria-current="page">Salary Adjustments</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Alert Messages -->
<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $message; ?>
        <button type="button" class="btn-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: #e3f2fd; color: #3498db;">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['pending'] ?? 0; ?></div>
            <div class="stat-label">Pending</div>
            <div class="stat-trend">Awaiting approval</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #e8f5e9; color: #27ae60;">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['approved'] ?? 0; ?></div>
            <div class="stat-label">Approved</div>
            <div class="stat-trend">This month</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #fff3e0; color: #e67e22;">
            <i class="fas fa-arrow-up"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['promotions'] ?? 0; ?></div>
            <div class="stat-label">Promotions</div>
            <div class="stat-trend">Year to date</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #fce4ec; color: #e74c3c;">
            <i class="fas fa-sync-alt"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $stats['reclassifications'] ?? 0; ?></div>
            <div class="stat-label">Reclassifications</div>
            <div class="stat-trend">Year to date</div>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="tab-container">
    <div class="tab-header">
        <button class="tab-btn active" onclick="showTab('pending')">
            <i class="fas fa-clock"></i> Pending 
            <?php if (($stats['pending'] ?? 0) > 0): ?>
                <span class="tab-badge"><?php echo $stats['pending']; ?></span>
            <?php endif; ?>
        </button>
        <button class="tab-btn" onclick="showTab('approved')">
            <i class="fas fa-check-circle"></i> Approved
        </button>
        <button class="tab-btn" onclick="showTab('rejected')">
            <i class="fas fa-times-circle"></i> Rejected
        </button>
        <button class="tab-btn" onclick="showTab('all')">
            <i class="fas fa-list"></i> All History
        </button>
    </div>
    
    <div class="tab-content">
        <!-- Pending Tab -->
        <div class="tab-pane active" id="pending-tab">
            <div class="adjustments-grid">
                <?php 
                $pending_result->data_seek(0);
                $has_pending = false;
                while ($adjustment = $pending_result->fetch_assoc()): 
                    if ($adjustment['status'] == 'pending'):
                        $has_pending = true;
                        $diff = $adjustment['new_monthly_salary'] - $adjustment['old_monthly_salary'];
                ?>
                <div class="adjustment-card">
                    <div class="card-status pending"></div>
                    <div class="card-header">
                        <div class="card-title">
                            <span class="type-badge type-<?php echo $adjustment['adjustment_type']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $adjustment['adjustment_type'])); ?>
                            </span>
                            <span class="adjustment-id">#<?php echo $adjustment['id']; ?></span>
                        </div>
                        <span class="adjustment-date"><?php echo date('M d, Y', strtotime($adjustment['adjustment_date'])); ?></span>
                    </div>
                    
                    <div class="card-body">
                        <div class="employee-section">
                            <div class="employee-avatar">
                                <?php echo strtoupper(substr($adjustment['first_name'], 0, 1) . substr($adjustment['last_name'], 0, 1)); ?>
                            </div>
                            <div class="employee-info">
                                <h4><?php echo $adjustment['first_name'] . ' ' . $adjustment['last_name']; ?></h4>
                                <p><?php echo $adjustment['employee_id']; ?> • <?php echo $adjustment['position'] ?? 'No position'; ?></p>
                                <small><?php echo $adjustment['office_name'] ?? 'No office'; ?></small>
                            </div>
                        </div>
                        
                        <div class="salary-change-section">
                            <div class="old-grade">
                                <span class="grade-label">From</span>
                                <span class="grade-value"><?php echo $adjustment['old_grade_name'] ?? 'N/A'; ?> Step <?php echo $adjustment['old_step']; ?></span>
                                <span class="salary-value">₱<?php echo number_format($adjustment['old_monthly_salary'], 2); ?></span>
                            </div>
                            <div class="change-arrow">
                                <i class="fas fa-arrow-right"></i>
                                <span class="diff-amount <?php echo $diff > 0 ? 'increase' : ($diff < 0 ? 'decrease' : ''); ?>">
                                    <?php echo $diff > 0 ? '+' : ''; ?>₱<?php echo number_format(abs($diff), 2); ?>
                                </span>
                            </div>
                            <div class="new-grade">
                                <span class="grade-label">To</span>
                                <span class="grade-value"><?php echo $adjustment['new_grade_name'] ?? 'N/A'; ?> Step <?php echo $adjustment['new_step']; ?></span>
                                <span class="salary-value">₱<?php echo number_format($adjustment['new_monthly_salary'], 2); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($adjustment['csc_approval']): ?>
                        <div class="approval-info">
                            <i class="fas fa-file-contract"></i>
                            <span>CSC Approval: <?php echo $adjustment['csc_approval']; ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($adjustment['remarks']): ?>
                        <div class="remarks">
                            <i class="fas fa-comment"></i>
                            <p><?php echo $adjustment['remarks']; ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer">
                        <button class="btn-approve" onclick="approveAdjustment(<?php echo $adjustment['id']; ?>)">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn-reject" onclick="showRejectModal(<?php echo $adjustment['id']; ?>)">
                            <i class="fas fa-times"></i> Reject
                        </button>
                        <button class="btn-view" onclick="viewDetails(<?php echo $adjustment['id']; ?>)" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <?php 
                    endif;
                endwhile; 
                
                if (!$has_pending):
                ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Pending Adjustments</h3>
                    <p>There are no salary adjustments waiting for approval.</p>
                    <button class="btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Create New Adjustment
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Approved Tab -->
        <div class="tab-pane" id="approved-tab">
            <div class="adjustments-grid">
                <?php 
                $pending_result->data_seek(0);
                $has_approved = false;
                while ($adjustment = $pending_result->fetch_assoc()): 
                    if ($adjustment['status'] == 'approved'):
                        $has_approved = true;
                ?>
                <div class="adjustment-card approved">
                    <div class="card-status approved"></div>
                    <div class="card-header">
                        <div class="card-title">
                            <span class="type-badge type-<?php echo $adjustment['adjustment_type']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $adjustment['adjustment_type'])); ?>
                            </span>
                            <span class="adjustment-id">#<?php echo $adjustment['id']; ?></span>
                        </div>
                        <span class="adjustment-date"><?php echo date('M d, Y', strtotime($adjustment['adjustment_date'])); ?></span>
                    </div>
                    
                    <div class="card-body">
                        <div class="employee-section">
                            <div class="employee-avatar">
                                <?php echo strtoupper(substr($adjustment['first_name'], 0, 1) . substr($adjustment['last_name'], 0, 1)); ?>
                            </div>
                            <div class="employee-info">
                                <h4><?php echo $adjustment['first_name'] . ' ' . $adjustment['last_name']; ?></h4>
                                <p><?php echo $adjustment['employee_id']; ?></p>
                            </div>
                        </div>
                        
                        <div class="salary-change-section">
                            <div class="old-grade">
                                <span class="grade-label">From</span>
                                <span class="grade-value"><?php echo $adjustment['old_grade_name'] ?? 'N/A'; ?> Step <?php echo $adjustment['old_step']; ?></span>
                            </div>
                            <div class="change-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                            <div class="new-grade">
                                <span class="grade-label">To</span>
                                <span class="grade-value"><?php echo $adjustment['new_grade_name'] ?? 'N/A'; ?> Step <?php echo $adjustment['new_step']; ?></span>
                            </div>
                        </div>
                        
                        <div class="approved-stamp">
                            <i class="fas fa-check-circle"></i> Approved
                            <?php if ($adjustment['approved_at']): ?>
                            <span><?php echo date('M d, Y', strtotime($adjustment['approved_at'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php 
                    endif;
                endwhile; 
                
                if (!$has_approved):
                ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>No Approved Adjustments</h3>
                    <p>Approved adjustments will appear here.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Rejected Tab -->
        <div class="tab-pane" id="rejected-tab">
            <div class="adjustments-grid">
                <?php 
                $pending_result->data_seek(0);
                $has_rejected = false;
                while ($adjustment = $pending_result->fetch_assoc()): 
                    if ($adjustment['status'] == 'rejected'):
                        $has_rejected = true;
                ?>
                <div class="adjustment-card rejected">
                    <div class="card-status rejected"></div>
                    <div class="card-header">
                        <div class="card-title">
                            <span class="type-badge type-<?php echo $adjustment['adjustment_type']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $adjustment['adjustment_type'])); ?>
                            </span>
                            <span class="adjustment-id">#<?php echo $adjustment['id']; ?></span>
                        </div>
                        <span class="adjustment-date"><?php echo date('M d, Y', strtotime($adjustment['adjustment_date'])); ?></span>
                    </div>
                    
                    <div class="card-body">
                        <div class="employee-section">
                            <div class="employee-avatar">
                                <?php echo strtoupper(substr($adjustment['first_name'], 0, 1) . substr($adjustment['last_name'], 0, 1)); ?>
                            </div>
                            <div class="employee-info">
                                <h4><?php echo $adjustment['first_name'] . ' ' . $adjustment['last_name']; ?></h4>
                                <p><?php echo $adjustment['employee_id']; ?></p>
                            </div>
                        </div>
                        
                        <?php if ($adjustment['rejection_reason']): ?>
                        <div class="rejection-reason">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p><strong>Reason:</strong> <?php echo $adjustment['rejection_reason']; ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php 
                    endif;
                endwhile; 
                
                if (!$has_rejected):
                ?>
                <div class="empty-state">
                    <i class="fas fa-times-circle"></i>
                    <h3>No Rejected Adjustments</h3>
                    <p>Rejected adjustments will appear here.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- All History Tab -->
        <div class="tab-pane" id="all-tab">
            <div class="history-table-container">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Difference</th>
                            <th>CSC Approval</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $pending_result->data_seek(0);
                        while ($adjustment = $pending_result->fetch_assoc()): 
                            $diff = $adjustment['new_monthly_salary'] - $adjustment['old_monthly_salary'];
                        ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($adjustment['adjustment_date'])); ?></td>
                            <td>
                                <div class="employee-name">
                                    <?php echo $adjustment['first_name'] . ' ' . $adjustment['last_name']; ?>
                                    <small><?php echo $adjustment['employee_id']; ?></small>
                                </div>
                            </td>
                            <td>
                                <span class="type-badge type-<?php echo $adjustment['adjustment_type']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $adjustment['adjustment_type'])); ?>
                                </span>
                            </td>
                            <td><?php echo $adjustment['old_grade_name'] ?? 'N/A'; ?> Step <?php echo $adjustment['old_step']; ?></td>
                            <td><?php echo $adjustment['new_grade_name'] ?? 'N/A'; ?> Step <?php echo $adjustment['new_step']; ?></td>
                            <td class="<?php echo $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : ''); ?>">
                                <?php echo $diff > 0 ? '+' : ''; ?>₱<?php echo number_format(abs($diff), 2); ?>
                            </td>
                            <td><?php echo $adjustment['csc_approval'] ?? '-'; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $adjustment['status']; ?>">
                                    <?php echo ucfirst($adjustment['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-icon" onclick="viewDetails(<?php echo $adjustment['id']; ?>)" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Adjustment Modal -->
<div class="modal" id="addAdjustmentModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> New Salary Adjustment</h5>
                <button type="button" class="btn-close" onclick="closeModal('addAdjustmentModal')">&times;</button>
            </div>
            <form method="POST" id="addAdjustmentForm">
                <input type="hidden" name="action" value="add_adjustment">
                
                <div class="modal-body">
                    <div class="form-section">
                        <h6><i class="fas fa-user"></i> Employee Information</h6>
                        <div class="form-group">
                            <label for="user_id">Select Employee <span class="required">*</span></label>
                            <select class="form-control" id="user_id" name="user_id" required onchange="getEmployeeDetails(this.value)">
                                <option value="">-- Select Employee --</option>
                                <?php 
                                $employees_result->data_seek(0);
                                while ($emp = $employees_result->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $emp['id']; ?>" 
                                        data-salary-grade-id="<?php echo $emp['salary_grade_id']; ?>"
                                        data-salary-grade="<?php echo $emp['salary_grade']; ?>"
                                        data-step="<?php echo $emp['step']; ?>"
                                        data-salary="<?php echo $emp['monthly_salary']; ?>"
                                        data-position="<?php echo $emp['position']; ?>"
                                        data-office="<?php echo $emp['office_name']; ?>">
                                    <?php echo $emp['last_name'] . ', ' . $emp['first_name'] . ' (' . $emp['employee_id'] . ') - ' . ($emp['position'] ?? 'No Position'); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div id="currentEmploymentInfo" style="display: none;" class="info-panel">
                            <div class="info-row">
                                <span class="info-label">Current Position:</span>
                                <span class="info-value" id="currentPosition"></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Office/Department:</span>
                                <span class="info-value" id="currentOffice"></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Salary Grade:</span>
                                <span class="info-value" id="currentSalaryGrade"></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Step:</span>
                                <span class="info-value" id="currentStep"></span>
                            </div>
                            <div class="info-row highlight">
                                <span class="info-label">Current Monthly Salary:</span>
                                <span class="info-value" id="currentSalaryDisplay"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h6><i class="fas fa-exchange-alt"></i> Adjustment Details</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="adjustment_type">Adjustment Type <span class="required">*</span></label>
                                    <select class="form-control" id="adjustment_type" name="adjustment_type" required onchange="toggleAdjustmentFields()">
                                        <option value="">-- Select Type --</option>
                                        <option value="promotion">Promotion</option>
                                        <option value="reclassification">Reclassification</option>
                                        <option value="transfer">Transfer (Same Grade)</option>
                                        <option value="demotion">Demotion</option>
                                        <option value="salary_adjustment">Salary Adjustment (SSL)</option>
                                        <option value="merit_increase">Merit Increase</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="adjustment_date">Effectivity Date <span class="required">*</span></label>
                                    <input type="date" class="form-control" id="adjustment_date" name="adjustment_date" required value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div id="newGradeFields" style="display: none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="new_salary_grade_id">New Salary Grade <span class="required">*</span></label>
                                        <select class="form-control" id="new_salary_grade_id" name="new_salary_grade_id" onchange="getNewGradeSteps()">
                                            <option value="">-- Select New Grade --</option>
                                            <?php
                                            $grades = $conn->query("SELECT DISTINCT id, salary_grade FROM salary_grades ORDER BY salary_grade");
                                            while ($grade = $grades->fetch_assoc()):
                                            ?>
                                            <option value="<?php echo $grade['id']; ?>"><?php echo $grade['salary_grade']; ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="new_step">New Step <span class="required">*</span></label>
                                        <select class="form-control" id="new_step" name="new_step" onchange="updateNewSalary()">
                                            <option value="">-- Select Step --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div id="newSalaryField" style="display: none;">
                            <div class="form-group">
                                <label for="new_salary">New Monthly Salary (₱) <span class="required">*</span></label>
                                <input type="number" class="form-control" id="new_salary" name="new_salary" step="0.01" oninput="calculateSalaryDifference()">
                            </div>
                        </div>
                        
                        <div id="salaryComparison" style="display: none;" class="comparison-panel">
                            <div class="comparison-row">
                                <span>Current Salary:</span>
                                <span class="old-amount" id="compareOldSalary">₱0.00</span>
                            </div>
                            <div class="comparison-row">
                                <span>New Salary:</span>
                                <span class="new-amount" id="compareNewSalary">₱0.00</span>
                            </div>
                            <div class="comparison-row highlight">
                                <span>Difference:</span>
                                <span class="diff-amount" id="compareDiff">₱0.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h6><i class="fas fa-file-contract"></i> Legal Basis & Approvals</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="csc_approval">CSC Approval Number</label>
                                    <input type="text" class="form-control" id="csc_approval" name="csc_approval" placeholder="e.g., CSC-2026-0001">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="authority">Authority/Resolution</label>
                                    <input type="text" class="form-control" id="authority" name="authority" placeholder="e.g., DBM Circular No. 2026-01">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="remarks">Remarks / Justification</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="4" placeholder="Enter justification for this adjustment..."></textarea>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="approve_immediately" name="approve_immediately" value="1">
                            <label class="form-check-label" for="approve_immediately">
                                Approve immediately (skip approval workflow)
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('addAdjustmentModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Submit Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal" id="rejectModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: #e74c3c; color: white;">
                <h5 class="modal-title"><i class="fas fa-times-circle"></i> Reject Adjustment</h5>
                <button type="button" class="btn-close" onclick="closeModal('rejectModal')" style="color: white;">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reject_adjustment">
                <input type="hidden" name="adjustment_id" id="reject_adjustment_id">
                
                <div class="modal-body">
                    <p>Please provide a reason for rejecting this adjustment:</p>
                    <div class="form-group">
                        <textarea class="form-control" name="rejection_reason" rows="4" required placeholder="Enter rejection reason..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('rejectModal')">Cancel</button>
                    <button type="submit" class="btn-primary" style="background: #e74c3c;">Reject Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentEmployeeData = {};

function openAddModal() {
    document.getElementById('addAdjustmentModal').classList.add('show');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

function getEmployeeDetails(userId) {
    if (!userId) {
        document.getElementById('currentEmploymentInfo').style.display = 'none';
        return;
    }
    
    const select = document.getElementById('user_id');
    const selected = select.options[select.selectedIndex];
    
    currentEmployeeData = {
        id: userId,
        salary_grade_id: selected.getAttribute('data-salary-grade-id'),
        salary_grade: selected.getAttribute('data-salary-grade'),
        step: selected.getAttribute('data-step'),
        salary: selected.getAttribute('data-salary'),
        position: selected.getAttribute('data-position'),
        office: selected.getAttribute('data-office')
    };
    
    document.getElementById('currentPosition').textContent = currentEmployeeData.position || 'Not set';
    document.getElementById('currentOffice').textContent = currentEmployeeData.office || 'Not set';
    document.getElementById('currentSalaryGrade').textContent = currentEmployeeData.salary_grade || 'Not set';
    document.getElementById('currentStep').textContent = currentEmployeeData.step ? 'Step ' + currentEmployeeData.step : 'Not set';
    document.getElementById('currentSalaryDisplay').textContent = currentEmployeeData.salary ? 
        '₱' + parseFloat(currentEmployeeData.salary).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') : 'Not set';
    
    document.getElementById('currentEmploymentInfo').style.display = 'block';
}

function toggleAdjustmentFields() {
    const type = document.getElementById('adjustment_type').value;
    const newGradeFields = document.getElementById('newGradeFields');
    const newSalaryField = document.getElementById('newSalaryField');
    
    // Reset fields
    newGradeFields.style.display = 'none';
    newSalaryField.style.display = 'none';
    document.getElementById('salaryComparison').style.display = 'none';
    
    if (type === 'promotion' || type === 'reclassification' || type === 'demotion' || type === 'transfer') {
        newGradeFields.style.display = 'block';
    } else if (type === 'salary_adjustment' || type === 'merit_increase' || type === 'other') {
        newSalaryField.style.display = 'block';
    }
}

function getNewGradeSteps() {
    const gradeId = document.getElementById('new_salary_grade_id').value;
    const stepSelect = document.getElementById('new_step');
    
    if (!gradeId) return;
    
    // Clear current options
    stepSelect.innerHTML = '<option value="">-- Select Step --</option>';
    
    // Fetch steps for this grade using AJAX
    fetch('get_grade_steps.php?grade_id=' + gradeId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                data.steps.forEach(step => {
                    const option = document.createElement('option');
                    option.value = step.step;
                    option.textContent = 'Step ' + step.step + ' - ₱' + parseFloat(step.monthly_salary).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    option.setAttribute('data-salary', step.monthly_salary);
                    stepSelect.appendChild(option);
                });
            } else {
                alert('Error loading steps: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading steps');
        });
}

function updateNewSalary() {
    const stepSelect = document.getElementById('new_step');
    const selected = stepSelect.options[stepSelect.selectedIndex];
    const salary = selected.getAttribute('data-salary');
    
    if (salary) {
        document.getElementById('new_salary').value = salary;
        calculateSalaryDifference();
    }
}

function calculateSalaryDifference() {
    const oldSalary = parseFloat(currentEmployeeData.salary) || 0;
    const newSalary = parseFloat(document.getElementById('new_salary').value) || 0;
    const diff = newSalary - oldSalary;
    
    if (newSalary > 0) {
        document.getElementById('salaryComparison').style.display = 'block';
        document.getElementById('compareOldSalary').textContent = '₱' + oldSalary.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        document.getElementById('compareNewSalary').textContent = '₱' + newSalary.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        
        const diffElement = document.getElementById('compareDiff');
        diffElement.textContent = (diff > 0 ? '+' : '') + '₱' + Math.abs(diff).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        diffElement.className = 'diff-amount ' + (diff > 0 ? 'increase' : (diff < 0 ? 'decrease' : ''));
    }
}

function approveAdjustment(id) {
    if (confirm('Are you sure you want to approve this adjustment? This will update the employee\'s salary immediately.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="approve_adjustment">
            <input type="hidden" name="adjustment_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function showRejectModal(id) {
    document.getElementById('reject_adjustment_id').value = id;
    document.getElementById('rejectModal').classList.add('show');
}

function viewDetails(id) {
    window.location.href = 'adjustment_details.php?id=' + id;
}

function exportAdjustments() {
    window.location.href = 'export_adjustments.php';
}

function generateReport() {
    window.location.href = 'generate_adjustment_report.php';
}

function showTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Show selected tab
    document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.classList.remove('active');
    });
    document.getElementById(tabName + '-tab').classList.add('active');
}

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});
</script>

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
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
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
    background: none;
    padding: 0;
    margin: 0;
    display: flex;
    list-style: none;
}

.breadcrumb-item a {
    color: #3498db;
    text-decoration: none;
    font-size: 13px;
}

.breadcrumb-item.active {
    color: #666;
    font-size: 13px;
}

.breadcrumb-item + .breadcrumb-item:before {
    content: "/";
    padding: 0 8px;
    color: #ccc;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin: 20px 0;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    transition: transform 0.3s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
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

/* Tab Container */
.tab-container {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    overflow: hidden;
    margin: 20px 0;
}

.tab-header {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 0 20px;
}

.tab-btn {
    background: none;
    border: none;
    padding: 15px 20px;
    font-size: 14px;
    font-weight: 500;
    color: #666;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    border-bottom: 2px solid transparent;
    transition: all 0.3s;
}

.tab-btn:hover {
    color: #3498db;
}

.tab-btn.active {
    color: #3498db;
    border-bottom-color: #3498db;
}

.tab-badge {
    background: #e74c3c;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 10px;
    margin-left: 5px;
}

.tab-pane {
    display: none;
    padding: 20px;
}

.tab-pane.active {
    display: block;
}

/* Adjustments Grid */
.adjustments-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 20px;
}

.adjustment-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
    position: relative;
    border: 1px solid #e0e0e0;
    transition: all 0.3s;
}

.adjustment-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.card-status {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
}

.card-status.pending {
    background: #f39c12;
}

.card-status.approved {
    background: #27ae60;
}

.card-status.rejected {
    background: #e74c3c;
}

.card-header {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.type-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.type-promotion { background: #d4edda; color: #155724; }
.type-reclassification { background: #cce5ff; color: #004085; }
.type-transfer { background: #d1ecf1; color: #0c5460; }
.type-demotion { background: #f8d7da; color: #721c24; }
.type-salary_adjustment { background: #fff3cd; color: #856404; }
.type-merit_increase { background: #d6d8d9; color: #1e2b3a; }
.type-other { background: #e2e3e5; color: #383d41; }

.adjustment-id {
    font-size: 11px;
    color: #999;
}

.adjustment-date {
    font-size: 11px;
    color: #666;
}

.card-body {
    padding: 15px;
}

.employee-section {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.employee-avatar {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 600;
}

.employee-info h4 {
    margin: 0 0 3px;
    font-size: 15px;
    font-weight: 600;
}

.employee-info p {
    margin: 0 0 3px;
    font-size: 12px;
    color: #666;
}

.employee-info small {
    font-size: 11px;
    color: #999;
}

.salary-change-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.old-grade, .new-grade {
    text-align: center;
    flex: 1;
}

.grade-label {
    display: block;
    font-size: 10px;
    color: #999;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.grade-value {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #333;
    margin-bottom: 3px;
}

.salary-value {
    display: block;
    font-size: 12px;
    color: #27ae60;
    font-weight: 500;
}

.change-arrow {
    position: relative;
    padding: 0 15px;
}

.change-arrow i {
    color: #3498db;
    font-size: 16px;
}

.diff-amount {
    position: absolute;
    top: -20px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 10px;
    white-space: nowrap;
    padding: 2px 6px;
    border-radius: 10px;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.diff-amount.increase {
    color: #27ae60;
}

.diff-amount.decrease {
    color: #e74c3c;
}

.approval-info {
    background: #e7f3ff;
    border-radius: 4px;
    padding: 8px;
    margin: 10px 0;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    color: #004085;
}

.remarks {
    background: #f8f9fa;
    border-radius: 4px;
    padding: 8px;
    margin: 10px 0;
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 11px;
    color: #666;
}

.remarks i {
    color: #3498db;
    font-size: 12px;
    margin-top: 2px;
}

.remarks p {
    margin: 0;
    flex: 1;
}

.approved-stamp {
    text-align: center;
    padding: 10px;
    background: #d4edda;
    color: #155724;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.rejection-reason {
    background: #f8d7da;
    border-radius: 4px;
    padding: 10px;
    margin: 10px 0;
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 12px;
    color: #721c24;
}

.card-footer {
    padding: 15px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    gap: 10px;
}

.btn-approve, .btn-reject, .btn-view {
    flex: 1;
    padding: 8px;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    transition: all 0.3s;
}

.btn-approve {
    background: #d4edda;
    color: #155724;
}

.btn-approve:hover {
    background: #c3e6cb;
}

.btn-reject {
    background: #f8d7da;
    color: #721c24;
}

.btn-reject:hover {
    background: #f5c6cb;
}

.btn-view {
    background: #e2e3e5;
    color: #383d41;
    flex: 0.5;
}

.btn-view:hover {
    background: #d6d8db;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
    grid-column: 1 / -1;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    color: #ddd;
}

.empty-state h3 {
    font-size: 18px;
    color: #666;
    margin: 0 0 10px;
}

.empty-state p {
    font-size: 13px;
    margin: 0 0 20px;
}

/* History Table */
.history-table-container {
    background: white;
    border-radius: 8px;
    overflow-x: auto;
}

.history-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.history-table th {
    background: #f8f9fa;
    padding: 12px;
    font-weight: 600;
    color: #666;
    border-bottom: 2px solid #dee2e6;
    text-align: left;
}

.history-table td {
    padding: 12px;
    border-bottom: 1px solid #f0f0f0;
}

.history-table tr:hover {
    background: #f8f9fa;
}

.employee-name {
    display: flex;
    flex-direction: column;
}

.employee-name small {
    color: #666;
    font-size: 10px;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-approved {
    background: #d4edda;
    color: #155724;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.text-success {
    color: #27ae60;
}

.text-danger {
    color: #e74c3c;
}

.btn-icon {
    background: none;
    border: none;
    padding: 5px;
    cursor: pointer;
    color: #3498db;
    transition: color 0.3s;
}

.btn-icon:hover {
    color: #2980b9;
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
    max-width: 700px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-dialog.modal-lg {
    max-width: 900px;
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
    max-height: calc(90vh - 130px);
    overflow-y: auto;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.btn-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #666;
    padding: 0 5px;
}

.btn-close:hover {
    color: #333;
}

/* Form Styles */
.form-section {
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.form-section h6 {
    font-size: 14px;
    color: #333;
    margin: 0 0 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-section h6 i {
    color: #3498db;
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
    transition: border-color 0.3s;
}

.form-control:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 0 2px rgba(52,152,219,0.1);
}

.form-control[readonly] {
    background: #f5f5f5;
    cursor: not-allowed;
}

.form-check {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 15px 0;
}

.form-check-input {
    margin: 0;
    width: 16px;
    height: 16px;
}

.required {
    color: #e74c3c;
}

.row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.col-md-6 {
    grid-column: span 1;
}

/* Info Panel */
.info-panel {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e0e0e0;
}

.info-row:last-child {
    border-bottom: none;
}

.info-row.highlight {
    background: #e7f3ff;
    margin: 8px -15px -15px;
    padding: 12px 15px;
    border-radius: 0 0 8px 8px;
    font-weight: 600;
}

.info-label {
    font-size: 12px;
    color: #666;
}

.info-value {
    font-size: 13px;
    font-weight: 500;
    color: #333;
}

/* Comparison Panel */
.comparison-panel {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
}

.comparison-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e0e0e0;
}

.comparison-row:last-child {
    border-bottom: none;
}

.comparison-row.highlight {
    font-weight: 600;
    font-size: 14px;
}

.old-amount {
    color: #e74c3c;
}

.new-amount {
    color: #27ae60;
}

.diff-amount.increase {
    color: #27ae60;
}

.diff-amount.decrease {
    color: #e74c3c;
}

/* Buttons */
.btn-secondary {
    background: #95a5a6;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    transition: background 0.3s;
}

.btn-secondary:hover {
    background: #7f8c8d;
}

/* Alert */
.alert {
    padding: 12px 20px;
    border-radius: 4px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert i {
    font-size: 16px;
}

/* Responsive */
@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .welcome-banner {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .adjustments-grid {
        grid-template-columns: 1fr;
    }
    
    .tab-header {
        flex-wrap: wrap;
    }
    
    .tab-btn {
        flex: 1;
        padding: 12px 10px;
        font-size: 12px;
    }
    
    .row {
        grid-template-columns: 1fr;
    }
    
    .salary-change-section {
        flex-direction: column;
        gap: 15px;
    }
    
    .change-arrow {
        transform: rotate(90deg);
    }
}

@media (max-width: 480px) {
    .card-footer {
        flex-direction: column;
    }
    
    .employee-section {
        flex-direction: column;
        text-align: center;
    }
    
    .info-row {
        flex-direction: column;
        gap: 5px;
    }
}
</style>

