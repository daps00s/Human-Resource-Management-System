<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Check if connection exists
if (!isset($conn) || $conn->connect_error) {
    die("Database connection error. Please check your configuration.");
}

// Display session messages
if (isset($_SESSION['success'])) {
    $message = $_SESSION['success'];
    $message_type = 'success';
    unset($_SESSION['success']);
} elseif (isset($_SESSION['error'])) {
    $message = $_SESSION['error'];
    $message_type = 'danger';
    unset($_SESSION['error']);
} else {
    $message = '';
    $message_type = '';
}

// Fetch selected annex from session or default to A-1
$selected_annex = isset($_GET['annex']) ? $_GET['annex'] : (isset($_SESSION['selected_annex']) ? $_SESSION['selected_annex'] : 'A-1');
$_SESSION['selected_annex'] = $selected_annex;

// Fetch salary grades for selected annex
$sql = "SELECT * FROM salary_grades WHERE annex = ? ORDER BY 
        CAST(SUBSTRING(salary_grade, 4) AS UNSIGNED), step";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $selected_annex);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Error fetching salary grades: " . $conn->error);
}

$salary_grades = [];
while ($row = $result->fetch_assoc()) {
    $salary_grades[$row['salary_grade']][] = $row;
}
$stmt->close();

// Get statistics for selected annex
$total_grades = count($salary_grades);
$total_steps = 0;
foreach ($salary_grades as $steps) {
    $total_steps += count($steps);
}

$stats_sql = "SELECT 
                MIN(monthly_salary) as min_salary, 
                MAX(monthly_salary) as max_salary 
              FROM salary_grades 
              WHERE annex = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("s", $selected_annex);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$min_salary = $stats['min_salary'] ?? 0;
$max_salary = $stats['max_salary'] ?? 0;
$stats_stmt->close();

