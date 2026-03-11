<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();
$user = $auth->getCurrentUser();

// Get current month and year from URL or use current
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('n'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Validate month and year
if ($month < 1 || $month > 12) $month = date('n');
if ($year < 2000 || $year > 2100) $year = date('Y');

// Get events for the selected month
$start_date = date('Y-m-01', strtotime("$year-$month-01"));
$end_date = date('Y-m-t', strtotime("$year-$month-01"));

$events_query = "
    SELECT 
        ce.*,
        CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
        DAY(ce.start_date) as day,
        DATE_FORMAT(ce.start_date, '%Y-%m-%d') as event_date,
        DATE_FORMAT(ce.start_time, '%h:%i %p') as formatted_time
    FROM calendar_events ce
    LEFT JOIN users u ON ce.created_by = u.id
    WHERE ce.status = 'active' 
    AND ce.start_date BETWEEN '$start_date' AND '$end_date'
    ORDER BY ce.start_date ASC, ce.start_time ASC
";

$events_result = $db->query($events_query);
$events_by_day = [];

while ($row = $events_result->fetch_assoc()) {
    $day = (int)date('j', strtotime($row['start_date']));
    if (!isset($events_by_day[$day])) {
        $events_by_day[$day] = [];
    }
    $events_by_day[$day][] = $row;
}

// Calculate calendar data
$days_in_month = date('t', strtotime("$year-$month-01"));
$first_day_of_month = date('w', strtotime("$year-$month-01")); // 0 = Sunday
$month_name = date('F Y', strtotime("$year-$month-01"));
$today_day = (int)date('j');
$today_month = (int)date('n');
$today_year = (int)date('Y');

// Previous and next month
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year = $year - 1;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year = $year + 1;
}

// Day names
$day_names = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

include 'includes/header.php';
?>

<style>
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

