<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();
$error = '';
$success = '';

// Get form data
$offices = $db->query("SELECT * FROM offices WHERE status = 'active' ORDER BY office_name");
$statuses = $db->query("SELECT * FROM employment_status ORDER BY status_name");
$supervisors = $db->query("SELECT id, first_name, last_name FROM users WHERE role IN ('admin', 'hr_manager') ORDER BY first_name");
$salary_grades = $db->query("SELECT sg.*, sg.salary_grade as grade_name FROM salary_grades sg ORDER BY sg.salary_grade, sg.step");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data - Personal Information
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $suffix = $_POST['suffix'] ?? '';
    $sex = $_POST['sex'] ?? '';
    $civil_status = $_POST['civil_status'] ?? 'Single';
    $birth_date = $_POST['birth_date'] ?? '';
    $address = $_POST['address'] ?? '';
    $contact_number = $_POST['contact_number'] ?? '';
    $employee_id = $_POST['employee_id'] ?? '';
    
    // Government Numbers
    $gsis_number = $_POST['gsis_number'] ?? '';
    $pagibig_number = $_POST['pagibig_number'] ?? '';
    $philhealth_number = $_POST['philhealth_number'] ?? '';
    $tin_number = $_POST['tin_number'] ?? '';
    
    // Employment Details
    $office_id = $_POST['office_id'] ?? '';
    $position = $_POST['position'] ?? '';
    $date_hired = $_POST['date_hired'] ?? '';
    $employment_status_id = $_POST['employment_status_id'] ?? '';
    $supervisor_id = $_POST['supervisor_id'] ?? '';
    $probation_end_date = $_POST['probation_end_date'] ?? null;
    $appointment_status = $_POST['appointment_status'] ?? 'Original';
    $date_of_original_appointment = $_POST['date_of_original_appointment'] ?? null;
    $date_of_last_appointment = $_POST['date_of_last_appointment'] ?? null;
    $place_of_assignment = $_POST['place_of_assignment'] ?? '';
    
    // Service History
    $first_day_gov_service = $_POST['first_day_gov_service'] ?? null;
    $continuous_service_start = $_POST['continuous_service_start'] ?? null;
    
    // Salary Information
    $salary_grade_id = $_POST['salary_grade_id'] ?? '';
    $step = $_POST['step'] ?? 1;
    $monthly_salary = $_POST['monthly_salary'] ?? 0;

    // Validate
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error = "Please fill in all required fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        // Check if username/email exists
        $check = $db->query("SELECT id FROM users WHERE username = '$username' OR email = '$email'");
        if ($check->num_rows > 0) {
            $error = "Username or email already exists";
        } else {
            // Handle profile picture upload
            $profile_picture = '';
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $target_dir = "uploads/profiles/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $profile_picture = uniqid() . '.' . $file_extension;
                $target_file = $target_dir . $profile_picture;
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                    // File uploaded successfully
                } else {
                    $error = "Failed to upload profile picture";
                }
            }
            
            if (empty($error)) {
                // Start transaction
                $db->begin_transaction();
                
                try {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
                    
                    // Insert user with all new fields
                    $insert_user = "INSERT INTO users (
                        username, email, password, first_name, last_name, middle_name, suffix,
                        sex, civil_status, birth_date, address, contact_number, employee_id,
                        gsis_number, pagibig_number, philhealth_number, tin_number,
                        profile_picture, place_of_assignment, first_day_gov_service,
                        continuous_service_start, role, employment_status
                    ) VALUES (
                        '$username', '$email', '$hashed_password', '$first_name', '$last_name', 
                        " . ($middle_name ? "'$middle_name'" : "NULL") . ",
                        " . ($suffix ? "'$suffix'" : "NULL") . ",
                        " . ($sex ? "'$sex'" : "NULL") . ",
                        '$civil_status',
                        " . ($birth_date ? "'$birth_date'" : "NULL") . ",
                        " . ($address ? "'$address'" : "NULL") . ",
                        " . ($contact_number ? "'$contact_number'" : "NULL") . ",
                        " . ($employee_id ? "'$employee_id'" : "NULL") . ",
                        " . ($gsis_number ? "'$gsis_number'" : "NULL") . ",
                        " . ($pagibig_number ? "'$pagibig_number'" : "NULL") . ",
                        " . ($philhealth_number ? "'$philhealth_number'" : "NULL") . ",
                        " . ($tin_number ? "'$tin_number'" : "NULL") . ",
                        " . ($profile_picture ? "'$profile_picture'" : "NULL") . ",
                        " . ($place_of_assignment ? "'$place_of_assignment'" : "NULL") . ",
                        " . ($first_day_gov_service ? "'$first_day_gov_service'" : "NULL") . ",
                        " . ($continuous_service_start ? "'$continuous_service_start'" : "NULL") . ",
                        'employee', 'Active'
                    )";
                    
                    $db->query($insert_user);
                    $user_id = $db->insert_id;
                    
                    // Calculate years in service
                    $years_in_gov_service = 0;
                    $years_in_lgu_service = 0;
                    
                    if (!empty($first_day_gov_service)) {
                        $start = new DateTime($first_day_gov_service);
                        $now = new DateTime();
                        $years_in_gov_service = $now->diff($start)->y;
                    }
                    
                    if (!empty($date_hired)) {
                        $start_lgu = new DateTime($date_hired);
                        $now = new DateTime();
                        $years_in_lgu_service = $now->diff($start_lgu)->y;
                    }
                    
                    // Update user with calculated years
                    $db->query("UPDATE users SET 
                        years_in_gov_service = $years_in_gov_service,
                        years_in_lgu_service = $years_in_lgu_service
                        WHERE id = $user_id");
                    
                    // Insert employment details
                    $insert_employment = "INSERT INTO employee_employment (
                        user_id, date_hired, employment_status_id, office_id, position, 
                        supervisor_id, probation_end_date, salary_grade_id, step, monthly_salary
                    ) VALUES (
                        $user_id, 
                        " . ($date_hired ? "'$date_hired'" : "NULL") . ",
                        " . ($employment_status_id ?: 'NULL') . ",
                        " . ($office_id ?: 'NULL') . ",
                        " . ($position ? "'$position'" : "NULL") . ",
                        " . ($supervisor_id ?: 'NULL') . ",
                        " . ($probation_end_date ? "'$probation_end_date'" : "NULL") . ",
                        " . ($salary_grade_id ?: 'NULL') . ",
                        $step,
                        " . ($monthly_salary ?: 'NULL') . "
                    )";
                    $db->query($insert_employment);
                    
                    // Add to service history
                    if (!empty($date_hired) && !empty($position)) {
                        $insert_history = "INSERT INTO service_history (
                            user_id, position, office_id, salary_grade_id, step, start_date, is_current
                        ) VALUES (
                            $user_id,
                            " . ($position ? "'$position'" : "NULL") . ",
                            " . ($office_id ?: 'NULL') . ",
                            " . ($salary_grade_id ?: 'NULL') . ",
                            $step,
                            " . ($date_hired ? "'$date_hired'" : "NULL") . ",
                            1
                        )";
                        $db->query($insert_history);
                    }
                    
                    $db->commit();
                    $success = "Employee added successfully";
                    
                    // Clear form
                    $_POST = array();
                    
                } catch (Exception $e) {
                    $db->rollback();
                    $error = "Error adding employee: " . $e->getMessage();
                }
            }
        }
    }
}

