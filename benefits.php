<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Handle actions
$message = '';
$message_type = '';

// Process benefit updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_benefits':
                $user_id = $_POST['user_id'];
                $gsis_number = $_POST['gsis_number'] ?? null;
                $pagibig_number = $_POST['pagibig_number'] ?? null;
                $philhealth_number = $_POST['philhealth_number'] ?? null;
                $tin_number = $_POST['tin_number'] ?? null;
                
                // Check if sss_number column exists
                $check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'sss_number'");
                $has_sss = $check_column->num_rows > 0;
                
                if ($has_sss) {
                    $sss_number = $_POST['sss_number'] ?? null;
                    $update_sql = "UPDATE users SET 
                                    gsis_number = ?,
                                    pagibig_number = ?,
                                    philhealth_number = ?,
                                    tin_number = ?,
                                    sss_number = ?,
                                    updated_at = NOW()
                                  WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("sssssi", 
                        $gsis_number, 
                        $pagibig_number, 
                        $philhealth_number, 
                        $tin_number, 
                        $sss_number, 
                        $user_id
                    );
                } else {
                    $update_sql = "UPDATE users SET 
                                    gsis_number = ?,
                                    pagibig_number = ?,
                                    philhealth_number = ?,
                                    tin_number = ?,
                                    updated_at = NOW()
                                  WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("ssssi", 
                        $gsis_number, 
                        $pagibig_number, 
                        $philhealth_number, 
                        $tin_number, 
                        $user_id
                    );
                }
                
                if ($update_stmt->execute()) {
                    $message = "Employee benefits updated successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error updating benefits: " . $update_stmt->error;
                    $message_type = "error";
                }
                $update_stmt->close();
                break;
        }
    }
}

// Check if sss_number column exists for the queries
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'sss_number'");
$has_sss = $check_column->num_rows > 0;

// Fetch benefits statistics
$benefits_sql = "
    SELECT 
        COUNT(DISTINCT u.id) as total_employees,
        SUM(CASE WHEN u.gsis_number IS NOT NULL AND u.gsis_number != '' THEN 1 ELSE 0 END) as with_gsis,
        SUM(CASE WHEN u.pagibig_number IS NOT NULL AND u.pagibig_number != '' THEN 1 ELSE 0 END) as with_pagibig,
        SUM(CASE WHEN u.philhealth_number IS NOT NULL AND u.philhealth_number != '' THEN 1 ELSE 0 END) as with_philhealth,
        SUM(CASE WHEN u.tin_number IS NOT NULL AND u.tin_number != '' THEN 1 ELSE 0 END) as with_tin,
        SUM(CASE WHEN u.gsis_number IS NULL OR u.gsis_number = '' THEN 1 ELSE 0 END) as missing_gsis,
        SUM(CASE WHEN u.pagibig_number IS NULL OR u.pagibig_number = '' THEN 1 ELSE 0 END) as missing_pagibig,
        SUM(CASE WHEN u.philhealth_number IS NULL OR u.philhealth_number = '' THEN 1 ELSE 0 END) as missing_philhealth,
        SUM(CASE WHEN u.tin_number IS NULL OR u.tin_number = '' THEN 1 ELSE 0 END) as missing_tin
    FROM users u
    WHERE u.role != 'admin'
";
$benefits_result = $conn->query($benefits_sql);
$benefits_stats = $benefits_result->fetch_assoc();

// Get employee benefits details
$employees_sql = "
    SELECT 
        u.id,
        u.employee_id,
        u.first_name,
        u.last_name,
        u.gsis_number,
        u.pagibig_number,
        u.philhealth_number,
        u.tin_number" .
        ($has_sss ? ", u.sss_number" : "") . ",
        ee.position,
        o.office_name,
        ee.monthly_salary,
        sg.salary_grade,
        ee.step
    FROM users u
    LEFT JOIN employee_employment ee ON u.id = ee.user_id
    LEFT JOIN salary_grades sg ON ee.salary_grade_id = sg.id
    LEFT JOIN offices o ON ee.office_id = o.id
    WHERE u.role != 'admin'
    ORDER BY u.last_name
";
$employees_result = $conn->query($employees_sql);