// LGU Positions by Department/Office
$lgu_positions = [
    'A-1' => [
        'title' => 'Annex A-1: Executive & Administrative Offices',
        'description' => 'Office of the Mayor, Administrator, and General Services',
        'icon' => 'fas fa-city',
        'color' => '#1e2b3a',
        'positions' => [
            'Municipal Mayor',
            'Municipal Administrator',
            'Administrative Aide III',
            'Administrative Aide VI',
            'Administrative Aide I',
            'Supervising Administrative Officer',
            'Administrative Assistant IV - Cash Clerk',
            'Administrative Assistant II',
            'Administrative Assistant III',
            'Administrative Aide IV',
            'MGADH I - GSO',
            'Supply Officer III',
            'Records Officer I',
            'Driver I',
            'Heavy Equipment Operator I',
            'Heavy Equipment Operator III',
            'Mechanic II',
            'Watchman I',
            'Ex-officio SB member, LNB President'
        ]
    ],
    'A-2' => [
        'title' => 'Annex A-2: Finance & Revenue Department',
        'description' => 'Accounting, Treasury, Assessment, and Revenue Collection',
        'icon' => 'fas fa-coins',
        'color' => '#27ae60',
        'positions' => [
            'Municipal Accountant',
            'Accountant III',
            'Local Assessment Operations Officer III',
            'Local Assessment Operations Officer I',
            'Assessment Clerk II',
            'Revenue Collection Clerk III',
            'Revenue Collection Clerk II',
            'Revenue Collection Clerk I',
            'Licensing Officer III',
            'Licensing Officer IV',
            'Licensing Officer V',
            'Tax Mapper II',
            'LRCO III',
            'Budget Officer III',
            'Budget Officer II',
            'Administrative Officer IV'
        ]
    ],
    'A-3' => [
        'title' => 'Annex A-3: Planning & Development',
        'description' => 'MPDC, Planning, and Development Office',
        'icon' => 'fas fa-draw-polygon',
        'color' => '#3498db',
        'positions' => [
            'MPDC',
            'Planning Officer III',
            'Planning Assistant',
            'Statistician Aide',
            'Environmental Management Specialist II',
            'Supervising Environment and Natural Resources Specialist'
        ]
    ],
    'A-4' => [
        'title' => 'Annex A-4: Engineering & Public Works',
        'description' => 'Municipal Engineer, Infrastructure, and Public Works',
        'icon' => 'fas fa-hard-hat',
        'color' => '#e67e22',
        'positions' => [
            'Municipal Engineer',
            'Engineer IV',
            'Engineer II',
            'Engineer I',
            'Heavy Equipment Operator I',
            'Heavy Equipment Operator III',
            'Mechanic II',
            'Administrative Aide III'
        ]
    ],
    'A-5' => [
        'title' => 'Annex A-5: Health Services',
        'description' => 'Rural Health Unit, Hospital, and Sanitation',
        'icon' => 'fas fa-hospital',
        'color' => '#e74c3c',
        'positions' => [
            'Health Officer',
            'Physician',
            'Medical Officer III',
            'Dentist III',
            'Dentist II',
            'Nurse IV',
            'Nurse III',
            'Nurse I',
            'Medical Technologist III',
            'Medical Technologist II',
            'Medical Technologist I',
            'Pharmacist I',
            'Sanitation Inspector VI',
            'Sanitation Inspector V',
            'Sanitation Inspector I',
            'Midwife I',
            'Midwife II',
            'Midwife III',
            'Dental Aide',
            'Administrative Aide VI'
        ]
    ],
    'A-6' => [
        'title' => 'Annex A-6: Agriculture & Veterinary',
        'description' => 'Office of the Municipal Agriculturist',
        'icon' => 'fas fa-tractor',
        'color' => '#8e44ad',
        'positions' => [
            'Municipal Agriculturist',
            'Supervising Agriculturist',
            'Agricultural Technologist - OAS',
            'Veterinarian I',
            'Agricultural Technologist'
        ]
    ],
    'A-7' => [
        'title' => 'Annex A-7: Social Welfare & HR',
        'description' => 'MSWDO, HRMO, and Social Services',
        'icon' => 'fas fa-hands-helping',
        'color' => '#16a085',
        'positions' => [
            'SWA',
            'Day Care Worker I',
            'Social Welfare Assistant',
            'SWO III',
            'Human Resource Assistant',
            'HRMO III',
            'HRMO V',
            'Human Resource Management Officer IV',
            'LDRRM Officer - IV',
            'LDRRM Officer - III',
            'LDRRM Assistant',
            'Driver I',
            'Admin Aide IV'
        ]
    ],
    'A-8' => [
        'title' => 'Annex A-8: Tourism & Culture',
        'description' => 'Tourism Office and Cultural Affairs',
        'icon' => 'fas fa-umbrella-beach',
        'color' => '#f39c12',
        'positions' => [
            'Senior Tourism Operations Officer',
            'Tourism Operations Officer I',
            'Administrative Aide III'
        ]
    ],
    'A-9' => [
        'title' => 'Annex A-9: Library & Information',
        'description' => 'Library Services and Information',
        'icon' => 'fas fa-book',
        'color' => '#2c3e50',
        'positions' => [
            'Librarian II',
            'Administrative Aide VI',
            'Administrative Aide III'
        ]
    ],
    'A-10' => [
        'title' => 'Annex A-10: Civil Registry',
        'description' => 'Office of the Municipal Civil Registrar',
        'icon' => 'fas fa-file-signature',
        'color' => '#7f8c8d',
        'positions' => [
            'Municipal Civil Registrar',
            'Registration Officer I',
            'Assistant Registration Officer',
            'Administrative Aide IV',
            'Administrative Aide III',
            'Administrative Aide VI'
        ]
    ],
    'A-11' => [
        'title' => 'Annex A-11: Market & Public Services',
        'description' => 'Public Market and Services',
        'icon' => 'fas fa-store',
        'color' => '#d35400',
        'positions' => [
            'Market Supervisor I',
            'Market Inspector II',
            'Revenue Collection Clerk III',
            'Revenue Collection Clerk II',
            'Administrative Assistant II',
            'Administrative Aide III',
            'Administrative Aide I',
            'Watchman I'
        ]
    ],
    'A-12' => [
        'title' => 'Annex A-12: Labor & Employment',
        'description' => 'Labor and Employment Services',
        'icon' => 'fas fa-briefcase',
        'color' => '#c0392b',
        'positions' => [
            'Supervising Labor and Employment Officer',
            'Senior Labor and Employment Officer',
            'Labor and Employment Assistant'
        ]
    ]
];

