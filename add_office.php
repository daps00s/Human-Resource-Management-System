<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $office_code = trim($_POST['office_code'] ?? '');
    $office_name = trim($_POST['office_name'] ?? '');
    $office_head = trim($_POST['office_head'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Validate required fields
    if (empty($office_code) || empty($office_name)) {
        $error = "Office Code and Office Name are required fields.";
    } else {
        // Check if office code already exists
        $check = $db->query("SELECT id FROM offices WHERE office_code = '$office_code'");
        if ($check->num_rows > 0) {
            $error = "Office Code already exists. Please use a different code.";
        } else {
            // Handle logo upload
            $logo = '';
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
                    
                    $file_extension = pathinfo($_FILES['office_logo']['name'], PATHINFO_EXTENSION);
                    $logo = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $logo;
                    
                    if (!move_uploaded_file($_FILES['office_logo']['tmp_name'], $target_file)) {
                        $error = "Failed to upload office logo. Please try again.";
                    }
                }
            }
            
            if (empty($error)) {
                $query = "INSERT INTO offices (office_code, office_name, office_head, location, contact_number, email, logo) 
                         VALUES ('$office_code', '$office_name', " . ($office_head ? "'$office_head'" : "NULL") . ", 
                         " . ($location ? "'$location'" : "NULL") . ", " . ($contact_number ? "'$contact_number'" : "NULL") . ", 
                         " . ($email ? "'$email'" : "NULL") . ", " . ($logo ? "'$logo'" : "NULL") . ")";
                
                if ($db->query($query)) {
                    header("Location: offices.php?success=" . urlencode("Office added successfully"));
                    exit();
                } else {
                    $error = "Failed to add office. Please try again.";
                }
            }
        }
    }
}

include 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/add_office.css">

<div class="add-office-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h2><i class="fas fa-building"></i> Add New Office</h2>
            <p>Create a new office or department in the system</p>
        </div>
        <a href="offices.php" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Offices
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data" class="office-form">
        <!-- Office Logo Section -->
        <div class="form-section">
            <h3><i class="fas fa-image"></i> Office Logo</h3>
            <div class="logo-upload-container">
                <div class="logo-preview" id="logoPreview">
                    <div class="preview-placeholder">
                        <i class="fas fa-building"></i>
                        <span>No logo selected</span>
                    </div>
                </div>
                <div class="upload-controls">
                    <input type="file" id="office_logo" name="office_logo" accept="image/*" onchange="previewLogo(this)">
                    <label for="office_logo" class="upload-btn">
                        <i class="fas fa-upload"></i> Choose File
                    </label>
                    <p class="help-text">Accepted formats: JPG, PNG, GIF, WEBP (Max 2MB)</p>
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
                           value="<?php echo htmlspecialchars($_POST['office_code'] ?? ''); ?>"
                           placeholder="e.g., HR, IT, FIN" maxlength="20">
                    <small>Unique code for the office</small>
                </div>

                <div class="form-group">
                    <label>Office Name <span class="required">*</span></label>
                    <input type="text" name="office_name" required 
                           value="<?php echo htmlspecialchars($_POST['office_name'] ?? ''); ?>"
                           placeholder="e.g., Human Resources Department">
                </div>

                <div class="form-group">
                    <label>Office Head</label>
                    <input type="text" name="office_head" 
                           value="<?php echo htmlspecialchars($_POST['office_head'] ?? ''); ?>"
                           placeholder="e.g., Maria Santos">
                </div>

                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" 
                           value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                           placeholder="e.g., 2nd Floor Main Building">
                </div>

                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" 
                           value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ''); ?>"
                           placeholder="e.g., 123-4567">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           placeholder="e.g., office@email.com">
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="submit" name="add_office" class="btn-primary">
                <i class="fas fa-save"></i> Save Office
            </button>
            <a href="offices.php" class="btn-secondary">
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
    } else {
        preview.innerHTML = `
            <div class="preview-placeholder">
                <i class="fas fa-building"></i>
                <span>No logo selected</span>
            </div>
        `;
    }
}
</script>

<?php include 'includes/footer.php'; ?>