// Calculate contribution rates
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
            <h1><i class="fas fa-gift"></i> Benefits Management</h1>
            <p>Manage government-mandated benefits: GSIS, Pag-IBIG, PhilHealth, and TIN</p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn-primary" onclick="openBulkImportModal()">
                <i class="fas fa-upload"></i> Bulk Import
            </button>
            <button type="button" class="btn-primary" onclick="exportBenefitsReport()">
                <i class="fas fa-file-excel"></i> Export
            </button>
        </div>
    </div>
    
    <div class="breadcrumb-wrapper">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="salary_grade.php">Salary & Compensation</a></li>
                <li class="breadcrumb-item active">Benefits Management</li>
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

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon bg-primary">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $benefits_stats['total_employees']; ?></div>
            <div class="stat-label">Total Employees</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon bg-success">
            <i class="fas fa-id-card"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $benefits_stats['with_gsis']; ?></div>
            <div class="stat-label">With GSIS</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon bg-warning">
            <i class="fas fa-home"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $benefits_stats['with_pagibig']; ?></div>
            <div class="stat-label">With Pag-IBIG</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon bg-danger">
            <i class="fas fa-heartbeat"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $benefits_stats['with_philhealth']; ?></div>
            <div class="stat-label">With PhilHealth</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon bg-info">
            <i class="fas fa-file-invoice"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?php echo $benefits_stats['with_tin']; ?></div>
            <div class="stat-label">With TIN</div>
        </div>
    </div>
</div>


