<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();
$error = '';
$success = '';

// Handle status messages from other pages
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

$offices = $db->query("SELECT * FROM offices ORDER BY office_name");

include 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/offices.css">

<div class="offices-container">
<div class="page-header">
    <div class="header-left">
        <h2><i class="fas fa-building"></i> Offices/Departments</h2>
        <p>Manage all offices and departments in the organization</p>
    </div>
    <a href="add_office.php" class="btn-primary">
        <i class="fas fa-plus"></i> Add New Office
    </a>
</div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Offices Table -->
    <div class="card">
        <div class="card-body">
            <table class="offices-table">
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Code</th>
                        <th>Office Name</th>
                        <th>Office Head</th>
                        <th>Location</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($offices->num_rows > 0): ?>
                        <?php while($off = $offices->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="office-logo">
                                    <?php if (!empty($off['logo'])): ?>
                                        <img src="uploads/offices/<?php echo $off['logo']; ?>" alt="<?php echo $off['office_name']; ?>">
                                    <?php else: ?>
                                        <div class="logo-placeholder">
                                            <?php echo strtoupper(substr($off['office_code'], 0, 2)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><span class="office-code"><?php echo htmlspecialchars($off['office_code']); ?></span></td>
                            <td><?php echo htmlspecialchars($off['office_name']); ?></td>
                            <td><?php echo htmlspecialchars($off['office_head'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($off['location'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($off['contact_number'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($off['email'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $off['status']; ?>">
                                    <?php echo ucfirst($off['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="view_office.php?id=<?php echo $off['id']; ?>" class="action-btn view" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_office.php?id=<?php echo $off['id']; ?>" class="action-btn edit" title="Edit Office">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="action-btn delete" onclick="confirmDelete(<?php echo $off['id']; ?>)" title="Delete Office">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="no-data">No offices found. Click "Add New Office" to create one.</td>
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
        <p>Are you sure you want to delete this office?</p>
        <p class="warning-text">This action cannot be undone. All associated data will be permanently removed.</p>
        <p>Type <strong>"confirm"</strong> in the box below to proceed with deletion.</p>
        <form action="delete_office.php" method="POST" id="deleteForm">
            <input type="hidden" name="id" id="delete_id">
            <div class="form-group">
                <input type="text" name="confirm_delete" id="confirm_delete" 
                       placeholder="Type 'confirm' here" required 
                       pattern="confirm" title="Please type 'confirm' exactly">
            </div>
            <div class="form-actions">
                <button type="submit" name="delete_office" class="btn-danger">Delete Office</button>
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
