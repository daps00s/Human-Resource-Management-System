<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();

// Handle add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_office'])) {
        $office_code = $_POST['office_code'];
        $office_name = $_POST['office_name'];
        $office_head = $_POST['office_head'];
        $location = $_POST['location'];
        $contact_number = $_POST['contact_number'];
        $email = $_POST['email'];
        
        $db->query("INSERT INTO offices (office_code, office_name, office_head, location, contact_number, email) 
                   VALUES ('$office_code', '$office_name', '$office_head', '$location', '$contact_number', '$email')");
    } elseif (isset($_POST['edit_office'])) {
        $id = $_POST['id'];
        $office_code = $_POST['office_code'];
        $office_name = $_POST['office_name'];
        $office_head = $_POST['office_head'];
        $location = $_POST['location'];
        $contact_number = $_POST['contact_number'];
        $email = $_POST['email'];
        $status = $_POST['status'];
        
        $db->query("UPDATE offices SET office_code='$office_code', office_name='$office_name', 
                   office_head='$office_head', location='$location', contact_number='$contact_number', 
                   email='$email', status='$status' WHERE id=$id");
    } elseif (isset($_POST['delete_office'])) {
        $id = $_POST['id'];
        $db->query("DELETE FROM offices WHERE id=$id");
    }
}

$offices = $db->query("SELECT * FROM offices ORDER BY office_name");

include 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/offices.css">

<div class="offices-container">
    <div class="page-header">
        <h2><i class="fas fa-building"></i> Offices/Departments</h2>
        <button class="btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i> Add Office
        </button>
    </div>

    <!-- Offices Table -->
    <div class="card">
        <div class="card-body">
            <table class="offices-table">
                <thead>
                    <tr>
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
                    <?php while($off = $offices->fetch_assoc()): ?>
                    <tr>
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
                                <button class="action-btn edit" onclick="editOffice(<?php echo $off['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn delete" onclick="deleteOffice(<?php echo $off['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeAddModal()">&times;</span>
        <h3>Add New Office</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label>Office Code</label>
                <input type="text" name="office_code" required>
            </div>
            <div class="form-group">
                <label>Office Name</label>
                <input type="text" name="office_name" required>
            </div>
            <div class="form-group">
                <label>Office Head</label>
                <input type="text" name="office_head">
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location">
            </div>
            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="contact_number">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email">
            </div>
            <button type="submit" name="add_office" class="btn-primary">Save Office</button>
        </form>
    </div>
</div>

<script>
function showAddModal() {
    document.getElementById('addModal').style.display = 'block';
}

function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
}

function editOffice(id) {
    // Implement edit functionality
    alert('Edit office ' + id);
}

function deleteOffice(id) {
    if (confirm('Are you sure you want to delete this office?')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="id" value="' + id + '"><input type="hidden" name="delete_office" value="1">';
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('addModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>