<!-- Benefits Table -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> Employee Benefits</h3>
        <div class="card-actions">
            <div class="filter-group">
                <select class="filter-select" id="benefitFilter" onchange="filterTable()">
                    <option value="all">All Employees</option>
                    <option value="missing-gsis">Missing GSIS</option>
                    <option value="missing-pagibig">Missing Pag-IBIG</option>
                    <option value="missing-philhealth">Missing PhilHealth</option>
                    <option value="missing-tin">Missing TIN</option>
                </select>
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-box" id="searchEmployee" placeholder="Search employee..." onkeyup="filterTable()">
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table" id="benefitsTable">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Position</th>
                        <th>Monthly Salary</th>
                        <th>GSIS</th>
                        <th>Pag-IBIG</th>
                        <th>PhilHealth</th>
                        <th>TIN</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $employees_result->data_seek(0);
                    while ($emp = $employees_result->fetch_assoc()): 
                    ?>
                    <tr data-gsis="<?php echo empty($emp['gsis_number']) ? 'missing' : 'complete'; ?>"
                        data-pagibig="<?php echo empty($emp['pagibig_number']) ? 'missing' : 'complete'; ?>"
                        data-philhealth="<?php echo empty($emp['philhealth_number']) ? 'missing' : 'complete'; ?>"
                        data-tin="<?php echo empty($emp['tin_number']) ? 'missing' : 'complete'; ?>">
                        <td>
                            <div class="employee-info">
                                <div class="employee-avatar">
                                    <?php echo strtoupper(substr($emp['first_name'] ?? 'U', 0, 1) . substr($emp['last_name'] ?? 'N', 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="employee-name"><?php echo htmlspecialchars($emp['last_name'] . ', ' . $emp['first_name']); ?></div>
                                    <div class="employee-id"><?php echo htmlspecialchars($emp['employee_id']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="position-info">
                                <div><?php echo htmlspecialchars($emp['position'] ?? 'N/A'); ?></div>
                                <?php if (!empty($emp['office_name'])): ?>
                                    <small><?php echo htmlspecialchars($emp['office_name']); ?></small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><span class="salary-amount">₱<?php echo number_format($emp['monthly_salary'] ?? 0, 2); ?></span></td>
                        <td>
                            <span class="benefit-status <?php echo $emp['gsis_number'] ? 'completed' : 'pending'; ?>">
                                <?php echo $emp['gsis_number'] ? substr($emp['gsis_number'], -4) : '—'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="benefit-status <?php echo $emp['pagibig_number'] ? 'completed' : 'pending'; ?>">
                                <?php echo $emp['pagibig_number'] ? substr($emp['pagibig_number'], -4) : '—'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="benefit-status <?php echo $emp['philhealth_number'] ? 'completed' : 'pending'; ?>">
                                <?php echo $emp['philhealth_number'] ? substr($emp['philhealth_number'], -4) : '—'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="benefit-status <?php echo $emp['tin_number'] ? 'completed' : 'pending'; ?>">
                                <?php echo $emp['tin_number'] ? substr($emp['tin_number'], -4) : '—'; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-icon" onclick="editBenefits(<?php echo $emp['id']; ?>)" title="Edit Benefits">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Benefits Modal -->
<div class="modal" id="editBenefitsModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Employee Benefits</h5>
                <button type="button" class="btn-close" onclick="closeModal('editBenefitsModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_benefits">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>GSIS Number</label>
                        <input type="text" class="form-control" name="gsis_number" id="edit_gsis">
                    </div>
                    <div class="form-group">
                        <label>Pag-IBIG Number</label>
                        <input type="text" class="form-control" name="pagibig_number" id="edit_pagibig">
                    </div>
                    <div class="form-group">
                        <label>PhilHealth Number</label>
                        <input type="text" class="form-control" name="philhealth_number" id="edit_philhealth">
                    </div>
                    <div class="form-group">
                        <label>TIN Number</label>
                        <input type="text" class="form-control" name="tin_number" id="edit_tin">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('editBenefitsModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Import Modal -->
<div class="modal" id="bulkImportModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Import Benefits</h5>
                <button type="button" class="btn-close" onclick="closeModal('bulkImportModal')">&times;</button>
            </div>
            <form action="benefits.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="bulk_update">
                <div class="modal-body">
                    <p class="text-muted">Upload a CSV file with columns: employee_id, gsis_number, pagibig_number, philhealth_number, tin_number</p>
                    <div class="form-group">
                        <label>CSV File</label>
                        <input type="file" class="form-control" name="import_file" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('bulkImportModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div>

<script>
function openBulkImportModal() {
    document.getElementById('bulkImportModal').classList.add('show');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

function editBenefits(id) {
    // In a real app, you'd fetch the data via AJAX
    // For now, just open the modal with empty fields
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_gsis').value = '';
    document.getElementById('edit_pagibig').value = '';
    document.getElementById('edit_philhealth').value = '';
    document.getElementById('edit_tin').value = '';
    document.getElementById('editBenefitsModal').classList.add('show');
}

function exportBenefitsReport() {
    alert('Export functionality will be implemented');
}

function filterTable() {
    const search = document.getElementById('searchEmployee').value.toLowerCase();
    const filter = document.getElementById('benefitFilter').value;
    const rows = document.getElementById('benefitsTable').getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const text = row.textContent.toLowerCase();
        const gsisMissing = row.dataset.gsis === 'missing';
        const pagibigMissing = row.dataset.pagibig === 'missing';
        const philhealthMissing = row.dataset.philhealth === 'missing';
        const tinMissing = row.dataset.tin === 'missing';
        
        let show = text.includes(search);
        
        if (filter === 'missing-gsis') show = show && gsisMissing;
        else if (filter === 'missing-pagibig') show = show && pagibigMissing;
        else if (filter === 'missing-philhealth') show = show && philhealthMissing;
        else if (filter === 'missing-tin') show = show && tinMissing;
        
        row.style.display = show ? '' : 'none';
    }
}

window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});
</script>

<style>
/* Dashboard Container */
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
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
}

.btn-primary:hover {
    background: rgba(255,255,255,0.25);
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
    font-size: 22px;
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
    font-weight: 600;
    color: #2c3e50;
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
}

.table td {
    padding: 12px 10px;
    border-bottom: 1px solid #ecf0f1;
    vertical-align: middle;
}

/* Employee Info */
.employee-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.employee-avatar {
    width: 32px;
    height: 32px;
    background: #3498db;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 600;
}

.employee-name {
    font-weight: 500;
    color: #2c3e50;
}

.employee-id {
    font-size: 11px;
    color: #95a5a6;
}

.position-info small {
    color: #95a5a6;
    font-size: 11px;
}

.salary-amount {
    font-weight: 500;
    color: #27ae60;
}

/* Benefit Status */
.benefit-status {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
}

.benefit-status.completed {
    background: #e8f5e9;
    color: #27ae60;
}

.benefit-status.pending {
    background: #fef9e7;
    color: #f39c12;
}

/* Buttons */
.btn-icon {
    background: none;
    border: none;
    padding: 5px;
    cursor: pointer;
    color: #95a5a6;
    border-radius: 3px;
}

.btn-icon:hover {
    color: #3498db;
    background: #f0f7ff;
}

.btn-secondary {
    background: #95a5a6;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
}

/* Modal */
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
    max-width: 400px;
    background: white;
    border-radius: 6px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

.modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ecf0f1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h5 {
    margin: 0;
    font-size: 16px;
    font-weight: 500;
}

.btn-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #95a5a6;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ecf0f1;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* Form */
.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-size: 12px;
    color: #2c3e50;
}

.form-control {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
    box-sizing: border-box;
}

.form-control:focus {
    border-color: #3498db;
    outline: none;
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

.alert-danger {
    background: #ffebee;
    color: #e74c3c;
    border: 1px solid #ffcdd2;
}

.text-muted {
    color: #7f8c8d;
    font-size: 12px;
    margin-bottom: 10px;
}

/* Responsive */
@media (max-width: 992px) {
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .welcome-banner {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    
    .quick-stats {
        flex-wrap: wrap;
    }
    
    .filter-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-box {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

