<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();
$error = '';
$success = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id === 0) {
    header("Location: offices.php");
    exit();
}

$office = $db->query("SELECT * FROM offices WHERE id = $id")->fetch_assoc();

if (!$office) {
    header("Location: offices.php?error=" . urlencode("Office not found"));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $office_code = trim($_POST['office_code'] ?? '');
    $office_name = trim($_POST['office_name'] ?? '');
    $office_head = trim($_POST['office_head'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    // Validate required fields
    if (empty($office_code) || empty($office_name)) {
        $error = "Office Code and Office Name are required fields.";
    } else {
        // Check if office code already exists (excluding current office)
        $check = $db->query("SELECT id FROM offices WHERE office_code = '$office_code' AND id != $id");
        if ($check->num_rows > 0) {
            $error = "Office Code already exists. Please use a different code.";
        } else {
            // Handle logo upload
            $logo = $office['logo']; // Keep existing logo by default
            
            if (isset($_FILES['office_logo']) && $_FILES['office_logo']['error'] == 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = $_FILES['office_logo']['type'];
                
                if (!in_array($file_type, $allowed_types)) {
                    $error = "Only JPG, PNG, GIF, and WEBP images are allowed.";
                } elseif ($_FILES['office_logo']['size'] > 2 * 1024 * 1024) { // 2MB limit
                    $error = "File size must be less than 2MB.";
                } else {
                    $target_dir = "uploads/offices/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    
                    // Delete old logo if exists
                    if (!empty($office['logo']) && file_exists($target_dir . $office['logo'])) {
                        unlink($target_dir . $office['logo']);
                    }
                    
                    $file_extension = pathinfo($_FILES['office_logo']['name'], PATHINFO_EXTENSION);
                    $logo = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $logo;
                    
                    if (!move_uploaded_file($_FILES['office_logo']['tmp_name'], $target_file)) {
                        $error = "Failed to upload office logo. Please try again.";
                    }
                }
            } elseif (isset($_POST['remove_logo']) && $_POST['remove_logo'] == '1') {
                // Remove logo
                if (!empty($office['logo'])) {
                    $target_dir = "uploads/offices/";
                    if (file_exists($target_dir . $office['logo'])) {
                        unlink($target_dir . $office['logo']);
                    }
                }
                $logo = null;
            }
            
            if (empty($error)) {
                $query = "UPDATE offices SET 
                         office_code = '$office_code',
                         office_name = '$office_name',
                         office_head = " . ($office_head ? "'$office_head'" : "NULL") . ",
                         location = " . ($location ? "'$location'" : "NULL") . ",
                         contact_number = " . ($contact_number ? "'$contact_number'" : "NULL") . ",
                         email = " . ($email ? "'$email'" : "NULL") . ",
                         status = '$status',
                         logo = " . ($logo ? "'$logo'" : "NULL") . "
                         WHERE id = $id";
                
                if ($db->query($query)) {
                    header("Location: offices.php?id=$id&success=" . urlencode("Office updated successfully"));
                    exit();
                } else {
                    $error = "Failed to update office. Please try again.";
                }
            }
        }
    }
}

include 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/edit_office.css">

<div class="edit-office-container">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fas fa-edit"></i> Edit Office</h2>
        <div class="header-actions">
            <a href="view_office.php?id=<?php echo $id; ?>" class="btn-secondary">
                <i class="fas fa-eye"></i> View Office
            </a>
            <a href="offices.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Offices
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data" class="office-form">
        <!-- Office Logo Section -->
        <div class="form-section">
            <h3><i class="fas fa-image"></i> Office Logo</h3>
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Current Logo</label>
                    <div class="logo-upload-container">
                        <div class="logo-preview" id="logoPreview">
                            <?php if (!empty($office['logo'])): ?>
                                <img src="uploads/offices/<?php echo $office['logo']; ?>" alt="Current Logo" class="preview-image">
                            <?php else: ?>
                                <div class="preview-placeholder">
                                    <i class="fas fa-building"></i>
                                    <span>No logo</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="upload-controls">
                            <input type="file" id="office_logo" name="office_logo" accept="image/*" onchange="previewLogo(this)">
                            <label for="office_logo" class="upload-btn">
                                <i class="fas fa-upload"></i> Choose New File
                            </label>
                            
                            <?php if (!empty($office['logo'])): ?>
                            <div class="checkbox-group">
                                <label>
                                    <input type="checkbox" name="remove_logo" value="1" onchange="toggleRemoveLogo(this)">
                                    Remove current logo
                                </label>
                            </div>
                            <?php endif; ?>
                            
                            <p class="help-text">Accepted formats: JPG, PNG, GIF, WEBP (Max 2MB)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Office Information -->
        <div class="form-section">
            <h3><i class="fas fa-info-circle"></i> Office Information</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Office Code <span class="required">*</span></label>
                    <input type="text" name="office_code" required 
                           value="<?php echo htmlspecialchars($office['office_code']); ?>"
                           placeholder="e.g., HR, IT, FIN" maxlength="20">
                    <small>Unique code for the office</small>
                </div>

                <div class="form-group">
                    <label>Office Name <span class="required">*</span></label>
                    <input type="text" name="office_name" required 
                           value="<?php echo htmlspecialchars($office['office_name']); ?>"
                           placeholder="e.g., Human Resources Department">
                </div>

                <div class="form-group">
                    <label>Office Head</label>
                    <input type="text" name="office_head" 
                           value="<?php echo htmlspecialchars($office['office_head'] ?? ''); ?>"
                           placeholder="e.g., Maria Santos">
                </div>

                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" 
                           value="<?php echo htmlspecialchars($office['location'] ?? ''); ?>"
                           placeholder="e.g., 2nd Floor Main Building">
                </div>

                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" 
                           value="<?php echo htmlspecialchars($office['contact_number'] ?? ''); ?>"
                           placeholder="e.g., 123-4567">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" 
                           value="<?php echo htmlspecialchars($office['email'] ?? ''); ?>"
                           placeholder="e.g., office@email.com">
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="active" <?php echo $office['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $office['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="submit" name="edit_office" class="btn-primary">
                <i class="fas fa-save"></i> Update Office
            </button>
            <a href="offices.php?id=<?php echo $id; ?>" class="btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<script>
function previewLogo(input) {
    const preview = document.getElementById('logoPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Logo Preview" class="preview-image">`;
        }
        reader.readAsDataURL(input.files[0]);
        
        // Uncheck remove logo if checked
        const removeCheckbox = document.querySelector('input[name="remove_logo"]');
        if (removeCheckbox) {
            removeCheckbox.checked = false;
        }
    }
}

function toggleRemoveLogo(checkbox) {
    if (checkbox.checked) {
        // Clear file input if any
        const fileInput = document.getElementById('office_logo');
        if (fileInput) {
            fileInput.value = '';
        }
        
        // Show placeholder
        const preview = document.getElementById('logoPreview');
        preview.innerHTML = `
            <div class="preview-placeholder">
                <i class="fas fa-building"></i>
                <span>Logo will be removed</span>
            </div>
        `;
    } else {
        // Restore original logo
        <?php if (!empty($office['logo'])): ?>
        const preview = document.getElementById('logoPreview');
        preview.innerHTML = `<img src="uploads/offices/<?php echo $office['logo']; ?>" alt="Current Logo" class="preview-image">`;
        <?php else: ?>
        const preview = document.getElementById('logoPreview');
        preview.innerHTML = `
            <div class="preview-placeholder">
                <i class="fas fa-building"></i>
                <span>No logo</span>
            </div>
        `;
        <?php endif; ?>
    }
}
</script>

<?php include 'includes/footer.php'; ?>