<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'employee';

// Get current user info
$user_sql = "SELECT u.*, ee.position, o.office_name 
             FROM users u 
             LEFT JOIN employee_employment ee ON u.id = ee.user_id
             LEFT JOIN offices o ON ee.office_id = o.id
             WHERE u.id = ?";
$user_stmt = $db->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$current_user = $user_stmt->get_result()->fetch_assoc();

// Get current date
$current_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Handle POST requests for time in/out
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_POST['action'])) {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        if ($_POST['action'] === 'time_in') {
            $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            $date = $_POST['date'] ?? date('Y-m-d');
            $time_in = $_POST['time_in'] ?? date('H:i:s');
            $remarks = $_POST['remarks'] ?? '';
            
            if (!$target_user_id) {
                $response['message'] = 'Invalid employee ID';
                echo json_encode($response);
                exit;
            }
            
            // Get employee name
            $name_sql = "SELECT first_name, last_name FROM users WHERE id = ?";
            $name_stmt = $db->prepare($name_sql);
            $name_stmt->bind_param("i", $target_user_id);
            $name_stmt->execute();
            $name_result = $name_stmt->get_result();
            $employee = $name_result->fetch_assoc();
            $employee_name = $employee ? $employee['first_name'] . ' ' . $employee['last_name'] : 'Employee';
            
            // Check if already timed in
            $check_sql = "SELECT id FROM daily_time_record WHERE user_id = ? AND date = ?";
            $check_stmt = $db->prepare($check_sql);
            $check_stmt->bind_param("is", $target_user_id, $date);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $response['message'] = $employee_name . ' already timed in for today';
            } else {
                $sql = "INSERT INTO daily_time_record 
                        (user_id, date, time_in, time_in_remarks, status, ip_address, device_info, created_by, created_at) 
                        VALUES (?, ?, ?, ?, 'present', ?, ?, ?, NOW())";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("isssssi", $target_user_id, $date, $time_in, $remarks, $ip_address, $user_agent, $user_id);
                
                if ($stmt->execute()) {
                    $formatted_time = date('h:i A', strtotime($time_in));
                    $response['success'] = true;
                    $response['message'] = $employee_name . ' timed in at ' . $formatted_time;
                } else {
                    $response['message'] = 'Error recording time in: ' . $db->error;
                }
            }
        }
        
        if ($_POST['action'] === 'time_out') {
            $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            $date = $_POST['date'] ?? date('Y-m-d');
            $time_out = $_POST['time_out'] ?? date('H:i:s');
            $remarks = $_POST['remarks'] ?? '';
            
            if (!$target_user_id) {
                $response['message'] = 'Invalid employee ID';
                echo json_encode($response);
                exit;
            }
            
            // Get employee name
            $name_sql = "SELECT first_name, last_name FROM users WHERE id = ?";
            $name_stmt = $db->prepare($name_sql);
            $name_stmt->bind_param("i", $target_user_id);
            $name_stmt->execute();
            $name_result = $name_stmt->get_result();
            $employee = $name_result->fetch_assoc();
            $employee_name = $employee ? $employee['first_name'] . ' ' . $employee['last_name'] : 'Employee';
            
            // Get time in
            $get_sql = "SELECT time_in FROM daily_time_record WHERE user_id = ? AND date = ?";
            $get_stmt = $db->prepare($get_sql);
            $get_stmt->bind_param("is", $target_user_id, $date);
            $get_stmt->execute();
            $get_result = $get_stmt->get_result();
            $record = $get_result->fetch_assoc();
            
            if ($record) {
                $time_in_ts = strtotime($record['time_in']);
                $time_out_ts = strtotime($time_out);
                $total_hours = round(($time_out_ts - $time_in_ts) / 3600, 1);
                
                $sql = "UPDATE daily_time_record SET 
                        time_out = ?,
                        time_out_remarks = ?,
                        total_hours = ?,
                        updated_at = NOW()
                        WHERE user_id = ? AND date = ?";
                $stmt = $db->prepare($sql);
                $stmt->bind_param("ssdis", $time_out, $remarks, $total_hours, $target_user_id, $date);
                
                if ($stmt->execute()) {
                    $formatted_time = date('h:i A', strtotime($time_out));
                    $response['success'] = true;
                    $response['message'] = $employee_name . ' timed out at ' . $formatted_time . ' (' . $total_hours . ' hrs)';
                } else {
                    $response['message'] = 'Error recording time out';
                }
            } else {
                $response['message'] = $employee_name . ' has no time in record found';
            }
        }
    }
    
    echo json_encode($response);
    exit;
}

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_GET['ajax'] === 'get_employees_today') {
        $date = $_GET['date'] ?? date('Y-m-d');
        
        $sql = "SELECT 
                    u.id,
                    u.first_name,
                    u.last_name,
                    u.employee_id,
                    u.profile_picture,
                    o.office_name,
                    ee.position,
                    dtr.time_in,
                    dtr.time_out,
                    dtr.total_hours,
                    dtr.id as record_id,
                    CASE 
                        WHEN dtr.id IS NULL THEN 'absent'
                        WHEN dtr.time_out IS NOT NULL THEN 'completed'
                        WHEN dtr.time_in > '08:30:00' THEN 'late'
                        ELSE 'present'
                    END as attendance_status
                FROM users u
                LEFT JOIN employee_employment ee ON u.id = ee.user_id
                LEFT JOIN offices o ON ee.office_id = o.id
                LEFT JOIN daily_time_record dtr ON u.id = dtr.user_id AND dtr.date = ?
                WHERE u.role = 'employee' AND (u.employment_status = 'Active' OR u.employment_status IS NULL)
                ORDER BY u.last_name";
        
        $stmt = $db->prepare($sql);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $employees = [];
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $employees]);
        exit;
    }
    
    if ($_GET['ajax'] === 'get_attendance_summary') {
        $date = $_GET['date'] ?? date('Y-m-d');
        
        $sql = "SELECT 
                    COUNT(*) as total_employees,
                    SUM(CASE WHEN dtr.id IS NOT NULL AND dtr.time_out IS NULL AND dtr.time_in <= '08:30:00' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN dtr.id IS NOT NULL AND dtr.time_out IS NULL AND dtr.time_in > '08:30:00' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN dtr.id IS NOT NULL AND dtr.time_out IS NOT NULL THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN dtr.id IS NULL THEN 1 ELSE 0 END) as absent
                FROM users u
                LEFT JOIN daily_time_record dtr ON u.id = dtr.user_id AND dtr.date = ?
                WHERE u.role = 'employee' AND (u.employment_status = 'Active' OR u.employment_status IS NULL)";
        
        $stmt = $db->prepare($sql);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $summary = $result->fetch_assoc();
        
        echo json_encode(['success' => true, 'data' => $summary]);
        exit;
    }
}