$current_annex = $lgu_positions[$selected_annex];
?>
<link rel="stylesheet" href="assets/css/salary_grade.css">
<!-- Dashboard Container -->
<div class="dashboard-container">

<!-- Content Header -->
<div class="content-header">
    <div class="welcome-banner" style="margin-bottom: 0; background: linear-gradient(135deg, #1e2b3a 0%, <?php echo $current_annex['color']; ?> 100%);">
        <div class="welcome-content">
            <h1><i class="<?php echo $current_annex['icon']; ?>"></i> <?php echo $current_annex['title']; ?></h1>
            <p><?php echo $current_annex['description']; ?></p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add Salary Grade
            </button>
            <button type="button" class="btn-primary" onclick="exportTable()">
                <i class="fas fa-file-excel"></i> Export
            </button>
            <button type="button" class="btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <button type="button" class="btn-primary" onclick="openImportModal()">
                <i class="fas fa-upload"></i> Import
            </button>
        </div>
    </div>
    
    <div class="breadcrumb-wrapper" style="padding: 10px 0;">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">LGU Compensation</a></li>
                <li class="breadcrumb-item active" aria-current="page">Salary Grade Matrix</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Alert Messages -->
<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Annex Selector -->
<div class="annex-selector">
    <div class="annex-header">
        <h3><i class="fas fa-building"></i> LGU Office/Department Annex</h3>
        <p>Select the appropriate annex based on the employee's office/department</p>
    </div>
    <div class="annex-grid">
        <?php foreach ($lgu_positions as $key => $annex): ?>
        <a href="?annex=<?php echo $key; ?>" class="annex-card <?php echo $selected_annex == $key ? 'active' : ''; ?>" style="border-left-color: <?php echo $annex['color']; ?>;">
            <div class="annex-icon" style="background: <?php echo $annex['color']; ?>20; color: <?php echo $annex['color']; ?>;">
                <i class="<?php echo $annex['icon']; ?>"></i>
            </div>
            <div class="annex-info">
                <h4><?php echo $key; ?></h4>
                <p><?php echo $annex['title']; ?></p>
                <div class="annex-badge"><?php echo count($annex['positions']); ?> positions</div>
            </div>
            <?php if ($selected_annex == $key): ?>
            <div class="annex-check">
                <i class="fas fa-check-circle"></i>
            </div>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Metrics Section -->
<div class="metrics-section">
    <h3>
        <i class="fas fa-chart-pie"></i>
        Salary Grade Overview - <?php echo $selected_annex; ?>
    </h3>
    
    <!-- Stats Row -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon" style="background: #e8f5e9; color: #27ae60;">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $total_grades; ?></div>
                <div class="stat-label">Total Grades</div>
                <div class="stat-trend positive">
                    <i class="fas fa-arrow-up"></i> SG-1 to SG-33
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #e3f2fd; color: #3498db;">
                <i class="fas fa-sort-numeric-up-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $total_steps; ?></div>
                <div class="stat-label">Total Steps</div>
                <div class="stat-trend positive">
                    <i class="fas fa-arrow-up"></i> 8 steps per grade
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #fff3e0; color: #e67e22;">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">₱<?php echo number_format($min_salary, 2); ?></div>
                <div class="stat-label">Minimum Salary</div>
                <div class="stat-trend positive">
                    <i class="fas fa-arrow-up"></i> Entry level
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #fce4ec; color: #e74c3c;">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">₱<?php echo number_format($max_salary, 2); ?></div>
                <div class="stat-label">Maximum Salary</div>
                <div class="stat-trend positive">
                    <i class="fas fa-arrow-up"></i> Highest grade
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Position Tags - Show all positions for selected annex -->
<div class="position-tags">
    <h4><i class="fas fa-users"></i> Positions in <?php echo $selected_annex; ?>:</h4>
    <div class="tags-container">
        <?php foreach ($current_annex['positions'] as $position): ?>
        <span class="position-tag" style="background: <?php echo $current_annex['color']; ?>10; color: <?php echo $current_annex['color']; ?>;">
            <i class="fas fa-user-tie"></i> <?php echo $position; ?>
        </span>
        <?php endforeach; ?>
    </div>
