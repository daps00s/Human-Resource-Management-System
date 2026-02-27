<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$office = isset($_GET['office']) ? $_GET['office'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$employment_status = isset($_GET['employment_status']) ? $_GET['employment_status'] : '';

// Build query
$query = "SELECT u.*, o.office_name, es.status_name,
          ee.position, ee.employment_status_id
          FROM users u 
          LEFT JOIN employee_employment ee ON u.id = ee.user_id
          LEFT JOIN offices o ON ee.office_id = o.id
          LEFT JOIN employment_status es ON ee.employment_status_id = es.id
          WHERE u.role = 'employee'";

if (!empty($search)) {
    $query .= " AND (u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' 
               OR u.middle_name LIKE '%$search%' OR u.employee_id LIKE '%$search%')";
}
if (!empty($office)) {
    $query .= " AND o.id = $office";
}
if (!empty($status)) {
    $query .= " AND ee.employment_status_id = $status";
}
if (!empty($employment_status)) {
    $query .= " AND u.employment_status = '$employment_status'";
}

$query .= " ORDER BY u.last_name ASC";
$result = $db->query($query);

// Get offices for filter
$offices = $db->query("SELECT * FROM offices WHERE status = 'active' ORDER BY office_name");
$statuses = $db->query("SELECT * FROM employment_status ORDER BY status_name");

include 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/employee_list.css">

<div class="employee-list-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h2><i class="fas fa-users"></i> Employee List</h2>
            <p>Manage and view all employees</p>
        </div>
        <div class="header-actions">
            <a href="add_employee.php" class="btn-primary">
                <i class="fas fa-plus"></i> Add New Employee
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-card">
        <form method="GET" action="" class="filters-form">
            <div class="filter-group">
                <input type="text" name="search" placeholder="Search by name or ID..." 
                       value="<?php echo htmlspecialchars($search); ?>" class="search-input">
            </div>
            <div class="filter-group">
                <select name="office" class="filter-select">
                    <option value="">All Offices</option>
                    <?php while($off = $offices->fetch_assoc()): ?>
                    <option value="<?php echo $off['id']; ?>" <?php echo $office == $off['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($off['office_name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <select name="status" class="filter-select">
                    <option value="">All Employment Status</option>
                    <?php while($stat = $statuses->fetch_assoc()): ?>
                    <option value="<?php echo $stat['id']; ?>" <?php echo $status == $stat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($stat['status_name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-group">
                <select name="employment_status" class="filter-select">
                    <option value="">All Status</option>
                    <option value="Active" <?php echo $employment_status == 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Retired" <?php echo $employment_status == 'Retired' ? 'selected' : ''; ?>>Retired</option>
                    <option value="Resigned" <?php echo $employment_status == 'Resigned' ? 'selected' : ''; ?>>Resigned</option>
                    <option value="Deceased" <?php echo $employment_status == 'Deceased' ? 'selected' : ''; ?>>Deceased</option>
                </select>
            </div>
            <div class="filter-group">
                <button type="submit" class="btn-filter">Filter</button>
                <a href="employee_list.php" class="btn-reset">Reset</a>
            </div>
        </form>
    </div>

    <!-- Employee Table -->
    <div class="card">
        <div class="card-body">
            <table class="employee-table">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Office/Department</th>
                        <th>Position</th>
                        <th>Status</th>
                        <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($emp = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="employee-photo">
                                    <?php if (!empty($emp['profile_picture'])): ?>
                                        <img src="uploads/profiles/<?php echo $emp['profile_picture']; ?>" alt="Profile">
                                    <?php else: ?>
                                        <div class="photo-placeholder">
                                            <?php echo strtoupper(substr($emp['first_name'] ?? $emp['username'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><span class="employee-id"><?php echo htmlspecialchars($emp['employee_id'] ?? 'N/A'); ?></span></td>
                            <td>
                                <div class="employee-name">
                                    <div>
                                        <div><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></div>
                                        <small><?php echo htmlspecialchars($emp['username']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($emp['office_name'] ?? 'Not Assigned'); ?></td>
                            <td><?php echo htmlspecialchars($emp['position'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($emp['status_name'] ?? 'regular'); ?>">
                                    <?php echo htmlspecialchars($emp['status_name'] ?? 'Regular'); ?>
                                </span>
                                <br>
                                <small class="emp-status-<?php echo strtolower($emp['employment_status'] ?? 'active'); ?>">
                                    <?php echo $emp['employment_status'] ?? 'Active'; ?>
                                </small>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($emp['contact_number'] ?? 'N/A'); ?></div>
                                <small><?php echo htmlspecialchars($emp['email']); ?></small>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="employee_profile.php?id=<?php echo $emp['id']; ?>" class="action-btn view" title="View Profile">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_employee.php?id=<?php echo $emp['id']; ?>" class="action-btn edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="no-data">No employees found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>