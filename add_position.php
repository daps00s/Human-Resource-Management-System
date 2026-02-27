<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();
$error = '';
$success = '';

// Get offices for dropdown
$offices = $db->query("SELECT * FROM offices WHERE status = 'active' ORDER BY office_name");

// Get salary grades for dropdown
$salary_grades = $db->query("SELECT * FROM salary_grades ORDER BY salary_grade, step");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $position_code = trim($_POST['position_code'] ?? '');
    $position_title = trim($_POST['position_title'] ?? '');
    $position_description = trim($_POST['position_description'] ?? '');
    $office_id = (int)($_POST['office_id'] ?? 0);
    $parent_position_id = !empty($_POST['parent_position_id']) ? (int)$_POST['parent_position_id'] : null;
    $position_level = (int)($_POST['position_level'] ?? 1);
    $salary_grade_id = !empty($_POST['salary_grade_id']) ? (int)$_POST['salary_grade_id'] : null;
    $is_head = isset($_POST['is_head']) ? 1 : 0;
    $status = $_POST['status'] ?? 'active';
    
    // Validate required fields
    if (empty($position_code) || empty($position_title) || $office_id === 0) {
        $error = "Position Code, Position Title, and Office are required fields.";
    } else {
        // Check if position code already exists
        $check = $db->query("SELECT id FROM positions WHERE position_code = '$position_code'");
        if ($check->num_rows > 0) {
            $error = "Position Code already exists. Please use a different code.";
        } else {
            $query = "INSERT INTO positions (
                position_code, position_title, position_description, 
                office_id, parent_position_id, position_level, 
                salary_grade_id, is_head, status
            ) VALUES (
                '$position_code', '$position_title', " . ($position_description ? "'$position_description'" : "NULL") . ",
                $office_id, " . ($parent_position_id ?: 'NULL') . ", $position_level,
                " . ($salary_grade_id ?: 'NULL') . ", $is_head, '$status'
            )";
            
            if ($db->query($query)) {
                header("Location: positions.php?success=" . urlencode("Position added successfully"));
                exit();
            } else {
                $error = "Failed to add position. Please try again.";
            }
        }
    }
}

// Get parent positions for dropdown (based on selected office)
$parent_positions = [];
if (isset($_POST['office_id']) && $_POST['office_id'] > 0) {
    $office_id = (int)$_POST['office_id'];
    $parent_query = $db->query("SELECT * FROM positions WHERE office_id = $office_id AND status = 'active' ORDER BY position_level, position_title");
    while($p = $parent_query->fetch_assoc()) {
        $parent_positions[] = $p;
    }
}

include 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/add_position.css">

<div class="add-position-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h2><i class="fas fa-sitemap"></i> Add New Position</h2>
            <p>Create a new job position in the organization</p>
        </div>
        <a href="positions.php" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Positions
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="" class="position-form">
        <!-- Position Details -->
        <div class="form-section">
            <h3><i class="fas fa-info-circle"></i> Position Details</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Position Code <span class="required">*</span></label>
                    <input type="text" name="position_code" required 
                           value="<?php echo htmlspecialchars($_POST['position_code'] ?? ''); ?>"
                           placeholder="e.g., HR-MGR-001" maxlength="50">
                    <small>Unique identifier for the position</small>
                </div>

                <div class="form-group">
                    <label>Position Title <span class="required">*</span></label>
                    <input type="text" name="position_title" required 
                           value="<?php echo htmlspecialchars($_POST['position_title'] ?? ''); ?>"
                           placeholder="e.g., Human Resources Manager">
                </div>

                <div class="form-group">
                    <label>Office/Department <span class="required">*</span></label>
                    <select name="office_id" id="office_id" required onchange="loadParentPositions(this.value)">
                        <option value="">Select Office</option>
                        <?php 
                        $offices->data_seek(0);
                        while($off = $offices->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $off['id']; ?>" <?php echo ($_POST['office_id'] ?? '') == $off['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($off['office_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Reports To (Parent Position)</label>
                    <select name="parent_position_id" id="parent_position_id">
                        <option value="">Top Level (No Supervisor)</option>
                        <?php foreach($parent_positions as $parent): ?>
                        <option value="<?php echo $parent['id']; ?>" <?php echo ($_POST['parent_position_id'] ?? '') == $parent['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($parent['position_title']); ?> (Level <?php echo $parent['position_level']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Position Level</label>
                    <select name="position_level">
                        <option value="1" <?php echo ($_POST['position_level'] ?? 1) == 1 ? 'selected' : ''; ?>>Level 1 (Highest)</option>
                        <option value="2" <?php echo ($_POST['position_level'] ?? 1) == 2 ? 'selected' : ''; ?>>Level 2</option>
                        <option value="3" <?php echo ($_POST['position_level'] ?? 1) == 3 ? 'selected' : ''; ?>>Level 3</option>
                        <option value="4" <?php echo ($_POST['position_level'] ?? 1) == 4 ? 'selected' : ''; ?>>Level 4</option>
                        <option value="5" <?php echo ($_POST['position_level'] ?? 1) == 5 ? 'selected' : ''; ?>>Level 5 (Lowest)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Salary Grade</label>
                    <select name="salary_grade_id">
                        <option value="">Not Set</option>
                        <?php 
                        $salary_grades->data_seek(0);
                        while($sg = $salary_grades->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $sg['id']; ?>" <?php echo ($_POST['salary_grade_id'] ?? '') == $sg['id'] ? 'selected' : ''; ?>>
                            <?php echo $sg['salary_grade'] . ' - Step ' . $sg['step'] . ' (₱' . number_format($sg['monthly_salary'], 2) . ')'; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="is_head" value="1" <?php echo isset($_POST['is_head']) ? 'checked' : ''; ?>>
                        This is the Department Head position
                    </label>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="active" <?php echo ($_POST['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($_POST['status'] ?? 'active') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label>Description</label>
                    <textarea name="position_description" rows="4" placeholder="Describe the responsibilities and requirements of this position"><?php echo htmlspecialchars($_POST['position_description'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="submit" name="add_position" class="btn-primary">
                <i class="fas fa-save"></i> Save Position
            </button>
            <a href="positions.php" class="btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<script>
function loadParentPositions(officeId) {
    if (officeId) {
        // In a real application, you would make an AJAX call here
        // For now, we'll reload the page with the selected office to show parent positions
        // This is a simple approach - for better UX, implement AJAX
        window.location.href = 'add_position.php?office=' + officeId;
    }
}

// If office is preselected from URL parameter
<?php if (isset($_GET['office'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    var officeSelect = document.getElementById('office_id');
    officeSelect.value = <?php echo (int)$_GET['office']; ?>;
    // Trigger change if needed
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>