</div>

<!-- Main Salary Grade Matrix Card -->
<div class="card">
    <div class="card-header">
        <h3>
            <i class="<?php echo $current_annex['icon']; ?>"></i>
            Salary Grade Matrix with Step Increments - <?php echo $selected_annex; ?>
        </h3>
        <div class="card-actions">
            <span class="badge">8 Steps per Grade</span>
            <span class="badge" style="background: <?php echo $current_annex['color']; ?>;"><?php echo $selected_annex; ?></span>
            <span class="badge" style="background: #3498db;"><?php echo $total_steps; ?> Total Steps</span>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($salary_grades)): ?>
        <div style="text-align: center; padding: 40px;">
            <i class="fas fa-database" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
            <h3 style="color: #666; margin-bottom: 10px;">No Salary Grades Found</h3>
            <p style="color: #999; margin-bottom: 20px;">There are no salary grades in <?php echo $selected_annex; ?>. Click the "Add Salary Grade" button to create one.</p>
            <button type="button" class="btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add First Salary Grade
            </button>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="salary-matrix-table">
                <thead>
                    <tr>
                        <th>Salary Grade</th>
                        <th>Step 1</th>
                        <th>Step 2</th>
                        <th>Step 3</th>
                        <th>Step 4</th>
                        <th>Step 5</th>
                        <th>Step 6</th>
                        <th>Step 7</th>
                        <th>Step 8</th>
                        <th>Annual (Step 1)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Sort grades numerically
                    $sorted_grades = array_keys($salary_grades);
                    usort($sorted_grades, function($a, $b) {
                        return intval(substr($a, 3)) - intval(substr($b, 3));
                    });
                    
                    foreach ($sorted_grades as $grade): 
                        $steps = $salary_grades[$grade];
                        $step_values = [];
                        $step_ids = [];
                        $step1_salary = 0;
                        
                        foreach ($steps as $step) {
                            $step_values[$step['step']] = $step['monthly_salary'];
                            $step_ids[$step['step']] = $step['id'];
                            if ($step['step'] == 1) $step1_salary = $step['monthly_salary'];
                        }
                    ?>
                    <tr>
                        <td class="grade-cell"><strong><?php echo $grade; ?></strong></td>
                        <?php for ($s = 1; $s <= 8; $s++): ?>
                            <td class="salary-cell <?php echo isset($step_values[$s]) ? '' : 'empty-cell'; ?>">
                                <?php if (isset($step_values[$s])): ?>
                                    ₱<?php echo number_format($step_values[$s], 2); ?>
                                    <?php if ($s > 1 && isset($step_values[$s-1])): ?>
                                        <div class="step-increment">
                                            +₱<?php echo number_format($step_values[$s] - $step_values[$s-1], 2); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="empty-placeholder">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                        <td class="annual-cell">₱<?php echo number_format($step1_salary * 12, 2); ?></td>
                        <td class="actions-cell">
                            <!-- Edit button for each step - now using step ID -->
                            <?php if (isset($step_ids[1])): ?>
                                <button class="btn-icon" onclick="editStep(<?php echo $step_ids[1]; ?>)" title="Edit Step 1">
                                    <i class="fas fa-edit"></i>
                                </button>
                            <?php endif; ?>
                            
                            <!-- Delete button - you might want to delete individual steps or entire grade -->
                            <button class="btn-icon" onclick="deleteGrade('<?php echo $grade; ?>', '<?php echo $selected_annex; ?>')" title="Delete Grade">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="text-muted" style="font-size: 11px; margin-top: 10px;">
                <i class="fas fa-info-circle"></i> Click the edit icon to modify Step 1. For editing other steps, each step needs individual edit buttons.
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Sample Position Salaries Card -->
<div class="card" style="margin-top: 25px;">
    <div class="card-header">
        <h3>
            <i class="fas fa-user-tie"></i>
            Sample Position Salaries (<?php echo $selected_annex; ?>)
        </h3>
        <span class="badge" style="background: <?php echo $current_annex['color']; ?>;">LGU Position Reference</span>
    </div>
    <div class="card-body">
        <div class="sample-salaries-grid">
            <?php
            // Sample position salaries based on annex
            $sample_positions = [
                'A-1' => [
                    ['position' => 'Municipal Mayor', 'grade' => 'SG-30', 'step' => 1, 'amount' => 120000],
                    ['position' => 'Municipal Administrator', 'grade' => 'SG-26', 'step' => 3, 'amount' => 85000],
                    ['position' => 'Supervising Administrative Officer', 'grade' => 'SG-22', 'step' => 2, 'amount' => 65000],
                    ['position' => 'Administrative Officer IV', 'grade' => 'SG-18', 'step' => 1, 'amount' => 45000],
                    ['position' => 'Administrative Assistant IV', 'grade' => 'SG-12', 'step' => 3, 'amount' => 30000],
                    ['position' => 'Administrative Aide VI', 'grade' => 'SG-8', 'step' => 4, 'amount' => 22000],
                    ['position' => 'Administrative Aide III', 'grade' => 'SG-5', 'step' => 2, 'amount' => 18000],
                    ['position' => 'Administrative Aide I', 'grade' => 'SG-3', 'step' => 1, 'amount' => 15000]
                ],
                'A-2' => [
                    ['position' => 'Municipal Accountant', 'grade' => 'SG-24', 'step' => 1, 'amount' => 75000],
                    ['position' => 'Budget Officer III', 'grade' => 'SG-19', 'step' => 3, 'amount' => 52000],
                    ['position' => 'Revenue Collection Clerk III', 'grade' => 'SG-10', 'step' => 4, 'amount' => 26000],
                    ['position' => 'Revenue Collection Clerk II', 'grade' => 'SG-8', 'step' => 2, 'amount' => 21000],
                    ['position' => 'Revenue Collection Clerk I', 'grade' => 'SG-6', 'step' => 1, 'amount' => 19000],
                    ['position' => 'Licensing Officer III', 'grade' => 'SG-15', 'step' => 2, 'amount' => 36000],
                    ['position' => 'Tax Mapper II', 'grade' => 'SG-12', 'step' => 1, 'amount' => 28000]
                ],
                'A-5' => [
                    ['position' => 'Health Officer', 'grade' => 'SG-25', 'step' => 2, 'amount' => 80000],
                    ['position' => 'Physician', 'grade' => 'SG-24', 'step' => 3, 'amount' => 78000],
                    ['position' => 'Nurse IV', 'grade' => 'SG-19', 'step' => 2, 'amount' => 48000],
                    ['position' => 'Nurse III', 'grade' => 'SG-17', 'step' => 3, 'amount' => 42000],
                    ['position' => 'Nurse I', 'grade' => 'SG-15', 'step' => 1, 'amount' => 35000],
                    ['position' => 'Midwife III', 'grade' => 'SG-14', 'step' => 4, 'amount' => 34000],
                    ['position' => 'Midwife II', 'grade' => 'SG-12', 'step' => 2, 'amount' => 29000],
                    ['position' => 'Midwife I', 'grade' => 'SG-10', 'step' => 1, 'amount' => 25000],
                    ['position' => 'Medical Technologist III', 'grade' => 'SG-16', 'step' => 3, 'amount' => 38000]
                ],
                'A-6' => [
                    ['position' => 'Municipal Agriculturist', 'grade' => 'SG-22', 'step' => 1, 'amount' => 62000],
                    ['position' => 'Supervising Agriculturist', 'grade' => 'SG-20', 'step' => 3, 'amount' => 55000],
                    ['position' => 'Agricultural Technologist', 'grade' => 'SG-15', 'step' => 2, 'amount' => 36000],
                    ['position' => 'Veterinarian I', 'grade' => 'SG-19', 'step' => 1, 'amount' => 48000]
                ],
                'A-7' => [
                    ['position' => 'SWO III', 'grade' => 'SG-16', 'step' => 2, 'amount' => 38000],
                    ['position' => 'HRMO V', 'grade' => 'SG-24', 'step' => 1, 'amount' => 72000],
                    ['position' => 'HRMO III', 'grade' => 'SG-18', 'step' => 3, 'amount' => 46000],
                    ['position' => 'Day Care Worker I', 'grade' => 'SG-8', 'step' => 1, 'amount' => 20000],
                    ['position' => 'Social Welfare Assistant', 'grade' => 'SG-10', 'step' => 2, 'amount' => 25000]
                ]
            ];
            
            $samples = isset($sample_positions[$selected_annex]) ? $sample_positions[$selected_annex] : $sample_positions['A-1'];
            foreach ($samples as $sample):
            ?>
            <div class="sample-card">
                <div class="sample-header">
                    <h4><?php echo $sample['position']; ?></h4>
                    <span class="sample-badge"><?php echo $sample['grade']; ?></span>
                </div>
                <div class="sample-details">
                    <div class="sample-detail">
                        <span class="detail-label">Step</span>
                        <span class="detail-value"><?php echo $sample['step']; ?></span>
                    </div>
                    <div class="sample-detail">
                        <span class="detail-label">Monthly</span>
                        <span class="detail-value">₱<?php echo number_format($sample['amount'], 2); ?></span>
                    </div>
                    <div class="sample-detail">
                        <span class="detail-label">Annual</span>
                        <span class="detail-value">₱<?php echo number_format($sample['amount'] * 12, 2); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card" style="margin-top: 25px;">
    <div class="card-header">
        <h3>
            <i class="fas fa-bolt"></i>
            Quick Actions
        </h3>
    </div>
    <div class="card-body">
        <div class="quick-actions-grid">
            <a href="step_increments.php" class="quick-action-item">
                <i class="fas fa-arrow-circle-up"></i>
                <span>Step Increments</span>
            </a>
            <a href="salary_adjustments.php" class="quick-action-item">
                <i class="fas fa-adjust"></i>
                <span>Adjustments</span>
            </a>
            <a href="benefits.php" class="quick-action-item">
                <i class="fas fa-gift"></i>
                <span>Benefits</span>
            </a>
            <a href="payroll.php" class="quick-action-item">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Payroll</span>
            </a>
            <a href="#" class="quick-action-item">
                <i class="fas fa-chart-line"></i>
                <span>Reports</span>
            </a>
            <a href="#" class="quick-action-item">
                <i class="fas fa-history"></i>
                <span>History</span>
            </a>
        </div>
    </div>
