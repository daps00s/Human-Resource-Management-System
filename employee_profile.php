<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

$user = $auth->getCurrentUser();
$fullName = $auth->getUserFullName();

// Get employee ID from URL or use current user
$employee_id = isset($_GET['id']) ? (int)$_GET['id'] : $user['id'];

$db = getDB();

// Fetch employee details with all new fields
$employee = $db->query("SELECT * FROM users WHERE id = $employee_id")->fetch_assoc();

if (!$employee) {
    header("Location: employee_list.php");
    exit();
}

// Fetch employment details from database
$emp_details_query = "
    SELECT 
        ee.*,
        es.status_name as employment_status,
        o.office_name as department,
        o.location as branch,
        CONCAT(sup.first_name, ' ', sup.last_name) as supervisor_name,
        sg.salary_grade,
        sg.step as salary_step,
        sg.monthly_salary as grade_salary
    FROM employee_employment ee
    LEFT JOIN employment_status es ON ee.employment_status_id = es.id
    LEFT JOIN offices o ON ee.office_id = o.id
    LEFT JOIN users sup ON ee.supervisor_id = sup.id
    LEFT JOIN salary_grades sg ON ee.salary_grade_id = sg.id
    WHERE ee.user_id = $employee_id
";

$emp_details_result = $db->query($emp_details_query);

if ($emp_details_result && $emp_details_result->num_rows > 0) {
    $emp = $emp_details_result->fetch_assoc();
    
    $employment_details = [
        'employee_id' => $employee['employee_id'] ?? 'N/A',
        'date_hired' => $emp['date_hired'] ?? '',
        'employment_status' => $emp['employment_status'] ?? 'Regular',
        'position' => $emp['position'] ?? $employee['position'] ?? 'N/A',
        'department' => $emp['department'] ?? $employee['department'] ?? 'N/A',
        'branch' => $emp['branch'] ?? 'Main Office',
        'supervisor' => $emp['supervisor_name'] ?? 'N/A',
        'probation_end_date' => $emp['probation_end_date'] ?? '',
        'regularization_date' => $emp['regularization_date'] ?? ''
    ];
    
    // Calculate years of service
    if (!empty($emp['date_hired'])) {
        $hired = new DateTime($emp['date_hired']);
        $now = new DateTime();
        $years_of_service = $now->diff($hired)->y;
    } else {
        $years_of_service = 0;
    }
    
    // Salary information
    $monthly_salary = $emp['monthly_salary'] ?? $emp['grade_salary'] ?? 0;
    $salary_info = [
        'salary_grade' => $emp['salary_grade'] ?? 'N/A',
        'step' => 'Step ' . ($emp['salary_step'] ?? '1'),
        'monthly_salary' => '₱' . number_format($monthly_salary, 2),
        'annual_salary' => '₱' . number_format($monthly_salary * 12, 2),
        'last_increment_date' => '',
        'next_increment_date' => '',
        'increment_amount' => '₱0.00'
    ];
} else {
    // Fallback to default values if no employment record found
    $employment_details = [
        'employee_id' => $employee['employee_id'] ?? 'N/A',
        'date_hired' => '',
        'employment_status' => 'Regular',
        'position' => $employee['position'] ?? 'N/A',
        'department' => $employee['department'] ?? 'N/A',
        'branch' => 'Main Office',
        'supervisor' => 'N/A',
        'probation_end_date' => '',
        'regularization_date' => ''
    ];
    
    $years_of_service = 0;
    $salary_info = [
        'salary_grade' => 'N/A',
        'step' => 'Step 1',
        'monthly_salary' => '₱0.00',
        'annual_salary' => '₱0.00',
        'last_increment_date' => '',
        'next_increment_date' => '',
        'increment_amount' => '₱0.00'
    ];
}

