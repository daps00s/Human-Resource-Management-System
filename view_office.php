<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = isset($_GET['success']) ? $_GET['success'] : '';

if ($id === 0) {
    header("Location: offices.php");
    exit();
}

$office = $db->query("SELECT * FROM offices WHERE id = $id")->fetch_assoc();

if (!$office) {
    header("Location: offices.php?error=" . urlencode("Office not found"));
    exit();
}

// Get all employees in this office
$employees = $db->query("
    SELECT 
        u.id,
        u.first_name,
        u.last_name,
        u.middle_name,
        u.suffix,
        u.profile_picture,
        u.employee_id,
        u.email,
        u.employment_status,
        ee.position,
        ee.supervisor_id,
        ee.date_hired,
        es.status_name as employment_status_name,
        CONCAT(sup.first_name, ' ', sup.last_name) as supervisor_name
    FROM users u 
    INNER JOIN employee_employment ee ON u.id = ee.user_id 
    LEFT JOIN users sup ON ee.supervisor_id = sup.id
    LEFT JOIN employment_status es ON ee.employment_status_id = es.id
    WHERE ee.office_id = $id AND u.role = 'employee'
    ORDER BY 
        CASE WHEN ee.supervisor_id IS NULL THEN 0 ELSE 1 END,
        u.last_name ASC
");

include 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/office_view.css">

<div class="view-office-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h2><i class="fas fa-building"></i> <?php echo htmlspecialchars($office['office_name']); ?></h2>
            <p>Office Details and Employee List</p>
        </div>
        <div class="header-actions">
            <a href="edit_office.php?id=<?php echo $id; ?>" class="btn-primary">
                <i class="fas fa-edit"></i> Edit Office
            </a>
            <a href="offices.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Offices
            </a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Office Info Card -->
    <div class="office-info-card">
        <div class="office-header">
            <div class="office-logo">
                <?php if (!empty($office['logo'])): ?>
                    <img src="uploads/offices/<?php echo $office['logo']; ?>" alt="<?php echo $office['office_name']; ?>">
                <?php else: ?>
                    <div class="logo-placeholder">
                        <?php echo strtoupper(substr($office['office_code'], 0, 2)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="office-title">
                <h1><?php echo htmlspecialchars($office['office_name']); ?></h1>
                <div class="office-meta">
                    <span class="office-code-badge"><?php echo htmlspecialchars($office['office_code']); ?></span>
                    <span class="status-badge status-<?php echo $office['status']; ?>">
                        <?php echo ucfirst($office['status']); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="office-details">
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-icon"><i class="fas fa-user-tie"></i></span>
                    <div>
                        <div class="detail-label">Office Head</div>
                        <div class="detail-value"><?php echo htmlspecialchars($office['office_head'] ?? 'Not Assigned'); ?></div>
                    </div>
                </div>
                <div class="detail-item">
                    <span class="detail-icon"><i class="fas fa-map-marker-alt"></i></span>
                    <div>
                        <div class="detail-label">Location</div>
                        <div class="detail-value"><?php echo htmlspecialchars($office['location'] ?? 'Not Specified'); ?></div>
                    </div>
                </div>
                <div class="detail-item">
                    <span class="detail-icon"><i class="fas fa-phone"></i></span>
                    <div>
                        <div class="detail-label">Contact</div>
                        <div class="detail-value">
                            <?php if (!empty($office['contact_number'])): ?>
                                <a href="tel:<?php echo $office['contact_number']; ?>"><?php echo $office['contact_number']; ?></a>
                            <?php else: ?>
                                Not Provided
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="detail-item">
                    <span class="detail-icon"><i class="fas fa-envelope"></i></span>
                    <div>
                        <div class="detail-label">Email</div>
                        <div class="detail-value">
                            <?php if (!empty($office['email'])): ?>
                                <a href="mailto:<?php echo $office['email']; ?>"><?php echo $office['email']; ?></a>
                            <?php else: ?>
                                Not Provided
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-section">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $employees->num_rows; ?></div>
                <div class="stat-label">Total Employees</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?php echo date('M d, Y', strtotime($office['created_at'])); ?></div>
                <div class="stat-label">Established</div>
            </div>
        </div>
    </div>

    <!-- Employee List -->
    <div class="employee-list-section">
        <div class="section-header">
            <h3><i class="fas fa-list"></i> Employees in this Office</h3>
            <span class="employee-count"><?php echo $employees->num_rows; ?> Employees</span>
        </div>

        <?php if ($employees && $employees->num_rows > 0): ?>
            <div class="employee-grid">
                <?php while($emp = $employees->fetch_assoc()): ?>
                    <div class="employee-card">
                        <div class="employee-avatar">
                            <?php if (!empty($emp['profile_picture'])): ?>
                                <img src="uploads/profiles/<?php echo $emp['profile_picture']; ?>" alt="Profile">
                            <?php else: ?>
                                <div class="avatar-placeholder">
                                    <?php echo strtoupper(substr($emp['first_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="employee-info">
                            <h4>
                                <?php 
                                $full_name = $emp['first_name'] . ' ' . $emp['last_name'];
                                if (!empty($emp['suffix'])) {
                                    $full_name .= ' ' . $emp['suffix'];
                                }
                                echo htmlspecialchars($full_name);
                                ?>
                            </h4>
                            <p class="employee-position"><?php echo htmlspecialchars($emp['position'] ?? 'No Position'); ?></p>
                            <p class="employee-id">ID: <?php echo htmlspecialchars($emp['employee_id'] ?? 'N/A'); ?></p>
                            <p class="employee-supervisor">
                                <i class="fas fa-user-tie"></i> 
                                <?php echo !empty($emp['supervisor_name']) ? htmlspecialchars($emp['supervisor_name']) : 'No Supervisor'; ?>
                            </p>
                            <span class="status-badge status-<?php echo strtolower($emp['employment_status_name'] ?? 'active'); ?>">
                                <?php echo htmlspecialchars($emp['employment_status_name'] ?? 'Active'); ?>
                            </span>
                        </div>
                        <div class="employee-actions">
                            <a href="employee_profile.php?id=<?php echo $emp['id']; ?>" class="btn-view" title="View Profile">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-employees">
                <i class="fas fa-users"></i>
                <p>No employees currently assigned to this office.</p>
                <a href="add_employee.php?office=<?php echo $id; ?>" class="btn-small">Add Employee</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-hide alerts after 5 seconds
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);
</script>

<?php include 'includes/footer.php'; ?>