// Get offices for filter
$offices = $db->query("SELECT id, office_name FROM offices WHERE status = 'active' ORDER BY office_name");

include 'includes/header.php';
?>

<style>
/* Main Container */
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

/* Welcome Banner */
.welcome-banner {
    background: linear-gradient(135deg, #003366 0%, #004080 100%);
    border-radius: 10px;
    padding: 25px 30px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.welcome-content h1 {
    font-size: 26px;
    margin: 0 0 8px;
    font-weight: 500;
}

.welcome-content p {
    font-size: 14px;
    margin: 0;
    opacity: 0.9;
}

.welcome-actions {
    display: flex;
    gap: 12px;
}

/* Buttons */
.btn-primary, .btn-success, .btn-danger, .btn-secondary {
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
    border: none;
}

.btn-primary {
    background: rgba(255,255,255,0.15);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
}

.btn-primary:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-2px);
}

.btn-success {
    background: #27ae60;
    color: white;
}

.btn-success:hover {
    background: #2ecc71;
    transform: translateY(-2px);
}

.btn-danger {
    background: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background: #7f8c8d;
    transform: translateY(-2px);
}

.btn-link {
    color: #003366;
    text-decoration: none;
    font-weight: 500;
    background: none;
    border: none;
    cursor: pointer;
}