// Fetch salary history
$salary_history = [];
$salary_history_result = $db->query("
    SELECT 
        sh.*,
        old.salary_grade as old_grade,
        old.step as old_step,
        old.monthly_salary as old_amount,
        new.salary_grade as new_grade,
        new.step as new_step,
        new.monthly_salary as new_amount
    FROM salary_history sh
    LEFT JOIN salary_grades old ON sh.old_salary_grade_id = old.id
    LEFT JOIN salary_grades new ON sh.new_salary_grade_id = new.id
    WHERE sh.user_id = $employee_id
    ORDER BY sh.adjustment_date DESC
");

if ($salary_history_result && $salary_history_result->num_rows > 0) {
    while ($row = $salary_history_result->fetch_assoc()) {
        $salary_history[] = [
            'date' => $row['adjustment_date'],
            'type' => ucfirst(str_replace('_', ' ', $row['adjustment_type'])),
            'amount' => '+₱' . number_format(($row['new_amount'] - $row['old_amount']), 2),
            'old_salary' => '₱' . number_format($row['old_amount'], 2),
            'new_salary' => '₱' . number_format($row['new_amount'], 2)
        ];
    }
}

// Fetch retirement information
$retirement_info = [];

// Get birth date from employee
if (!empty($employee['birth_date'])) {
    $birth_date = new DateTime($employee['birth_date']);
    $today = new DateTime();
    $age = $today->diff($birth_date)->y;
    $retirement_age = 65;
    $retirement_date = clone $birth_date;
    $retirement_date->modify('+65 years');
    $years_to_retirement = max(0, $retirement_age - $age);
    
    // Calculate years of service at retirement
    $hired_date = !empty($employment_details['date_hired']) ? new DateTime($employment_details['date_hired']) : null;
    $years_at_retirement = $hired_date ? $retirement_date->diff($hired_date)->y : 0;
    
    // Estimate pension (sample calculation - 80% of last salary)
    $monthly_salary_num = floatval(str_replace(['₱', ','], '', $salary_info['monthly_salary']));
    $estimated_pension = $monthly_salary_num * 0.8;
    
    $retirement_info = [
        'birth_date' => $employee['birth_date'],
        'age' => $age,
        'retirement_age' => $retirement_age,
        'years_to_retirement' => $years_to_retirement,
        'retirement_date' => $retirement_date->format('Y-m-d'),
        'years_of_service' => $years_of_service,
        'total_years_at_retirement' => $years_at_retirement,
        'estimated_pension' => '₱' . number_format($estimated_pension, 2)
    ];
}

// Government numbers
$retirement_info['gsis_number'] = $employee['gsis_number'] ?? 'Not Provided';
$retirement_info['pagibig_number'] = $employee['pagibig_number'] ?? 'Not Provided';
$retirement_info['philhealth_number'] = $employee['philhealth_number'] ?? 'Not Provided';
$retirement_info['tin_number'] = $employee['tin_number'] ?? 'Not Provided';

// Fetch loyalty awards
$loyalty_awards = [];
$awards_result = $db->query("
    SELECT ea.*, la.award_name, la.cash_amount, la.benefits
    FROM employee_awards ea
    JOIN loyalty_awards la ON ea.award_id = la.id
    WHERE ea.user_id = $employee_id
    ORDER BY ea.date_received DESC
");

if ($awards_result && $awards_result->num_rows > 0) {
    while ($row = $awards_result->fetch_assoc()) {
        $loyalty_awards[] = [
            'year' => date('Y', strtotime($row['date_received'])),
            'award' => $row['award_name'],
            'description' => $row['remarks'] ?? $row['award_name'] . ' - ' . $row['years_of_service'] . ' years',
            'date_received' => $row['date_received']
        ];
    }
}

// Fetch service history
$service_history = [];
$history_result = $db->query("
    SELECT 
        sh.*,
        o.office_name as department,
        sg.salary_grade
    FROM service_history sh
    LEFT JOIN offices o ON sh.office_id = o.id
    LEFT JOIN salary_grades sg ON sh.salary_grade_id = sg.id
    WHERE sh.user_id = $employee_id
    ORDER BY sh.start_date DESC
");

if ($history_result && $history_result->num_rows > 0) {
    while ($row = $history_result->fetch_assoc()) {
        $start_year = date('Y', strtotime($row['start_date']));
        $end_year = $row['is_current'] ? 'Present' : (isset($row['end_date']) ? date('Y', strtotime($row['end_date'])) : '');
        
        $service_history[] = [
            'period' => $start_year . ' - ' . $end_year,
            'position' => $row['position'],
            'department' => $row['department'] ?? 'N/A',
            'salary_grade' => $row['salary_grade'] ?? 'N/A',
            'status' => $row['is_current'] ? 'Current' : 'Previous'
        ];
    }
}

// Include header
include 'includes/header.php';

?>
<link rel="stylesheet" href="assets/css/employee_profile.css">
<div class="profile-container">
    <!-- Profile Header -->
    <div class="profile-header-card">
        <div class="profile-cover">
            <div class="profile-avatar-large">
                <?php if (!empty($employee['profile_picture'])): ?>
                    <img src="uploads/profiles/<?php echo $employee['profile_picture']; ?>" alt="Profile" class="profile-image">
                <?php else: ?>
                    <?php 
                    $name_initial = '';
                    if (!empty($employee['first_name'])) {
                        $name_initial = strtoupper(substr($employee['first_name'], 0, 1));
                    } elseif (!empty($employee['last_name'])) {
                        $name_initial = strtoupper(substr($employee['last_name'], 0, 1));
                    } else {
                        $name_initial = strtoupper(substr($employee['username'], 0, 1));
                    }
                    echo $name_initial;
                    ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="profile-info-header">
            <h1>
                <?php 
                $full_name = trim($employee['first_name'] . ' ' . ($employee['middle_name'] ?? '') . ' ' . $employee['last_name'] . ' ' . ($employee['suffix'] ?? ''));
                echo htmlspecialchars($full_name ?: $employee['username']); 
                ?>
            </h1>
            <p class="profile-title"><?php echo htmlspecialchars($employment_details['position']); ?></p>
            <p class="profile-dept"><?php echo htmlspecialchars($employment_details['department']); ?></p>
            <div class="profile-badges">
                <span class="badge badge-primary">Employee ID: <?php echo htmlspecialchars($employment_details['employee_id']); ?></span>
                <span class="badge badge-success"><?php echo $employment_details['employment_status']; ?></span>
                <span class="badge badge-info"><?php echo $salary_info['salary_grade'] . ' ' . $salary_info['step']; ?></span>
                <span class="badge <?php echo ($employee['employment_status'] ?? 'Active') == 'Active' ? 'badge-success' : 'badge-secondary'; ?>">
                    <?php echo $employee['employment_status'] ?? 'Active'; ?>
                </span>
            </div>
        </div>
        <div class="profile-actions">
            <a href="edit_employee.php?id=<?php echo $employee_id; ?>" class="btn-primary">
                <i class="fas fa-edit"></i> Edit Profile
            </a>
            <button class="btn-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <!-- Personal Information Summary -->
    <div class="personal-info-card">
        <div class="info-grid-compact">
            <div class="info-item">
                <span class="info-icon"><i class="fas fa-venus-mars"></i></span>
                <div>
                    <small>Sex</small>
                    <strong><?php echo $employee['sex'] ?? 'Not Specified'; ?></strong>
                </div>
            </div>
            <div class="info-item">
                <span class="info-icon"><i class="fas fa-heart"></i></span>
                <div>
                    <small>Civil Status</small>
                    <strong><?php echo $employee['civil_status'] ?? 'Single'; ?></strong>
                </div>
            </div>
            <div class="info-item">
                <span class="info-icon"><i class="fas fa-calendar"></i></span>
                <div>
                    <small>Birth Date</small>
                    <strong><?php echo !empty($employee['birth_date']) ? date('F d, Y', strtotime($employee['birth_date'])) : 'Not Specified'; ?></strong>
                </div>
            </div>
            <div class="info-item">
                <span class="info-icon"><i class="fas fa-phone"></i></span>
                <div>
                    <small>Contact</small>
                    <strong><?php echo $employee['contact_number'] ?? 'Not Provided'; ?></strong>
                </div>
            </div>
            <div class="info-item full-width">
                <span class="info-icon"><i class="fas fa-map-marker-alt"></i></span>
                <div>
                    <small>Address</small>
                    <strong><?php echo $employee['address'] ?? 'Not Provided'; ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Tabs -->
    <div class="profile-tabs">
        <div class="tab-nav">
            <button class="tab-link active" data-tab="employment">Employment Details</button>
            <button class="tab-link" data-tab="gov-numbers">Government Numbers</button>
            <button class="tab-link" data-tab="salary">Salary & Increment</button>
            <button class="tab-link" data-tab="retirement">Retirement Info</button>
            <button class="tab-link" data-tab="awards">Loyalty Awards</button>
            <button class="tab-link" data-tab="history">Service History</button>
            <button class="tab-link" data-tab="performance">Performance</button>
        </div>

        <!-- Employment Details Tab -->
        <div class="tab-pane active" id="employment">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-briefcase"></i> Employment Details</h3>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-row">
                            <div class="info-label">Employee ID</div>
                            <div class="info-value"><?php echo $employment_details['employee_id']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Date Hired</div>
                            <div class="info-value"><?php echo !empty($employment_details['date_hired']) ? date('F d, Y', strtotime($employment_details['date_hired'])) : 'N/A'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Employment Status</div>
                            <div class="info-value"><span class="status-badge status-regular"><?php echo $employment_details['employment_status']; ?></span></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Appointment Status</div>
                            <div class="info-value"><?php echo $employee['appointment_status'] ?? 'Original'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Position</div>
                            <div class="info-value"><?php echo $employment_details['position']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Department/Office</div>
                            <div class="info-value"><?php echo $employment_details['department']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Place of Assignment</div>
                            <div class="info-value"><?php echo $employee['place_of_assignment'] ?? $employment_details['branch']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Supervisor</div>
                            <div class="info-value"><?php echo $employment_details['supervisor']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Date of Original Appointment</div>
                            <div class="info-value"><?php echo !empty($employee['date_of_original_appointment']) ? date('F d, Y', strtotime($employee['date_of_original_appointment'])) : 'N/A'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Date of Last Appointment</div>
                            <div class="info-value"><?php echo !empty($employee['date_of_last_appointment']) ? date('F d, Y', strtotime($employee['date_of_last_appointment'])) : 'N/A'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Probation End Date</div>
                            <div class="info-value"><?php echo !empty($employment_details['probation_end_date']) ? date('F d, Y', strtotime($employment_details['probation_end_date'])) : 'N/A'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Regularization Date</div>
                            <div class="info-value"><?php echo !empty($employment_details['regularization_date']) ? date('F d, Y', strtotime($employment_details['regularization_date'])) : 'N/A'; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Government Numbers Tab -->
        <div class="tab-pane" id="gov-numbers">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-id-card"></i> Government Issued Numbers</h3>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-row">
                            <div class="info-label">GSIS Number</div>
                            <div class="info-value"><?php echo $retirement_info['gsis_number']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Pag-IBIG Number</div>
                            <div class="info-value"><?php echo $retirement_info['pagibig_number']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">PhilHealth Number</div>
                            <div class="info-value"><?php echo $retirement_info['philhealth_number']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">TIN Number</div>
                            <div class="info-value"><?php echo $retirement_info['tin_number']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Salary & Increment Tab -->
        <div class="tab-pane" id="salary">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-money-bill-wave"></i> Current Salary Information</h3>
                </div>
                <div class="card-body">
                    <div class="salary-summary">
                        <div class="salary-card">
                            <div class="salary-label">Salary Grade</div>
                            <div class="salary-value"><?php echo $salary_info['salary_grade']; ?></div>
                        </div>
                        <div class="salary-card">
                            <div class="salary-label">Step</div>
                            <div class="salary-value"><?php echo $salary_info['step']; ?></div>
                        </div>
                        <div class="salary-card">
                            <div class="salary-label">Monthly Salary</div>
                            <div class="salary-value highlight"><?php echo $salary_info['monthly_salary']; ?></div>
                        </div>
                        <div class="salary-card">
                            <div class="salary-label">Annual Salary</div>
                            <div class="salary-value"><?php echo $salary_info['annual_salary']; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($salary_history)): ?>
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Salary & Increment History</h3>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Old Salary</th>
                                <th>New Salary</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salary_history as $adj): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($adj['date'])); ?></td>
                                <td><?php echo $adj['type']; ?></td>
                                <td class="text-success"><?php echo $adj['amount']; ?></td>
                                <td><?php echo $adj['old_salary']; ?></td>
                                <td><?php echo $adj['new_salary']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Retirement Info Tab -->
        <div class="tab-pane" id="retirement">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-clock"></i> Retirement Information</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($retirement_info)): ?>
                    <div class="retirement-summary">
                        <div class="retirement-card">
                            <div class="retirement-icon">
                                <i class="fas fa-birthday-cake"></i>
                            </div>
                            <div class="retirement-content">
                                <div class="retirement-label">Birth Date</div>
                                <div class="retirement-value"><?php echo date('F d, Y', strtotime($retirement_info['birth_date'])); ?></div>
                                <div class="retirement-sub">Age: <?php echo $retirement_info['age']; ?> years</div>
                            </div>
                        </div>
                        
                        <div class="retirement-card">
                            <div class="retirement-icon">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <div class="retirement-content">
                                <div class="retirement-label">Retirement Date</div>
                                <div class="retirement-value"><?php echo date('F d, Y', strtotime($retirement_info['retirement_date'])); ?></div>
                                <div class="retirement-sub">Years to Retirement: <?php echo $retirement_info['years_to_retirement']; ?></div>
                            </div>
                        </div>
                        
                        <div class="retirement-card">
                            <div class="retirement-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="retirement-content">
                                <div class="retirement-label">Years of Service</div>
                                <div class="retirement-value"><?php echo $retirement_info['years_of_service']; ?> years</div>
                                <div class="retirement-sub">At Retirement: <?php echo $retirement_info['total_years_at_retirement']; ?> years</div>
                            </div>
                        </div>
                        
                        <div class="retirement-card">
                            <div class="retirement-icon">
                                <i class="fas fa-piggy-bank"></i>
                            </div>
                            <div class="retirement-content">
                                <div class="retirement-label">Estimated Pension</div>
                                <div class="retirement-value highlight"><?php echo $retirement_info['estimated_pension']; ?>/mo</div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <p class="no-data">No retirement information available. Please update birth date.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Loyalty Awards Tab -->
        <div class="tab-pane" id="awards">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-award"></i> Loyalty Awards & Recognitions</h3>
                    <button class="btn-small"><i class="fas fa-plus"></i> Add Award</button>
                </div>
                <div class="card-body">
                    <?php if (!empty($loyalty_awards)): ?>
                    <div class="awards-grid">
                        <?php foreach ($loyalty_awards as $award): ?>
                        <div class="award-card">
                            <div class="award-icon">
                                <i class="fas fa-medal"></i>
                            </div>
                            <div class="award-content">
                                <h4><?php echo $award['award']; ?></h4>
                                <p><?php echo $award['description']; ?></p>
                                <span class="award-date">Received: <?php echo date('F d, Y', strtotime($award['date_received'])); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="no-data">No loyalty awards recorded yet.</p>
                    <?php endif; ?>

                    <?php if (!empty($employment_details['date_hired'])): ?>
                    <div class="years-of-service">
                        <h4>Years of Service Timeline</h4>
                        <div class="timeline">
                            <?php
                            $service_years = [5, 10, 15, 20, 25, 30];
                            $current_years = $retirement_info['years_of_service'] ?? 0;
                            foreach ($service_years as $year):
                                $is_completed = $current_years >= $year;
                                $status_class = $is_completed ? 'completed' : 'upcoming';
                                $completion_date = !empty($employment_details['date_hired']) ? 
                                    date('F d, Y', strtotime($employment_details['date_hired'] . ' + ' . $year . ' years')) : 
                                    'TBD';
                            ?>
                            <div class="timeline-item <?php echo $status_class; ?>">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h5><?php echo $year; ?> Years</h5>
                                    <?php if ($is_completed): ?>
                                        <p>Completed on <?php echo $completion_date; ?></p>
                                        <span class="badge badge-success">Awarded</span>
                                    <?php else: ?>
                                        <p>Expected on <?php echo $completion_date; ?></p>
                                        <span class="badge badge-info">Upcoming</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Service History Tab -->
        <div class="tab-pane" id="history">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Service History</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($service_history)): ?>
                    <div class="timeline-vertical">
                        <?php foreach ($service_history as $history): ?>
                        <div class="timeline-item-vertical <?php echo strtolower($history['status']); ?>">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-period"><?php echo $history['period']; ?></div>
                                <h4><?php echo $history['position']; ?></h4>
                                <p><i class="fas fa-building"></i> <?php echo $history['department']; ?></p>
                                <p><i class="fas fa-money-bill"></i> <?php echo $history['salary_grade']; ?></p>
                                <?php if($history['status'] == 'Current'): ?>
                                <span class="status-badge status-current">Current Position</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="no-data">No service history recorded.</p>
                    <?php endif; ?>

                    <div class="service-summary">
                        <h4>Service Summary</h4>
                        <div class="info-grid">
                            <div class="info-row">
                                <div class="info-label">Years in Government Service</div>
                                <div class="info-value"><?php echo $employee['years_in_gov_service'] ?? 0; ?> years</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Years in LGU Service</div>
                                <div class="info-value"><?php echo $employee['years_in_lgu_service'] ?? 0; ?> years</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">First Day in Government</div>
                                <div class="info-value"><?php echo !empty($employee['first_day_gov_service']) ? date('F d, Y', strtotime($employee['first_day_gov_service'])) : 'N/A'; ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Continuous Service Start</div>
                                <div class="info-value"><?php echo !empty($employee['continuous_service_start']) ? date('F d, Y', strtotime($employee['continuous_service_start'])) : 'N/A'; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Tab -->
        <div class="tab-pane" id="performance">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> Performance Information</h3>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-row">
                            <div class="info-label">Latest Performance Rating</div>
                            <div class="info-value"><?php echo $employee['latest_performance_rating'] ?? 'Not Rated'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Performance Period</div>
                            <div class="info-value"><?php echo $employee['performance_period'] ?? 'N/A'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Pending Administrative Case</div>
                            <div class="info-value">
                                <?php if (!empty($employee['pending_admin_case'])): ?>
                                    <span class="status-badge status-warning">Yes</span>
                                <?php else: ?>
                                    <span class="status-badge status-success">No</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Additional styles for the profile */
