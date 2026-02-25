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

// Fetch employee details
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

if ($salary_history_result) {
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

// If no salary history, use sample data for demo
if (empty($salary_history)) {
    $salary_history = [
        ['date' => '2025-01-01', 'type' => 'Step Increment', 'amount' => '+₱1,500.00', 'old_salary' => '₱33,500.00', 'new_salary' => '₱35,000.00'],
        ['date' => '2024-01-01', 'type' => 'Step Increment', 'amount' => '+₱1,500.00', 'old_salary' => '₱32,000.00', 'new_salary' => '₱33,500.00'],
        ['date' => '2023-06-01', 'type' => 'Salary Adjustment', 'amount' => '+₱2,000.00', 'old_salary' => '₱30,000.00', 'new_salary' => '₱32,000.00']
    ];
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
} else {
    // Sample data if no birth date
    $retirement_info = [
        'birth_date' => '1985-03-20',
        'age' => 40,
        'retirement_age' => 65,
        'years_to_retirement' => 25,
        'retirement_date' => '2050-03-20',
        'years_of_service' => $years_of_service,
        'total_years_at_retirement' => $years_of_service + 25,
        'estimated_pension' => '₱25,000.00'
    ];
}

// Government numbers (from employee table if available)
$retirement_info['gsis_number'] = $employee['gsis_number'] ?? 'GSIS-1234-5678';
$retirement_info['pagibig_number'] = $employee['pagibig_number'] ?? 'PAG-1234-5678';
$retirement_info['philhealth_number'] = $employee['philhealth_number'] ?? 'PHIL-1234-5678';
$retirement_info['tin_number'] = $employee['tin_number'] ?? '123-456-789-000';

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

// If no awards, use sample data
if (empty($loyalty_awards)) {
    $loyalty_awards = [
        ['year' => 2025, 'award' => '5 Years of Service', 'description' => 'Loyalty Award for 5 years', 'date_received' => '2025-06-15'],
        ['year' => 2023, 'award' => 'Employee of the Year', 'description' => 'Outstanding Performance', 'date_received' => '2023-12-15'],
        ['year' => 2022, 'award' => 'Perfect Attendance', 'description' => 'No absences for the year', 'date_received' => '2022-12-20']
    ];
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
        $end_year = $row['is_current'] ? 'Present' : date('Y', strtotime($row['end_date']));
        
        $service_history[] = [
            'period' => $start_year . ' - ' . $end_year,
            'position' => $row['position'],
            'department' => $row['department'] ?? 'N/A',
            'salary_grade' => $row['salary_grade'] ?? 'N/A',
            'status' => $row['is_current'] ? 'Current' : 'Previous'
        ];
    }
}

// If no service history, use sample data
if (empty($service_history)) {
    $service_history = [
        [
            'period' => '2023 - Present',
            'position' => $employment_details['position'],
            'department' => $employment_details['department'],
            'salary_grade' => $salary_info['salary_grade'],
            'status' => 'Current'
        ],
        [
            'period' => '2021 - 2023',
            'position' => 'HR Clerk',
            'department' => 'Human Resources',
            'salary_grade' => 'SG-12',
            'status' => 'Previous'
        ],
        [
            'period' => '2020 - 2021',
            'position' => 'Administrative Aide',
            'department' => 'Administration',
            'salary_grade' => 'SG-10',
            'status' => 'Previous'
        ]
    ];
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
                <?php echo strtoupper(substr($employee['first_name'] ?? $employee['username'], 0, 1)); ?>
            </div>
        </div>
        <div class="profile-info-header">
            <h1><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h1>
            <p class="profile-title"><?php echo htmlspecialchars($employment_details['position']); ?></p>
            <p class="profile-dept"><?php echo htmlspecialchars($employment_details['department']); ?></p>
            <div class="profile-badges">
                <span class="badge badge-primary">Employee ID: <?php echo htmlspecialchars($employment_details['employee_id']); ?></span>
                <span class="badge badge-success"><?php echo $employment_details['employment_status']; ?></span>
                <span class="badge badge-info"><?php echo $salary_info['salary_grade'] . ' ' . $salary_info['step']; ?></span>
            </div>
        </div>
        <div class="profile-actions">
            <button class="btn-primary"><i class="fas fa-edit"></i> Edit Profile</button>
            <button class="btn-secondary"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>

    <!-- Profile Tabs -->
    <div class="profile-tabs">
        <div class="tab-nav">
            <button class="tab-link active" data-tab="employment">Employment Details</button>
            <button class="tab-link" data-tab="salary">Salary & Increment</button>
            <button class="tab-link" data-tab="retirement">Retirement Info</button>
            <button class="tab-link" data-tab="awards">Loyalty Awards</button>
            <button class="tab-link" data-tab="history">Service History</button>
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
                            <div class="info-label">Position</div>
                            <div class="info-value"><?php echo $employment_details['position']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Department</div>
                            <div class="info-value"><?php echo $employment_details['department']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Branch/Office</div>
                            <div class="info-value"><?php echo $employment_details['branch']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Supervisor</div>
                            <div class="info-value"><?php echo $employment_details['supervisor']; ?></div>
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
        </div>

        <!-- Retirement Info Tab -->
        <div class="tab-pane" id="retirement">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-clock"></i> Retirement Information</h3>
                </div>
                <div class="card-body">
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

                    <div class="benefits-section">
                        <h4>Government Numbers</h4>
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
        </div>

        <!-- Loyalty Awards Tab -->
        <div class="tab-pane" id="awards">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-award"></i> Loyalty Awards & Recognitions</h3>
                    <button class="btn-small"><i class="fas fa-plus"></i> Add Award</button>
                </div>
                <div class="card-body">
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

                    <div class="years-of-service">
                        <h4>Years of Service Timeline</h4>
                        <div class="timeline">
                            <?php
                            $service_years = [5, 10, 15, 20, 25, 30];
                            $current_years = $retirement_info['years_of_service'];
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

                    <div class="promotion-history">
                        <h4>Promotion History</h4>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>From Position</th>
                                    <th>To Position</th>
                                    <th>From Salary Grade</th>
                                    <th>To Salary Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $promotions = array_filter($salary_history, function($adj) {
                                    return $adj['type'] == 'Promotion';
                                });
                                if (!empty($promotions)):
                                    foreach ($promotions as $promo):
                                ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($promo['date'])); ?></td>
                                    <td>Previous Position</td>
                                    <td>New Position</td>
                                    <td><?php echo $promo['old_salary']; ?></td>
                                    <td><?php echo $promo['new_salary']; ?></td>
                                </tr>
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                <tr>
                                    <td>Jan 01, 2023</td>
                                    <td>HR Clerk</td>
                                    <td>HR Assistant</td>
                                    <td>SG-12</td>
                                    <td>SG-15</td>
                                </tr>
                                <tr>
                                    <td>Jun 15, 2021</td>
                                    <td>Administrative Aide</td>
                                    <td>HR Clerk</td>
                                    <td>SG-10</td>
                                    <td>SG-12</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
</script>