/* Welcome Banner */
.welcome-banner {
    background: linear-gradient(135deg, #1e2b3a 0%, #2c3e50 100%);
    border-radius: 10px;
    padding: 25px 30px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.welcome-content h1 {
    font-size: 24px;
    margin-bottom: 5px;
    font-weight: 500;
}

.welcome-content p {
    font-size: 13px;
    opacity: 0.8;
}

.btn-primary {
    background: rgba(255,255,255,0.15);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    border: none;
}

.btn-primary:hover {
    background: rgba(255,255,255,0.25);
    border-color: rgba(255,255,255,0.5);
}

/* Calendar Header */
.calendar-header {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.calendar-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.calendar-title h2 {
    margin: 0;
    font-size: 22px;
    color: #333;
    font-weight: 600;
}

.calendar-title i {
    color: #3498db;
    font-size: 24px;
}

.calendar-nav {
    display: flex;
    gap: 8px;
}

.nav-btn {
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 10px 15px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #666;
    text-decoration: none;
}

.nav-btn:hover {
    background: #3498db;
    color: white;
    border-color: #3498db;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
}

.nav-btn i {
    font-size: 12px;
}

.nav-btn.today-btn {
    background: #3498db;
    color: white;
    border-color: #3498db;
}

.nav-btn.today-btn:hover {
    background: #2980b9;
}

/* Calendar Stats */
.calendar-stats {
    background: white;
    border-radius: 10px;
    padding: 15px 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.stat-icon {
    width: 36px;
    height: 36px;
    background: #f0f7ff;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #3498db;
}

.stat-info {
    font-size: 13px;
}

.stat-label {
    color: #666;
    margin-bottom: 2px;
}

.stat-value {
    font-weight: 600;
    color: #333;
    font-size: 16px;
}

/* Calendar Grid */
.calendar-grid {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 8px;
}

.day-header {
    text-align: center;
    font-weight: 600;
    font-size: 13px;
    color: #666;
    padding: 12px 5px;
    background: #f8f9fa;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.calendar-day {
    border: 1px solid #f0f0f0;
    border-radius: 8px;
    min-height: 120px;
    padding: 10px;
    background: white;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

.calendar-day:hover {
    border-color: #3498db;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.calendar-day.today {
    background: #f0f7ff;
    border: 2px solid #3498db;
}

.calendar-day.other-month {
    background: #f8f9fa;
    opacity: 0.6;
}

.day-number {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.today .day-number {
    color: #3498db;
}

.today-badge {
    background: #3498db;
    color: white;
    font-size: 9px;
    padding: 2px 6px;
    border-radius: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.events-container {
    display: flex;
    flex-direction: column;
    gap: 4px;
    max-height: 80px;
    overflow-y: auto;
}

.events-container::-webkit-scrollbar {
    width: 3px;
}

.events-container::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.events-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.event-item {
    font-size: 10px;
    padding: 4px 6px;
    border-radius: 4px;
    color: white;
    cursor: pointer;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-decoration: none;
}

.event-item:hover {
    transform: translateX(2px);
    opacity: 0.9;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.event-item i {
    font-size: 8px;
}

.no-events {
    color: #ccc;
    font-size: 10px;
    text-align: center;
    padding: 8px 0;
    font-style: italic;
}

/* Legend */
.calendar-legend {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-top: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    align-items: center;
}

.legend-title {
    font-size: 13px;
    font-weight: 600;
    color: #666;
    display: flex;
    align-items: center;
    gap: 5px;
}

.legend-items {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #666;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 4px;
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
    z-index: 2000;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 10px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
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
    padding: 18px 20px;
    background: linear-gradient(135deg, #1e2b3a, #2c3e50);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h5 {
    margin: 0;
    font-size: 16px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-header h5 i {
    color: #3498db;
}

.modal-close {
    background: rgba(255,255,255,0.1);
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.modal-close:hover {
    background: rgba(255,255,255,0.2);
    transform: rotate(90deg);
}

.modal-body {
    padding: 20px;
    max-height: calc(90vh - 130px);
    overflow-y: auto;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    background: #f8f9fa;
}

/* Form Styles */
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
}

.form-control:focus {
    outline: none;
    border-color: #3498db;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

/* Alert */
.alert {
    padding: 10px 15px;
    border-radius: 4px;
    margin-bottom: 15px;
    font-size: 12px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px;
    color: #999;
}

.empty-state i {
    font-size: 48px;
    color: #ddd;
    margin-bottom: 15px;
}

/* Loading Spinner */
.loading-spinner {
    text-align: center;
    padding: 30px;
    color: #999;
}

.loading-spinner i {
    font-size: 30px;
    margin-bottom: 10px;
    color: #3498db;
}

/* Responsive */
@media (max-width: 768px) {
    .welcome-banner {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .calendar-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .calendar-nav {
        justify-content: space-between;
    }
    
    .calendar-grid {
        grid-template-columns: repeat(1, 1fr);
        gap: 10px;
    }
    
    .day-header {
        display: none;
    }
    
    .calendar-day {
        min-height: auto;
    }
    
    .day-number {
        margin-bottom: 5px;
    }
    
    .events-container {
        max-height: none;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="dashboard-container">
    <!-- Page Header -->
    <div class="welcome-banner">
        <div class="welcome-content">
            <h1><i class="fas fa-calendar-alt"></i> HR Calendar</h1>
            <p>View and manage all HR events</p>
        </div>
        <div class="welcome-actions">
            <button class="btn-primary" onclick="openAddEventModal()">
                <i class="fas fa-plus"></i> Add Event
            </button>
            <a href="export_events.php" class="btn-primary">
                <i class="fas fa-download"></i> Export
            </a>
        </div>
    </div>

    <!-- Calendar Header with Navigation -->
    <div class="calendar-header">
        <div class="calendar-title">
            <i class="fas fa-calendar-alt"></i>
            <h2><?php echo $month_name; ?></h2>
        </div>
        <div class="calendar-nav">
            <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="nav-btn">
                <i class="fas fa-chevron-left"></i>
                Previous
            </a>
            <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="nav-btn today-btn">
                <i class="fas fa-calendar"></i>
                Today
            </a>
            <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="nav-btn">
                Next
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>

    <!-- Calendar Stats -->
    <div class="calendar-stats">
        <div class="stat-item">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Total Events</div>
                <div class="stat-value"><?php echo array_sum(array_map('count', $events_by_day)); ?></div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Days with Events</div>
                <div class="stat-value"><?php echo count($events_by_day); ?></div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon">
                <i class="fas fa-calendar-week"></i>
            </div>
            <div class="stat-info">
                <div class="stat-label">Days in Month</div>
                <div class="stat-value"><?php echo $days_in_month; ?></div>
            </div>
        </div>
    </div>

    <!-- Day Headers -->
    <div class="calendar-grid">
        <?php foreach ($day_names as $name): ?>
        <div class="day-header"><?php echo $name; ?></div>
        <?php endforeach; ?>

        <!-- Empty cells for days before month starts -->
        <?php for ($i = 0; $i < $first_day_of_month; $i++): ?>
        <div class="calendar-day other-month"></div>
        <?php endfor; ?>

        <!-- Calendar Days -->
        <?php for ($day = 1; $day <= $days_in_month; $day++): 
            $is_today = ($day == $today_day && $month == $today_month && $year == $today_year);
            $day_events = isset($events_by_day[$day]) ? $events_by_day[$day] : [];
        ?>
        <div class="calendar-day <?php echo $is_today ? 'today' : ''; ?>">
            <div class="day-number">
                <?php echo $day; ?>
                <?php if ($is_today): ?>
                    <span class="today-badge">Today</span>
                <?php endif; ?>
            </div>
            
            <div class="events-container">
                <?php if (!empty($day_events)): ?>
                    <?php foreach ($day_events as $event): ?>
                    <a href="event_details.php?id=<?php echo $event['id']; ?>" class="event-item" 
                       style="background: <?php echo $event['color']; ?>;"
                       title="<?php echo htmlspecialchars($event['title']); ?>">
                        <i class="fas fa-circle"></i>
                        <?php echo htmlspecialchars(substr($event['title'], 0, 20)) . (strlen($event['title']) > 20 ? '...' : ''); ?>
                        <?php if ($event['start_time']): ?>
                            <span style="font-size: 8px; opacity: 0.9;"><?php echo $event['formatted_time']; ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-events">—</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endfor; ?>

        <!-- Empty cells for days after month ends -->
        <?php 
        $total_cells = $first_day_of_month + $days_in_month;
        $remaining_cells = 42 - $total_cells;
        if ($remaining_cells > 0) {
            for ($i = 0; $i < $remaining_cells; $i++): 
        ?>
        <div class="calendar-day other-month"></div>
        <?php 
            endfor;
        }
        ?>
    </div>

    <!-- Legend -->
    <div class="calendar-legend">
        <div class="legend-title">
            <i class="fas fa-palette"></i>
            Event Types:
        </div>
        <div class="legend-items">
            <div class="legend-item">
                <div class="legend-color" style="background: #3498db;"></div>
                <span>Meeting</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #27ae60;"></div>
                <span>Training</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #e67e22;"></div>
                <span>Payroll</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #e74c3c;"></div>
                <span>Holiday/Deadline</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #9b59b6;"></div>
                <span>Orientation</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #f1c40f;"></div>
                <span>Other</span>
            </div>
        </div>
    </div>
</div>

<!-- Add Event Modal -->
<div class="modal" id="addEventModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5><i class="fas fa-plus-circle"></i> Add Calendar Event</h5>
            <button class="modal-close" onclick="closeModal('addEventModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div id="modalAlert" style="display: none;"></div>
            <form id="eventForm">
                <div class="form-group">
                    <label for="event_title">Event Title *</label>
                    <input type="text" id="event_title" name="title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="event_description">Description</label>
                    <textarea id="event_description" name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_type">Event Type *</label>
                        <select id="event_type" name="event_type" class="form-control" required>
                            <option value="meeting">Meeting</option>
                            <option value="training">Training</option>
                            <option value="holiday">Holiday</option>
                            <option value="payroll">Payroll</option>
                            <option value="deadline">Deadline</option>
                            <option value="orientation">Orientation</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="event_venue">Venue</label>
                        <input type="text" id="event_venue" name="venue" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_start_date">Start Date *</label>
                        <input type="date" id="event_start_date" name="start_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="event_end_date">End Date</label>
                        <input type="date" id="event_end_date" name="end_date" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_start_time">Start Time</label>
                        <input type="time" id="event_start_time" name="start_time" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="event_end_time">End Time</label>
                        <input type="time" id="event_end_time" name="end_time" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_color">Color</label>
                        <select id="event_color" name="color" class="form-control">
                            <option value="#3498db">Blue</option>
                            <option value="#27ae60">Green</option>
                            <option value="#e67e22">Orange</option>
                            <option value="#e74c3c">Red</option>
                            <option value="#9b59b6">Purple</option>
                            <option value="#f1c40f">Yellow</option>
                            <option value="#95a5a6">Gray</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="event_reminder">Reminder (days before)</label>
                        <select id="event_reminder" name="reminder_days" class="form-control">
                            <option value="0">No reminder</option>
                            <option value="1">1 day before</option>
                            <option value="2">2 days before</option>
                            <option value="3">3 days before</option>
                            <option value="7">1 week before</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-primary" onclick="saveEvent()" style="background: #3498db; color: white;">
                <i class="fas fa-save"></i> Save Event
            </button>
            <button class="btn-primary" onclick="closeModal('addEventModal')" style="background: #95a5a6;">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    </div>
</div>

<script>
// Modal Functions
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
    // Reset form and hide alerts
    document.getElementById('eventForm').reset();
    document.getElementById('modalAlert').style.display = 'none';
}

function openAddEventModal() {
    const modal = document.getElementById('addEventModal');
    modal.classList.add('show');
    
    // Set default start date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('event_start_date').value = today;
}

function showAlert(message, type) {
    const alertDiv = document.getElementById('modalAlert');
    alertDiv.style.display = 'block';
    alertDiv.className = 'alert alert-' + type;
    alertDiv.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + message;
    
    // Auto hide after 3 seconds for success
    if (type === 'success') {
        setTimeout(() => {
            alertDiv.style.display = 'none';
        }, 3000);
    }
}

function saveEvent() {
    // Validate form
    const title = document.getElementById('event_title').value;
    const startDate = document.getElementById('event_start_date').value;
    
    if (!title || !startDate) {
        showAlert('Please fill in all required fields', 'error');
        return;
    }
    
    // Show loading state
    const saveBtn = event.target;
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    saveBtn.disabled = true;
    
    // Prepare form data
    const formData = new FormData(document.getElementById('eventForm'));
    formData.append('action', 'add_event');
    
    // Send AJAX request
    fetch('save_event.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Event saved successfully!', 'success');
            
            // Reset form and close modal after delay
            setTimeout(() => {
                closeModal('addEventModal');
                // Refresh the page to show new event
                location.reload();
            }, 1500);
        } else {
            showAlert('Error: ' + data.error, 'error');
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    })
    .catch(error => {
        showAlert('Error saving event. Please try again.', 'error');
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
        // Reset form and hide alerts
        document.getElementById('eventForm').reset();
        document.getElementById('modalAlert').style.display = 'none';
    }
});

// Keyboard shortcut: ESC to close modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('addEventModal');
        if (modal.classList.contains('show')) {
            closeModal('addEventModal');
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>