.profile-avatar-large img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.personal-info-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.info-grid-compact {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
}

.info-item.full-width {
    grid-column: 1 / -1;
}

.info-icon {
    width: 35px;
    height: 35px;
    background: #e8f4fd;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #3498db;
}

.info-item small {
    display: block;
    color: #666;
    font-size: 10px;
    text-transform: uppercase;
}

.info-item strong {
    font-size: 13px;
    color: #333;
}

.service-summary {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
}

.service-summary h4 {
    font-size: 14px;
    color: #333;
    margin-bottom: 15px;
}

.no-data {
    text-align: center;
    color: #999;
    padding: 30px;
    font-style: italic;
}

.badge-secondary {
    background: #95a5a6;
    color: white;
}

.status-warning {
    background: #fff3cd;
    color: #856404;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
}
</style>

<script>
// Tab functionality
document.querySelectorAll('.tab-link').forEach(link => {
    link.addEventListener('click', function() {
        // Remove active class from all tabs and panes
        document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        
        // Add active class to clicked tab
        this.classList.add('active');
        
        // Show corresponding pane
        const tabId = this.getAttribute('data-tab');
        document.getElementById(tabId).classList.add('active');
    });
});

// Check for hash in URL to open specific tab
window.addEventListener('load', function() {
    const hash = window.location.hash.substring(1);
    if (hash) {
        const tab = document.querySelector(`.tab-link[data-tab="${hash}"]`);
        if (tab) {
            tab.click();
        }
    }
});
</script>