include 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/add_employee.css">

<div class="add-employee-container">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fas fa-user-plus"></i> Add New Employee</h2>
        <a href="employee_list.php" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data" class="employee-form">
        <!-- Personal Information -->
        <div class="form-section">
            <h3><i class="fas fa-user"></i> Personal Information</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Profile Picture</label>
                    <input type="file" name="profile_picture" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Employee ID</label>
                    <input type="text" name="employee_id" value="<?php echo htmlspecialchars($_POST['employee_id'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>First Name <span class="required">*</span></label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name <span class="required">*</span></label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Suffix</label>
                    <input type="text" name="suffix" value="<?php echo htmlspecialchars($_POST['suffix'] ?? ''); ?>" placeholder="Jr., Sr., III">
                </div>
                <div class="form-group">
                    <label>Sex</label>
                    <select name="sex">
                        <option value="">Select Sex</option>
                        <option value="Male" <?php echo ($_POST['sex'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($_POST['sex'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Civil Status</label>
                    <select name="civil_status">
                        <option value="Single">Single</option>
                        <option value="Married" <?php echo ($_POST['civil_status'] ?? '') == 'Married' ? 'selected' : ''; ?>>Married</option>
                        <option value="Widowed" <?php echo ($_POST['civil_status'] ?? '') == 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                        <option value="Separated" <?php echo ($_POST['civil_status'] ?? '') == 'Separated' ? 'selected' : ''; ?>>Separated</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Birth Date</label>
                    <input type="date" name="birth_date" value="<?php echo htmlspecialchars($_POST['birth_date'] ?? ''); ?>">
                </div>
                <div class="form-group full-width">
                    <label>Address</label>
                    <textarea name="address" rows="2"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Username <span class="required">*</span></label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Password <span class="required">*</span></label>
                    <input type="password" name="password" required>
                    <small>Minimum 6 characters</small>
                </div>
            </div>
        </div>

        <!-- Government Numbers -->
        <div class="form-section">
            <h3><i class="fas fa-id-card"></i> Government Numbers</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>GSIS Number</label>
                    <input type="text" name="gsis_number" value="<?php echo htmlspecialchars($_POST['gsis_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Pag-IBIG Number</label>
                    <input type="text" name="pagibig_number" value="<?php echo htmlspecialchars($_POST['pagibig_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>PhilHealth Number</label>
                    <input type="text" name="philhealth_number" value="<?php echo htmlspecialchars($_POST['philhealth_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>TIN Number</label>
                    <input type="text" name="tin_number" value="<?php echo htmlspecialchars($_POST['tin_number'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- Employment Details -->
        <div class="form-section">
            <h3><i class="fas fa-briefcase"></i> Employment Details</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Office/Department</label>
                    <select name="office_id">
                        <option value="">Select Office</option>
                        <?php while($off = $offices->fetch_assoc()): ?>
                        <option value="<?php echo $off['id']; ?>" <?php echo ($_POST['office_id'] ?? '') == $off['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($off['office_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Position</label>
                    <input type="text" name="position" value="<?php echo htmlspecialchars($_POST['position'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Date Hired</label>
                    <input type="date" name="date_hired" value="<?php echo htmlspecialchars($_POST['date_hired'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Employment Status</label>
                    <select name="employment_status_id">
                        <option value="">Select Status</option>
                        <?php while($stat = $statuses->fetch_assoc()): ?>
                        <option value="<?php echo $stat['id']; ?>" <?php echo ($_POST['employment_status_id'] ?? '') == $stat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($stat['status_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Appointment Status</label>
                    <select name="appointment_status">
                        <option value="Original">Original</option>
                        <option value="Promotional" <?php echo ($_POST['appointment_status'] ?? '') == 'Promotional' ? 'selected' : ''; ?>>Promotional</option>
                        <option value="Temporary" <?php echo ($_POST['appointment_status'] ?? '') == 'Temporary' ? 'selected' : ''; ?>>Temporary</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date of Original Appointment</label>
                    <input type="date" name="date_of_original_appointment" value="<?php echo htmlspecialchars($_POST['date_of_original_appointment'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Date of Last Appointment</label>
                    <input type="date" name="date_of_last_appointment" value="<?php echo htmlspecialchars($_POST['date_of_last_appointment'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Supervisor</label>
                    <select name="supervisor_id">
                        <option value="">Select Supervisor</option>
                        <?php while($sup = $supervisors->fetch_assoc()): ?>
                        <option value="<?php echo $sup['id']; ?>" <?php echo ($_POST['supervisor_id'] ?? '') == $sup['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sup['first_name'] . ' ' . $sup['last_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Place of Assignment</label>
                    <input type="text" name="place_of_assignment" value="<?php echo htmlspecialchars($_POST['place_of_assignment'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Probation End Date</label>
                    <input type="date" name="probation_end_date" value="<?php echo htmlspecialchars($_POST['probation_end_date'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- Service History -->
        <div class="form-section">
            <h3><i class="fas fa-history"></i> Service History</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>First Day in Government Service</label>
                    <input type="date" name="first_day_gov_service" value="<?php echo htmlspecialchars($_POST['first_day_gov_service'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Continuous Service Start Date</label>
                    <input type="date" name="continuous_service_start" value="<?php echo htmlspecialchars($_POST['continuous_service_start'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- Salary Information -->
        <div class="form-section">
            <h3><i class="fas fa-money-bill"></i> Salary Information</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Salary Grade</label>
                    <select name="salary_grade_id" id="salary_grade">
                        <option value="">Select Grade</option>
                        <?php 
                        $salary_grades->data_seek(0);
                        while($sg = $salary_grades->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $sg['id']; ?>" 
                                data-step="<?php echo $sg['step']; ?>"
                                data-amount="<?php echo $sg['monthly_salary']; ?>"
                                <?php echo ($_POST['salary_grade_id'] ?? '') == $sg['id'] ? 'selected' : ''; ?>>
                            <?php echo $sg['salary_grade'] . ' - Step ' . $sg['step'] . ' (₱' . number_format($sg['monthly_salary'], 2) . ')'; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Monthly Salary</label>
                    <input type="number" name="monthly_salary" id="monthly_salary" step="0.01" 
                           value="<?php echo htmlspecialchars($_POST['monthly_salary'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Save Employee</button>
            <a href="employee_list.php" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
document.getElementById('salary_grade').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    if (selected.value) {
        document.getElementById('monthly_salary').value = selected.getAttribute('data-amount');
    }
});
</script>