</div>

<!-- Add Salary Grade Modal -->
<div class="modal" id="addSalaryGradeModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add New Salary Grade to <?php echo $selected_annex; ?></h5>
                <button type="button" class="btn-close" onclick="closeModal('addSalaryGradeModal')">&times;</button>
            </div>
            <form action="process_salary_grade.php" method="POST" id="addSalaryGradeForm">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="annex">Annex <span class="required">*</span></label>
                                <select class="form-control" id="annex" name="annex" required>
                                    <?php foreach ($lgu_positions as $key => $annex): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $selected_annex == $key ? 'selected' : ''; ?>>
                                        <?php echo $key; ?> - <?php echo $annex['title']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="salary_grade">Salary Grade <span class="required">*</span></label>
                                <select class="form-control" id="salary_grade" name="salary_grade" required>
                                    <option value="">Select Grade</option>
                                    <?php for ($i = 1; $i <= 33; $i++): ?>
                                        <option value="SG-<?php echo $i; ?>">SG-<?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="step">Step <span class="required">*</span></label>
                                <select class="form-control" id="step" name="step" required>
                                    <option value="">Select Step</option>
                                    <?php for ($s = 1; $s <= 8; $s++): ?>
                                        <option value="<?php echo $s; ?>">Step <?php echo $s; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="monthly_salary">Monthly Salary (₱) <span class="required">*</span></label>
                                <input type="number" class="form-control" id="monthly_salary" name="monthly_salary" step="0.01" required oninput="calculateAnnual()">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="annual_salary">Annual Salary (₱)</label>
                                <input type="number" class="form-control" id="annual_salary" name="annual_salary" step="0.01" readonly>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="effective_date">Effective Date <span class="required">*</span></label>
                                <input type="date" class="form-control" id="effective_date" name="effective_date" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="position_category">Position Category</label>
                                <input type="text" class="form-control" id="position_category" name="position_category" placeholder="e.g., Administrative, Technical, etc.">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('addSalaryGradeModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save Grade</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal" id="importModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-upload"></i> Import Salary Grades</h5>
                <button type="button" class="btn-close" onclick="closeModal('importModal')">&times;</button>
            </div>
            <form action="process_salary_grade.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="bulk_import">
                <input type="hidden" name="annex" value="<?php echo $selected_annex; ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label>CSV File Format</label>
                        <p style="font-size: 12px; color: #666; margin-bottom: 10px;">
                            The CSV file should have the following columns:<br>
                            <strong>annex, salary_grade, step, monthly_salary, effective_date, position_category</strong>
                        </p>
                        <a href="sample_salary_grade.csv" class="btn-primary" style="display: inline-block; margin-bottom: 15px;" download>
                            <i class="fas fa-download"></i> Download Sample CSV
                        </a>
                    </div>
                    <div class="form-group">
                        <label for="import_file">Select CSV File <span class="required">*</span></label>
                        <input type="file" class="form-control" id="import_file" name="import_file" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('importModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Salary Grade Modal -->
<div class="modal" id="editSalaryGradeModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Salary Grade</h5>
                <button type="button" class="btn-close" onclick="closeModal('editSalaryGradeModal')">&times;</button>
            </div>
            <form action="process_salary_grade.php" method="POST" id="editSalaryGradeForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Annex</label>
                                <input type="text" class="form-control" id="edit_annex_display" readonly>
                                <input type="hidden" id="edit_annex" name="annex">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Salary Grade</label>
                                <input type="text" class="form-control" id="edit_salary_grade_display" readonly>
                                <input type="hidden" id="edit_salary_grade" name="salary_grade">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Step</label>
                                <input type="text" class="form-control" id="edit_step_display" readonly>
                                <input type="hidden" id="edit_step" name="step">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_monthly_salary">Monthly Salary (₱) <span class="required">*</span></label>
                                <input type="number" class="form-control" id="edit_monthly_salary" name="monthly_salary" step="0.01" required oninput="editCalculateAnnual()">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_annual_salary">Annual Salary (₱)</label>
                                <input type="number" class="form-control" id="edit_annual_salary" name="annual_salary" step="0.01" readonly>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_effective_date">Effective Date <span class="required">*</span></label>
                                <input type="date" class="form-control" id="edit_effective_date" name="effective_date" required>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="edit_position_category">Position Category</label>
                                <input type="text" class="form-control" id="edit_position_category" name="position_category" placeholder="e.g., Administrative, Technical, etc.">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('editSalaryGradeModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Update Grade</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="deleteModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: #e74c3c; color: white;">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                <button type="button" class="btn-close" onclick="closeModal('deleteModal')" style="filter: invert(1);">&times;</button>
            </div>
            <div class="modal-body">
                <p id="deleteMessage">Are you sure you want to delete this salary grade?</p>
                <p style="color: #e74c3c; font-size: 12px; margin-top: 10px;">
                    <i class="fas fa-exclamation-triangle"></i> This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn-primary" style="background: #e74c3c;">Delete</a>
            </div>
        </div>
    </div>
</div>

</div> <!-- End Dashboard Container -->

<!-- Create get_salary_grade.php for AJAX -->
<script>
// Modal functions
function openAddModal() {
    document.getElementById('addSalaryGradeModal').classList.add('show');
}

function openImportModal() {
    document.getElementById('importModal').classList.add('show');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

// Calculate annual salary for add modal
function calculateAnnual() {
    let monthly = parseFloat(document.getElementById('monthly_salary').value) || 0;
    document.getElementById('annual_salary').value = (monthly * 12).toFixed(2);
}

// Calculate annual salary for edit modal
function editCalculateAnnual() {
    let monthly = parseFloat(document.getElementById('edit_monthly_salary').value) || 0;
    document.getElementById('edit_annual_salary').value = (monthly * 12).toFixed(2);
}

// Edit step function - fetches data and opens edit modal
function editStep(stepId) {
    // Show loading state
    const editBtn = event.target.closest('.btn-icon');
    const originalHtml = editBtn.innerHTML;
    editBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    editBtn.disabled = true;
    
    // Fetch the salary grade data
    fetch('get_salary_grade.php?id=' + stepId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Populate the edit modal with the data
                document.getElementById('edit_id').value = data.data.id;
                document.getElementById('edit_annex_display').value = data.data.annex;
                document.getElementById('edit_annex').value = data.data.annex;
                document.getElementById('edit_salary_grade_display').value = data.data.salary_grade;
                document.getElementById('edit_salary_grade').value = data.data.salary_grade;
                document.getElementById('edit_step_display').value = 'Step ' + data.data.step;
                document.getElementById('edit_step').value = data.data.step;
                document.getElementById('edit_monthly_salary').value = data.data.monthly_salary;
                document.getElementById('edit_annual_salary').value = data.data.annual_salary;
                document.getElementById('edit_effective_date').value = data.data.effective_date;
                document.getElementById('edit_position_category').value = data.data.position_category || '';
                
                // Open the modal
                document.getElementById('editSalaryGradeModal').classList.add('show');
            } else {
                alert('Error loading data: ' + data.message);
            }
            
            // Restore button state
            editBtn.innerHTML = originalHtml;
            editBtn.disabled = false;
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading data. Please try again.');
            editBtn.innerHTML = originalHtml;
            editBtn.disabled = false;
        });
}

// Delete grade function
function deleteGrade(grade, annex) {
    // For now, we'll show a message that you need to specify which step to delete
    // In a real implementation, you would pass the specific step ID
    document.getElementById('deleteMessage').innerHTML = 'Are you sure you want to delete all steps for ' + grade + ' in ' + annex + '?';
    document.getElementById('confirmDeleteBtn').href = 'process_salary_grade.php?action=delete&grade=' + grade + '&annex=' + annex;
    document.getElementById('deleteModal').classList.add('show');
}

// Export table function
function exportTable() {
    window.location.href = 'export_salary_grade.php?annex=<?php echo $selected_annex; ?>';
}

// Close modals when clicking outside
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});

