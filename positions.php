<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();
$error = '';
$success = '';

// Handle status messages
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Get filter parameters
$office_filter = isset($_GET['office']) ? (int)$_GET['office'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$query = "SELECT p.*, o.office_name, sg.salary_grade as salary_grade_name,
          parent.position_title as parent_position_title
          FROM positions p
          LEFT JOIN offices o ON p.office_id = o.id
          LEFT JOIN salary_grades sg ON p.salary_grade_id = sg.id
          LEFT JOIN positions parent ON p.parent_position_id = parent.id
          WHERE 1=1";

if ($office_filter > 0) {
    $query .= " AND p.office_id = $office_filter";
}
if (!empty($status_filter)) {
    $query .= " AND p.status = '$status_filter'";
}

$query .= " ORDER BY o.office_name, p.position_level, p.position_title";
$positions = $db->query($query);

// Get offices for filter
$offices = $db->query("SELECT * FROM offices WHERE status = 'active' ORDER BY office_name");

include 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/positions.css">

<div class="positions-container">
    <div class="page-header">
        <div class="header-left">
            <h2><i class="fas fa-sitemap"></i> Positions Management</h2>
            <p>Manage job positions and organizational hierarchy</p>
        </div>
        <a href="add_position.php" class="btn-primary">
            <i class="fas fa-plus"></i> Add New Position
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="filters-card">
        <form method="GET" action="" class="filters-form">
            <div class="filter-group">
                <select name="office" class="filter-select">
                    <option value="">All Offices</option>
                    <?php while($off = $offices->fetch_assoc()): ?>
                    <option value="<?php echo $off['id']; ?>" <?php echo $office_filter == $off['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($off['office_name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <select name="status" class="filter-select">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="filter-group">
                <button type="submit" class="btn-filter">Filter</button>
                <a href="positions.php" class="btn-reset">Reset</a>
            </div>
        </form>
    </div>

    <!-- Positions Table -->
    <div class="card">
        <div class="card-body">
            <table class="positions-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Position Title</th>
                        <th>Office/Department</th>
                        <th>Reports To</th>
                        <th>Level</th>
                        <th>Salary Grade</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($positions && $positions->num_rows > 0): ?>
                        <?php while($pos = $positions->fetch_assoc()): ?>
                        <tr class="<?php echo $pos['is_head'] ? 'head-position' : ''; ?>">
                            <td><span class="position-code"><?php echo htmlspecialchars($pos['position_code']); ?></span></td>
                            <td>
                                <div class="position-title">
                                    <?php if ($pos['is_head']): ?>
                                        <i class="fas fa-crown" title="Office Head"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($pos['position_title']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($pos['office_name']); ?></td>
                            <td>
                                <?php if (!empty($pos['parent_position_title'])): ?>
                                    <?php echo htmlspecialchars($pos['parent_position_title']); ?>
                                <?php else: ?>
                                    <em>Top Level</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="level-badge level-<?php echo $pos['position_level']; ?>">
                                    Level <?php echo $pos['position_level']; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($pos['salary_grade_name'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($pos['is_head']): ?>
                                    <span class="badge badge-head">Department Head</span>
                                <?php else: ?>
                                    <span class="badge badge-staff">Staff</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $pos['status']; ?>">
                                    <?php echo ucfirst($pos['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view_position.php?id=<?php echo $pos['id']; ?>" class="action-btn view" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_position.php?id=<?php echo $pos['id']; ?>" class="action-btn edit" title="Edit Position">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="action-btn delete" onclick="confirmDelete(<?php echo $pos['id']; ?>)" title="Delete Position">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="no-data">No positions found. Click "Add New Position" to create one.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <div class="modal-header">
            <i class="fas fa-exclamation-triangle" style="color: #e74c3c; font-size: 48px;"></i>
            <h3>Confirm Deletion</h3>
        </div>
        <p>Are you sure you want to delete this position?</p>
        <p class="warning-text">This action cannot be undone. All associated data will be permanently removed.</p>
        <p>Type <strong>"confirm"</strong> in the box below to proceed with deletion.</p>
        <form action="delete_position.php" method="POST" id="deleteForm">
            <input type="hidden" name="id" id="delete_id">
            <div class="form-group">
                <input type="text" name="confirm_delete" id="confirm_delete" 
                       placeholder="Type 'confirm' here" required 
                       pattern="confirm" title="Please type 'confirm' exactly">
            </div>
            <div class="form-actions">
                <button type="submit" name="delete_position" class="btn-danger">Delete Position</button>
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmDelete(id) {
    document.getElementById('delete_id').value = id;
    document.getElementById('confirm_delete').value = '';
    document.getElementById('deleteModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

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