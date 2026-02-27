<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();
$error = '';
$success = '';

// Get employee ID from URL
$employee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($employee_id === 0) {
    header("Location: employee_list.php");
    exit();
}

// Fetch employee details
$employee = $db->query("SELECT * FROM users WHERE id = $employee_id")->fetch_assoc();

if (!$employee) {
    header("Location: employee_list.php");
    exit();
}

// Fetch employment details
$emp_employment = $db->query("
    SELECT ee.*, es.status_name 
    FROM employee_employment ee
    LEFT JOIN employment_status es ON ee.employment_status_id = es.id
    WHERE ee.user_id = $employee_id
")->fetch_assoc();

// Get form data for dropdowns
$offices = $db->query("SELECT * FROM offices WHERE status = 'active' ORDER BY office_name");
$statuses = $db->query("SELECT * FROM employment_status ORDER BY status_name");
$supervisors = $db->query("SELECT id, first_name, last_name FROM users WHERE role IN ('admin', 'hr_manager') AND id != $employee_id ORDER BY first_name");
$salary_grades = $db->query("SELECT sg.*, sg.salary_grade as grade_name FROM salary_grades sg ORDER BY sg.salary_grade, sg.step");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data - Personal Information
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $suffix = $_POST['suffix'] ?? '';
    $sex = $_POST['sex'] ?? '';
    $civil_status = $_POST['civil_status'] ?? 'Single';
    $birth_date = $_POST['birth_date'] ?? '';
    $address = $_POST['address'] ?? '';
    $contact_number = $_POST['contact_number'] ?? '';
    $employee_id_field = $_POST['employee_id'] ?? '';
    
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
    
    // Employment Status (Active/Retired/etc)
    $employment_status = $_POST['employment_status'] ?? 'Active';
    $date_of_separation = $_POST['date_of_separation'] ?? null;
    $reason_for_separation = $_POST['reason_for_separation'] ?? '';
    
    // Salary Information
    $salary_grade_id = $_POST['salary_grade_id'] ?? '';
    $step = $_POST['step'] ?? 1;
    $monthly_salary = $_POST['monthly_salary'] ?? 0;

    // Performance Rating
    $latest_performance_rating = $_POST['latest_performance_rating'] ?? null;
    $performance_period = $_POST['performance_period'] ?? '';
    $pending_admin_case = isset($_POST['pending_admin_case']) ? 1 : 0;

    // Validate
    if (empty($username) || empty($email) || empty($first_name) || empty($last_name)) {
        $error = "Please fill in all required fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if username/email exists for other users
        $check = $db->query("SELECT id FROM users WHERE (username = '$username' OR email = '$email') AND id != $employee_id");
        if ($check->num_rows > 0) {
            $error = "Username or email already exists for another user";
        } else {
            // Handle profile picture upload
            $profile_picture = $employee['profile_picture'];
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $target_dir = "uploads/profiles/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                // Delete old profile picture if exists
                if (!empty($profile_picture) && file_exists($target_dir . $profile_picture)) {
                    unlink($target_dir . $profile_picture);
                }
                
                $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $profile_picture = uniqid() . '.' . $file_extension;
                $target_file = $target_dir . $profile_picture;
                
                if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                    $error = "Failed to upload profile picture";
                }
            }
            
            if (empty($error)) {
                // Start transaction
                $db->begin_transaction();
                
                try {
                    // Calculate years in service
                    $years_in_gov_service = $employee['years_in_gov_service'];
                    $years_in_lgu_service = $employee['years_in_lgu_service'];
                    
                    if (!empty($first_day_gov_service) && $first_day_gov_service != $employee['first_day_gov_service']) {
                        $start = new DateTime($first_day_gov_service);
                        $now = new DateTime();
                        $years_in_gov_service = $now->diff($start)->y;
                    }
                    
                    if (!empty($date_hired) && $date_hired != ($emp_employment['date_hired'] ?? '')) {
                        $start_lgu = new DateTime($date_hired);
                        $now = new DateTime();
                        $years_in_lgu_service = $now->diff($start_lgu)->y;
                    }
                    
                    // Update user with all fields
                    $update_user = "UPDATE users SET 
                        username = '$username',
                        email = '$email',
                        first_name = '$first_name',
                        last_name = '$last_name',
                        middle_name = " . ($middle_name ? "'$middle_name'" : "NULL") . ",
                        suffix = " . ($suffix ? "'$suffix'" : "NULL") . ",
                        sex = " . ($sex ? "'$sex'" : "NULL") . ",
                        civil_status = '$civil_status',
                        birth_date = " . ($birth_date ? "'$birth_date'" : "NULL") . ",
                        address = " . ($address ? "'$address'" : "NULL") . ",
                        contact_number = " . ($contact_number ? "'$contact_number'" : "NULL") . ",
                        employee_id = " . ($employee_id_field ? "'$employee_id_field'" : "NULL") . ",
                        gsis_number = " . ($gsis_number ? "'$gsis_number'" : "NULL") . ",
                        pagibig_number = " . ($pagibig_number ? "'$pagibig_number'" : "NULL") . ",
                        philhealth_number = " . ($philhealth_number ? "'$philhealth_number'" : "NULL") . ",
                        tin_number = " . ($tin_number ? "'$tin_number'" : "NULL") . ",
                        profile_picture = " . ($profile_picture ? "'$profile_picture'" : "NULL") . ",
                        place_of_assignment = " . ($place_of_assignment ? "'$place_of_assignment'" : "NULL") . ",
                        first_day_gov_service = " . ($first_day_gov_service ? "'$first_day_gov_service'" : "NULL") . ",
                        continuous_service_start = " . ($continuous_service_start ? "'$continuous_service_start'" : "NULL") . ",
                        years_in_gov_service = $years_in_gov_service,
                        years_in_lgu_service = $years_in_lgu_service,
                        latest_performance_rating = " . ($latest_performance_rating ? "'$latest_performance_rating'" : "NULL") . ",
                        performance_period = " . ($performance_period ? "'$performance_period'" : "NULL") . ",
                        pending_admin_case = $pending_admin_case,
                        employment_status = '$employment_status',
                        date_of_separation = " . ($date_of_separation ? "'$date_of_separation'" : "NULL") . ",
                        reason_for_separation = " . ($reason_for_separation ? "'$reason_for_separation'" : "NULL") . "
                        WHERE id = $employee_id";
                    
                    $db->query($update_user);
                    
                    // Update or insert employment details
                    if ($emp_employment) {
                        // Update existing
                        $update_employment = "UPDATE employee_employment SET 
                            date_hired = " . ($date_hired ? "'$date_hired'" : "NULL") . ",
                            employment_status_id = " . ($employment_status_id ?: 'NULL') . ",
                            office_id = " . ($office_id ?: 'NULL') . ",
                            position = " . ($position ? "'$position'" : "NULL") . ",
                            supervisor_id = " . ($supervisor_id ?: 'NULL') . ",
                            probation_end_date = " . ($probation_end_date ? "'$probation_end_date'" : "NULL") . ",
                            salary_grade_id = " . ($salary_grade_id ?: 'NULL') . ",
                            step = $step,
                            monthly_salary = " . ($monthly_salary ?: 'NULL') . "
                            WHERE user_id = $employee_id";
                        $db->query($update_employment);
                    } else {
                        // Insert new
                        $insert_employment = "INSERT INTO employee_employment (
                            user_id, date_hired, employment_status_id, office_id, position, 
                            supervisor_id, probation_end_date, salary_grade_id, step, monthly_salary
                        ) VALUES (
                            $employee_id, 
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
                    }
                    
                    // Update service history - mark current as not current if position changed
                    if (!empty($position) && $position != ($emp_employment['position'] ?? '')) {
                        $db->query("UPDATE service_history SET is_current = 0, end_date = CURDATE() 
                                   WHERE user_id = $employee_id AND is_current = 1");
                        
                        // Add new service history entry
                        $insert_history = "INSERT INTO service_history (
                            user_id, position, office_id, salary_grade_id, step, start_date, is_current
                        ) VALUES (
                            $employee_id,
                            " . ($position ? "'$position'" : "NULL") . ",
                            " . ($office_id ?: 'NULL') . ",
                            " . ($salary_grade_id ?: 'NULL') . ",
                            $step,
                            CURDATE(),
                            1
                        )";
                        $db->query($insert_history);
                    }
                    
                    $db->commit();
                    $success = "Employee updated successfully";
                    
                    // Refresh employee data
                    $employee = $db->query("SELECT * FROM users WHERE id = $employee_id")->fetch_assoc();
                    $emp_employment = $db->query("SELECT ee.*, es.status_name FROM employee_employment ee
                                                LEFT JOIN employment_status es ON ee.employment_status_id = es.id
                                                WHERE ee.user_id = $employee_id")->fetch_assoc();
                    
                } catch (Exception $e) {
                    $db->rollback();
                    $error = "Error updating employee: " . $e->getMessage();
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
        <h2><i class="fas fa-edit"></i> Edit Employee</h2>
        <div class="header-actions">
            <a href="employee_profile.php?id=<?php echo $employee_id; ?>" class="btn-secondary">
                <i class="fas fa-eye"></i> View Profile
            </a>
            <a href="employee_list.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
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
                    <label>Current Profile Picture</label>
                    <div class="current-photo">
                        <?php if (!empty($employee['profile_picture'])): ?>
                            <img src="uploads/profiles/<?php echo $employee['profile_picture']; ?>" alt="Profile" style="max-width: 200px; max-height: 100px; border-radius: 50%;">
                        <?php else: ?>
                            <div class="photo-placeholder" style="width: 100px; height: 100px; background: #3498db; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 36px;">
                                <?php echo strtoupper(substr($employee['first_name'] ?? $employee['username'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Change Profile Picture</label>
                    <input type="file" name="profile_picture" accept="image/*">
                    <small>Leave empty to keep current picture</small>
                </div>
                <div class="form-group">
                    <label>Employee ID</label>
                    <input type="text" name="employee_id" value="<?php echo htmlspecialchars($employee['employee_id'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>First Name <span class="required">*</span></label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($employee['first_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Last Name <span class="required">*</span></label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($employee['last_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name" value="<?php echo htmlspecialchars($employee['middle_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Suffix</label>
                    <input type="text" name="suffix" value="<?php echo htmlspecialchars($employee['suffix'] ?? ''); ?>" placeholder="Jr., Sr., III">
                </div>
                <div class="form-group">
                    <label>Sex</label>
                    <select name="sex">
                        <option value="">Select Sex</option>
                        <option value="Male" <?php echo ($employee['sex'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($employee['sex'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Civil Status</label>
                    <select name="civil_status">
                        <option value="Single" <?php echo ($employee['civil_status'] ?? 'Single') == 'Single' ? 'selected' : ''; ?>>Single</option>
                        <option value="Married" <?php echo ($employee['civil_status'] ?? '') == 'Married' ? 'selected' : ''; ?>>Married</option>
                        <option value="Widowed" <?php echo ($employee['civil_status'] ?? '') == 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                        <option value="Separated" <?php echo ($employee['civil_status'] ?? '') == 'Separated' ? 'selected' : ''; ?>>Separated</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Birth Date</label>
                    <input type="date" name="birth_date" value="<?php echo htmlspecialchars($employee['birth_date'] ?? ''); ?>">
                </div>
                <div class="form-group full-width">
                    <label>Address</label>
                    <textarea name="address" rows="2"><?php echo htmlspecialchars($employee['address'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" value="<?php echo htmlspecialchars($employee['contact_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Username <span class="required">*</span></label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($employee['username']); ?>" required>
                </div>
            </div>
        </div>

        <!-- Government Numbers -->
        <div class="form-section">
            <h3><i class="fas fa-id-card"></i> Government Numbers</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>GSIS Number</label>
                    <input type="text" name="gsis_number" value="<?php echo htmlspecialchars($employee['gsis_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Pag-IBIG Number</label>
                    <input type="text" name="pagibig_number" value="<?php echo htmlspecialchars($employee['pagibig_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>PhilHealth Number</label>
                    <input type="text" name="philhealth_number" value="<?php echo htmlspecialchars($employee['philhealth_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>TIN Number</label>
                    <input type="text" name="tin_number" value="<?php echo htmlspecialchars($employee['tin_number'] ?? ''); ?>">
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
                        <option value="<?php echo $off['id']; ?>" <?php echo ($emp_employment['office_id'] ?? '') == $off['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($off['office_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Position</label>
                    <input type="text" name="position" value="<?php echo htmlspecialchars($emp_employment['position'] ?? $employee['position'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Date Hired</label>
                    <input type="date" name="date_hired" value="<?php echo htmlspecialchars($emp_employment['date_hired'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Employment Status</label>
                    <select name="employment_status_id">
                        <option value="">Select Status</option>
                        <?php while($stat = $statuses->fetch_assoc()): ?>
                        <option value="<?php echo $stat['id']; ?>" <?php echo ($emp_employment['employment_status_id'] ?? '') == $stat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($stat['status_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Appointment Status</label>
                    <select name="appointment_status">
                        <option value="Original" <?php echo ($employee['appointment_status'] ?? 'Original') == 'Original' ? 'selected' : ''; ?>>Original</option>
                        <option value="Promotional" <?php echo ($employee['appointment_status'] ?? '') == 'Promotional' ? 'selected' : ''; ?>>Promotional</option>
                        <option value="Temporary" <?php echo ($employee['appointment_status'] ?? '') == 'Temporary' ? 'selected' : ''; ?>>Temporary</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date of Original Appointment</label>
                    <input type="date" name="date_of_original_appointment" value="<?php echo htmlspecialchars($employee['date_of_original_appointment'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Date of Last Appointment</label>
                    <input type="date" name="date_of_last_appointment" value="<?php echo htmlspecialchars($employee['date_of_last_appointment'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Supervisor</label>
                    <select name="supervisor_id">
                        <option value="">Select Supervisor</option>
                        <?php while($sup = $supervisors->fetch_assoc()): ?>
                        <option value="<?php echo $sup['id']; ?>" <?php echo ($emp_employment['supervisor_id'] ?? '') == $sup['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sup['first_name'] . ' ' . $sup['last_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Place of Assignment</label>
                    <input type="text" name="place_of_assignment" value="<?php echo htmlspecialchars($employee['place_of_assignment'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Probation End Date</label>
                    <input type="date" name="probation_end_date" value="<?php echo htmlspecialchars($emp_employment['probation_end_date'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- Service History -->
        <div class="form-section">
            <h3><i class="fas fa-history"></i> Service History</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>First Day in Government Service</label>
                    <input type="date" name="first_day_gov_service" value="<?php echo htmlspecialchars($employee['first_day_gov_service'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Continuous Service Start Date</label>
                    <input type="date" name="continuous_service_start" value="<?php echo htmlspecialchars($employee['continuous_service_start'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- Performance & Status -->
        <div class="form-section">
            <h3><i class="fas fa-chart-line"></i> Performance & Status</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Latest Performance Rating</label>
                    <input type="number" name="latest_performance_rating" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($employee['latest_performance_rating'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Performance Period</label>
                    <input type="text" name="performance_period" value="<?php echo htmlspecialchars($employee['performance_period'] ?? ''); ?>" placeholder="e.g., 2025-2026">
                </div>
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" name="pending_admin_case" value="1" <?php echo ($employee['pending_admin_case'] ?? 0) ? 'checked' : ''; ?>>
                        Has Pending Administrative Case
                    </label>
                </div>
            </div>
        </div>

        <!-- Employment Status -->
        <div class="form-section">
            <h3><i class="fas fa-user-tag"></i> Employment Status</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Current Status</label>
                    <select name="employment_status">
                        <option value="Active" <?php echo ($employee['employment_status'] ?? 'Active') == 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Retired" <?php echo ($employee['employment_status'] ?? '') == 'Retired' ? 'selected' : ''; ?>>Retired</option>
                        <option value="Resigned" <?php echo ($employee['employment_status'] ?? '') == 'Resigned' ? 'selected' : ''; ?>>Resigned</option>
                        <option value="Deceased" <?php echo ($employee['employment_status'] ?? '') == 'Deceased' ? 'selected' : ''; ?>>Deceased</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date of Separation</label>
                    <input type="date" name="date_of_separation" value="<?php echo htmlspecialchars($employee['date_of_separation'] ?? ''); ?>">
                </div>
                <div class="form-group full-width">
                    <label>Reason for Separation</label>
                    <textarea name="reason_for_separation" rows="2"><?php echo htmlspecialchars($employee['reason_for_separation'] ?? ''); ?></textarea>
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
                                <?php echo ($emp_employment['salary_grade_id'] ?? '') == $sg['id'] ? 'selected' : ''; ?>>
                            <?php echo $sg['salary_grade'] . ' - Step ' . $sg['step'] . ' (₱' . number_format($sg['monthly_salary'], 2) . ')'; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Monthly Salary</label>
                    <input type="number" name="monthly_salary" id="monthly_salary" step="0.01" 
                           value="<?php echo htmlspecialchars($emp_employment['monthly_salary'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Update Employee</button>
            <a href="employee_list.php?id=<?php echo $employee_id; ?>" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
document.getElementById('salary_grade').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    if (selected.value && !document.getElementById('monthly_salary').value) {
        document.getElementById('monthly_salary').value = selected.getAttribute('data-amount');
    }
});

// Toggle separation fields based on employment status
document.querySelector('select[name="employment_status"]').addEventListener('change', function() {
    var separationFields = document.querySelectorAll('input[name="date_of_separation"], textarea[name="reason_for_separation"]');
    if (this.value === 'Active') {
        separationFields.forEach(field => {
            field.value = '';
            field.disabled = true;
        });
    } else {
        separationFields.forEach(field => {
            field.disabled = false;
        });
    }
});

// Trigger on page load
window.addEventListener('load', function() {
    var status = document.querySelector('select[name="employment_status"]').value;
    var separationFields = document.querySelectorAll('input[name="date_of_separation"], textarea[name="reason_for_separation"]');
    if (status === 'Active') {
        separationFields.forEach(field => {
            field.disabled = true;
        });
    }
});
</script>