// Auto-calculate annual salary on input
document.getElementById('monthly_salary').addEventListener('input', calculateAnnual);

// Prevent modal from closing when clicking inside modal content
document.querySelectorAll('.modal-content').forEach(content => {
    content.addEventListener('click', function(e) {
        e.stopPropagation();
    });
});
</script>

<!-- Add this CSS to style the action buttons properly -->
<style>
/* Additional styles for action buttons */
.actions-cell {
    display: flex;
    gap: 5px;
    justify-content: center;
}

.btn-icon {
    background: none;
    border: none;
    padding: 8px;
    cursor: pointer;
    color: #666;
    transition: all 0.3s;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-icon:hover {
    background: #f0f0f0;
    color: #3498db;
}

.btn-icon[title="Delete Grade"]:hover {
    color: #e74c3c;
    background: #fde9e9;
}

/* Modal styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-dialog {
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-dialog.modal-lg {
    max-width: 800px;
}

.modal-content {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        transform: translateY(-30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h5 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-header h5 i {
    color: #3498db;
}

.modal-header[style*="background: #e74c3c"] h5 i {
    color: white;
}

.btn-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 0;
    line-height: 1;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.3s;
}

.btn-close:hover {
    background: rgba(0,0,0,0.1);
    color: #333;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.required {
    color: #e74c3c;
    font-size: 12px;
}

.btn-secondary {
    background: #95a5a6;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.3s;
    font-size: 13px;
}

.btn-secondary:hover {
    background: #7f8c8d;
}

.btn-primary {
    background: #3498db;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.3s;
    font-size: 13px;
}

.btn-primary:hover {
    background: #2980b9;
}

/* Form styles inside modal */
.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-size: 12px;
    font-weight: 500;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
    transition: border-color 0.3s;
}

.form-control:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 0 2px rgba(52,152,219,0.1);
}

.form-control[readonly] {
    background: #f5f5f5;
    cursor: not-allowed;
}

.row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.col-md-6 {
    grid-column: span 1;
}

.col-md-12 {
    grid-column: span 2;
}

/* Responsive */
@media (max-width: 768px) {
    .row {
        grid-template-columns: 1fr;
    }
    
    .col-md-12 {
        grid-column: span 1;
    }
    
    .modal-dialog {
        width: 95%;
        margin: 10px auto;
    }
}
</style>