.btn-link:hover {
    text-decoration: underline;
}

.btn-icon {
    background: none;
    border: none;
    padding: 8px;
    cursor: pointer;
    color: #95a5a6;
    border-radius: 6px;
    transition: all 0.3s;
    font-size: 14px;
}

.btn-icon:hover {
    color: #003366;
    background: #e8f0fe;
}

/* Date Navigator */
.date-navigator {
    background: white;
    border-radius: 10px;
    padding: 15px 20px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.date-controls {
    display: flex;
    gap: 10px;
    align-items: center;
}

.date-display {
    font-size: 18px;
    font-weight: 600;
    color: #003366;
    min-width: 200px;
    text-align: center;
}

.date-picker {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 13px;
}

/* Summary Cards */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

.summary-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    cursor: pointer;
    transition: all 0.3s;
}

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.summary-card.active {
    border: 2px solid #003366;
}

.summary-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.bg-primary { background: #003366; }
.bg-success { background: #27ae60; }
.bg-warning { background: #f39c12; }
.bg-info { background: #3498db; }
.bg-danger { background: #e74c3c; }

.summary-content h3 {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
    margin: 0 0 5px;
}

.summary-content p {
    font-size: 13px;
    color: #7f8c8d;
    margin: 0;
}

/* Filter Bar */
.filter-bar {
    background: white;
    border-radius: 10px;
    padding: 15px 20px;
    margin-bottom: 20px;
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.filter-select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 13px;
    min-width: 200px;
}

.search-box {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 13px;
    flex: 1;
    min-width: 250px;
}

/* Employee Grid */
.employee-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.employee-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.employee-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(0,51,102,0.15);
}

.employee-card.present {
    border-top: 4px solid #27ae60;
}

.employee-card.late {
    border-top: 4px solid #f39c12;
}

.employee-card.completed {
    border-top: 4px solid #3498db;
}

.employee-card.absent {
    border-top: 4px solid #e74c3c;
}

.employee-card-header {
    padding: 20px;
    text-align: center;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    position: relative;
}

.employee-status-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 4px 8px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-present { background: #27ae60; color: white; }
.status-late { background: #f39c12; color: white; }
.status-completed { background: #3498db; color: white; }
.status-absent { background: #e74c3c; color: white; }

.employee-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    margin: 0 auto 15px;
    overflow: hidden;
    border: 4px solid white;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.employee-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #003366, #004080);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 600;
}

.employee-name {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0 0 5px;
}

.employee-details {
    font-size: 13px;
    color: #7f8c8d;
    margin-bottom: 5px;
}

.employee-id {
    font-size: 11px;
    color: #95a5a6;
    background: rgba(0,0,0,0.03);
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
}

.employee-card-body {
    padding: 20px;
}

.time-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 6px;
}

.time-label {
    font-size: 12px;
    color: #7f8c8d;
}

.time-value {
    font-size: 14px;
    font-weight: 600;
    color: #2c3e50;
}

.employee-card-footer {
    padding: 15px 20px;
    background: #f8f9fa;
    border-top: 1px solid #ecf0f1;
    display: flex;
    gap: 10px;
}

.btn-card {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.btn-card-in {
    background: #27ae60;
    color: white;
}

.btn-card-in:hover {
    background: #2ecc71;
    transform: translateY(-2px);
}

.btn-card-out {
    background: #e74c3c;
    color: white;
}

.btn-card-out:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

.btn-card-view {
    background: #95a5a6;
    color: white;
    cursor: default;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1100;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-dialog {
    width: 90%;
    max-width: 450px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
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
    padding: 18px 25px;
    background: #f8f9fa;
    border-bottom: 1px solid #ecf0f1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h5 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #003366;
}

.modal-body {
    padding: 25px;
}

.modal-footer {
    padding: 18px 25px;
    border-top: 1px solid #ecf0f1;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    background: #f8f9fa;
}

/* Form Styles */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #2c3e50;
}

.form-control {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 13px;
    box-sizing: border-box;
}

.form-control:focus {
    border-color: #003366;
    outline: none;
}

.form-control[readonly] {
    background: #f8f9fa;
    cursor: not-allowed;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    padding: 12px 15px;
    border-radius: 6px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 15px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 50px;
    color: #95a5a6;
    grid-column: 1/-1;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    color: #d0d7de;
}

/* Spinner */
.spinner {
    display: inline-block;
    width: 30px;
    height: 30px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #003366;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 1200px) {
    .summary-cards {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .welcome-banner {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .summary-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .date-navigator {
        flex-direction: column;
        gap: 15px;
    }
    
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .employee-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .summary-cards {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="dashboard-container">
    <!-- Content Header -->
    <div class="welcome-banner">
        <div class="welcome-content">
            <h1><i class="fas fa-clock"></i> Daily Time Record</h1>
            <p><?php echo date('l, F j, Y', strtotime($current_date)); ?> | Team Attendance Dashboard</p>
        </div>
        <div class="welcome-actions">
            <button class="btn-primary" onclick="refreshDashboard()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Date Navigator -->
    <div class="date-navigator">
        <div class="date-controls">
            <button class="btn-icon" onclick="changeDate('prev')">
                <i class="fas fa-chevron-left"></i>
            </button>
            <span class="date-display" id="dateDisplay">
                <?php echo date('F d, Y', strtotime($current_date)); ?>
            </span>
            <button class="btn-icon" onclick="changeDate('next')">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <div>
            <input type="date" class="date-picker" id="datePicker" value="<?php echo $current_date; ?>" onchange="goToDate(this.value)">
            <button class="btn-link" onclick="goToToday()">
                <i class="fas fa-calendar-day"></i> Today
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards" id="summaryCards">
        <div class="summary-card" onclick="filterByStatus('all')">
            <div class="summary-icon bg-primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="summary-content">
                <h3 id="totalEmployees">0</h3>
                <p>Total Employees</p>
            </div>
        </div>
        
        <div class="summary-card" onclick="filterByStatus('present')">
            <div class="summary-icon bg-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="summary-content">
                <h3 id="presentCount">0</h3>
                <p>Present</p>
            </div>
        </div>
        
        <div class="summary-card" onclick="filterByStatus('late')">
            <div class="summary-icon bg-warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="summary-content">
                <h3 id="lateCount">0</h3>
                <p>Late</p>
            </div>
        </div>
        
        <div class="summary-card" onclick="filterByStatus('completed')">
            <div class="summary-icon bg-info">
                <i class="fas fa-check-double"></i>
            </div>
            <div class="summary-content">
                <h3 id="completedCount">0</h3>
                <p>Completed</p>
            </div>
        </div>
        
        <div class="summary-card" onclick="filterByStatus('absent')">
            <div class="summary-icon bg-danger">
                <i class="fas fa-calendar-times"></i>
            </div>
            <div class="summary-content">
                <h3 id="absentCount">0</h3>
                <p>Absent</p>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <select class="filter-select" id="officeFilter" onchange="filterByOffice()">
            <option value="">All Offices</option>
            <?php
            if ($offices) {
                while ($office = $offices->fetch_assoc()) {
                    echo "<option value='" . htmlspecialchars($office['office_name']) . "'>" . htmlspecialchars($office['office_name']) . "</option>";
                }
            }
            ?>
        </select>
        <input type="text" class="search-box" id="searchEmployee" placeholder="Search employee by name or ID..." onkeyup="searchEmployees()">
    </div>

    <!-- Employee Grid -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-users"></i> Employee Attendance - <?php echo date('F d, Y', strtotime($current_date)); ?></h3>
            <span id="employeeCount">0 employees</span>
        </div>
        <div class="card-body">
            <div class="employee-grid" id="employeeGrid">
                <div class="empty-state">
                    <div class="spinner"></div>
                    <p>Loading employees...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Time In Modal -->
<div class="modal" id="timeInModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5><i class="fas fa-sign-in-alt" style="color: #27ae60;"></i> Time In</h5>
                <button type="button" class="btn-close" onclick="closeModal('timeInModal')">&times;</button>
            </div>
            <div class="modal-body" id="timeInModalContent">
                <!-- Content will be filled dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('timeInModal')">Cancel</button>
                <button type="button" class="btn-success" onclick="confirmTimeIn()">Confirm Time In</button>
            </div>
        </div>
    </div>
</div>

<!-- Time Out Modal -->
<div class="modal" id="timeOutModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5><i class="fas fa-sign-out-alt" style="color: #e74c3c;"></i> Time Out</h5>
                <button type="button" class="btn-close" onclick="closeModal('timeOutModal')">&times;</button>
            </div>
            <div class="modal-body" id="timeOutModalContent">
                <!-- Content will be filled dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal('timeOutModal')">Cancel</button>
                <button type="button" class="btn-danger" onclick="confirmTimeOut()">Confirm Time Out</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentDate = '<?php echo $current_date; ?>';
let currentStatus = 'all';
let currentOffice = '';
let searchTerm = '';
let employees = [];
let selectedEmployeeId = null;
let selectedEmployeeName = '';

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadSummary();
    loadEmployees();
});

function loadSummary() {
    fetch('daily_time_record.php?ajax=get_attendance_summary&date=' + currentDate)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalEmployees').textContent = data.data.total_employees || 0;
                document.getElementById('presentCount').textContent = data.data.present || 0;
                document.getElementById('lateCount').textContent = data.data.late || 0;
                document.getElementById('completedCount').textContent = data.data.completed || 0;
                document.getElementById('absentCount').textContent = data.data.absent || 0;
            }
        })
        .catch(error => console.error('Error:', error));
}

function loadEmployees() {
    fetch('daily_time_record.php?ajax=get_employees_today&date=' + currentDate)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                employees = data.data;
                filterAndDisplayEmployees();
            }
        })
        .catch(error => console.error('Error:', error));
}

function filterAndDisplayEmployees() {
    let filtered = employees.filter(emp => {
        if (currentStatus !== 'all' && emp.attendance_status !== currentStatus) return false;
        if (currentOffice && emp.office_name !== currentOffice) return false;
        if (searchTerm) {
            const term = searchTerm.toLowerCase();
            const fullName = (emp.first_name + ' ' + emp.last_name).toLowerCase();
            const empId = (emp.employee_id || '').toLowerCase();
            if (!fullName.includes(term) && !empId.includes(term)) return false;
        }
        return true;
    });
    
    displayEmployees(filtered);
    document.getElementById('employeeCount').textContent = filtered.length + ' employees';
}

function displayEmployees(filtered) {
    const grid = document.getElementById('employeeGrid');
    
    if (filtered.length === 0) {
        grid.innerHTML = '<div class="empty-state"><i class="fas fa-users"></i><p>No employees found</p></div>';
        return;
    }
    
    let html = '';
    filtered.forEach(emp => {
        const status = emp.attendance_status;
        const statusText = status.charAt(0).toUpperCase() + status.slice(1);
        const initials = (emp.first_name.charAt(0) + emp.last_name.charAt(0)).toUpperCase();
        
        const avatarHtml = emp.profile_picture 
            ? `<img src="uploads/profiles/${emp.profile_picture}" alt="${emp.first_name}">`
            : `<div class="avatar-placeholder">${initials}</div>`;
        
        const timeInFormatted = emp.time_in ? formatTime(emp.time_in) : '--:-- AM';
        const timeOutFormatted = emp.time_out ? formatTime(emp.time_out) : '--:-- AM';
        
        html += `
            <div class="employee-card ${status}">
                <div class="employee-card-header">
                    <span class="employee-status-badge status-${status}">${statusText}</span>
                    <div class="employee-avatar">
                        ${avatarHtml}
                    </div>
                    <h4 class="employee-name">${emp.first_name} ${emp.last_name}</h4>
                    <div class="employee-details">${emp.position || 'Employee'}</div>
                    <div class="employee-id">${emp.employee_id || ''}</div>
                </div>
                <div class="employee-card-body">
                    <div class="time-info">
                        <span class="time-label"><i class="fas fa-sign-in-alt" style="color: #27ae60;"></i> Time In:</span>
                        <span class="time-value">${timeInFormatted}</span>
                    </div>
                    <div class="time-info">
                        <span class="time-label"><i class="fas fa-sign-out-alt" style="color: #e74c3c;"></i> Time Out:</span>
                        <span class="time-value">${timeOutFormatted}</span>
                    </div>
                    ${emp.total_hours ? `
                    <div class="time-info">
                        <span class="time-label"><i class="fas fa-clock"></i> Total:</span>
                        <span class="time-value">${emp.total_hours.toFixed(1)} hrs</span>
                    </div>
                    ` : ''}
                </div>
                <div class="employee-card-footer">
                    ${!emp.time_in ? 
                        `<button class="btn-card btn-card-in" onclick="openTimeIn(${emp.id}, '${emp.first_name} ${emp.last_name}')">
                            <i class="fas fa-sign-in-alt"></i> Time In
                        </button>` : 
                        (!emp.time_out ? 
                            `<button class="btn-card btn-card-out" onclick="openTimeOut(${emp.id}, '${emp.first_name} ${emp.last_name}')">
                                <i class="fas fa-sign-out-alt"></i> Time Out
                            </button>` : 
                            `<button class="btn-card btn-card-view" disabled>
                                <i class="fas fa-check"></i> Completed
                            </button>`
                        )
                    }
                </div>
            </div>
        `;
    });
    
    grid.innerHTML = html;
}

function formatTime(time) {
    if (!time) return '--:-- AM';
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}

function filterByStatus(status) {
    currentStatus = status;
    
    // Update active class on summary cards
    document.querySelectorAll('.summary-card').forEach((card, index) => {
        card.classList.remove('active');
        if (status === 'all' && index === 0) card.classList.add('active');
        else if (status === 'present' && index === 1) card.classList.add('active');
        else if (status === 'late' && index === 2) card.classList.add('active');
        else if (status === 'completed' && index === 3) card.classList.add('active');
        else if (status === 'absent' && index === 4) card.classList.add('active');
    });
    
    filterAndDisplayEmployees();
}

function filterByOffice() {
    currentOffice = document.getElementById('officeFilter').value;
    filterAndDisplayEmployees();
}

function searchEmployees() {
    searchTerm = document.getElementById('searchEmployee').value;
    filterAndDisplayEmployees();
}

function changeDate(direction) {
    const date = new Date(currentDate);
    date.setDate(date.getDate() + (direction === 'next' ? 1 : -1));
    currentDate = date.toISOString().split('T')[0];
    updateDateDisplay();
    loadSummary();
    loadEmployees();
}

function goToDate(date) {
    currentDate = date;
    updateDateDisplay();
    loadSummary();
    loadEmployees();
}

function goToToday() {
    const today = new Date();
    currentDate = today.toISOString().split('T')[0];
    updateDateDisplay();
    loadSummary();
    loadEmployees();
}

function updateDateDisplay() {
    document.getElementById('dateDisplay').textContent = new Date(currentDate).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    document.getElementById('datePicker').value = currentDate;
}

function refreshDashboard() {
    loadSummary();
    loadEmployees();
}

function openTimeIn(id, name) {
    console.log('Time In clicked for:', id, name);
    selectedEmployeeId = id;
    selectedEmployeeName = name;
    
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const timeStr = hours + ':' + minutes;
    
    document.getElementById('timeInModalContent').innerHTML = `
        <div style="text-align: center; margin-bottom: 20px;">
            <h4 style="color: #2c3e50; margin: 0 0 5px;">${name}</h4>
            <p style="color: #7f8c8d;">Employee ID: ${id}</p>
            <p style="color: #7f8c8d;">${new Date(currentDate).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}</p>
        </div>
        <div class="form-group">
            <label>Time In</label>
            <input type="time" class="form-control" id="timeInValue" value="${timeStr}" readonly>
        </div>
        <div class="form-group">
            <label>Remarks (Optional)</label>
            <textarea class="form-control" id="timeInRemarks" rows="3" placeholder="Any notes about this time in..."></textarea>
        </div>
        <div class="alert-info">
            <i class="fas fa-info-circle"></i> This action will be recorded in the system logs.
        </div>
    `;
    
    document.getElementById('timeInModal').classList.add('show');
}

function openTimeOut(id, name) {
    console.log('Time Out clicked for:', id, name);
    selectedEmployeeId = id;
    selectedEmployeeName = name;
    
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const timeStr = hours + ':' + minutes;
    
    document.getElementById('timeOutModalContent').innerHTML = `
        <div style="text-align: center; margin-bottom: 20px;">
            <h4 style="color: #2c3e50; margin: 0 0 5px;">${name}</h4>
            <p style="color: #7f8c8d;">Employee ID: ${id}</p>
            <p style="color: #7f8c8d;">${new Date(currentDate).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}</p>
        </div>
        <div class="form-group">
            <label>Time Out</label>
            <input type="time" class="form-control" id="timeOutValue" value="${timeStr}" readonly>
        </div>
        <div class="form-group">
            <label>Remarks (Optional)</label>
            <textarea class="form-control" id="timeOutRemarks" rows="3" placeholder="Any notes about this time out..."></textarea>
        </div>
        <div class="alert-info">
            <i class="fas fa-info-circle"></i> Total hours will be calculated automatically.
        </div>
    `;
    
    document.getElementById('timeOutModal').classList.add('show');
}

function confirmTimeIn() {
    if (!selectedEmployeeId) {
        alert('No employee selected');
        closeModal('timeInModal');
        return;
    }
    
    const timeInValue = document.getElementById('timeInValue')?.value;
    if (!timeInValue) {
        alert('Time is required');
        return;
    }
    
    const confirmBtn = document.querySelector('#timeInModal .btn-success');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    const formData = new FormData();
    formData.append('action', 'time_in');
    formData.append('user_id', selectedEmployeeId);
    formData.append('date', currentDate);
    formData.append('time_in', timeInValue);
    formData.append('remarks', document.getElementById('timeInRemarks')?.value || '');
    
    fetch('daily_time_record.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            closeModal('timeInModal');
            loadSummary();
            loadEmployees();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    })
    .finally(() => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
    });
}

function confirmTimeOut() {
    if (!selectedEmployeeId) {
        alert('No employee selected');
        closeModal('timeOutModal');
        return;
    }
    
    const timeOutValue = document.getElementById('timeOutValue')?.value;
    if (!timeOutValue) {
        alert('Time is required');
        return;
    }
    
    const confirmBtn = document.querySelector('#timeOutModal .btn-danger');
    const originalText = confirmBtn.innerHTML;
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    const formData = new FormData();
    formData.append('action', 'time_out');
    formData.append('user_id', selectedEmployeeId);
    formData.append('date', currentDate);
    formData.append('time_out', timeOutValue);
    formData.append('remarks', document.getElementById('timeOutRemarks')?.value || '');
    
    fetch('daily_time_record.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            closeModal('timeOutModal');
            loadSummary();
            loadEmployees();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    })
    .finally(() => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalText;
    });
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
    selectedEmployeeId = null;
    selectedEmployeeName = '';
}

// Close modals on outside click
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
        selectedEmployeeId = null;
        selectedEmployeeName = '';
    }
});

// Escape key to close modals
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.show').forEach(modal => {
            modal.classList.remove('show');
            selectedEmployeeId = null;
            selectedEmployeeName = '';
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>