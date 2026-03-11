<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();

// Get current month and year from URL parameters or use current date
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('n'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Validate month and year
if ($month < 1 || $month > 12) $month = date('n');
if ($year < 2000 || $year > 2100) $year = date('Y');

// Get events for the selected month
$start_date = date('Y-m-01', strtotime("$year-$month-01"));
$end_date = date('Y-m-t', strtotime("$year-$month-01"));

$query = "
    SELECT 
        ce.*,
        CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
        DAY(ce.start_date) as day,
        DATE_FORMAT(ce.start_date, '%Y-%m-%d') as event_date,
        TIME_FORMAT(ce.start_time, '%h:%i %p') as formatted_time
    FROM calendar_events ce
    LEFT JOIN users u ON ce.created_by = u.id
    WHERE ce.status = 'active' 
    AND ce.start_date BETWEEN '$start_date' AND '$end_date'
    ORDER BY ce.start_date ASC, ce.start_time ASC
";

$result = $db->query($query);
$events_by_day = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $day = (int)date('j', strtotime($row['start_date']));
        if (!isset($events_by_day[$day])) {
            $events_by_day[$day] = [];
        }
        $events_by_day[$day][] = $row;
    }
}

// Calculate calendar data
$days_in_month = date('t', strtotime("$year-$month-01"));
$first_day_of_month = date('w', strtotime("$year-$month-01")); // 0 = Sunday, 6 = Saturday
$month_name = date('F Y', strtotime("$year-$month-01"));
$today_day = (int)date('j');
$today_month = (int)date('n');
$today_year = (int)date('Y');

// Calculate previous and next month
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
$full_day_names = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f8f9fa;
        }

        .calendar-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Calendar Header */
        .calendar-header {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #666;
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

        /* Calendar Grid */
        .calendar-grid {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
            border-radius: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .calendar-day {
            border: 1px solid #f0f0f0;
            border-radius: 10px;
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
        }

        .event-item:hover {
            transform: translateX(2px);
            opacity: 0.9;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .event-item i {
            font-size: 8px;
        }

        .event-time {
            font-size: 8px;
            opacity: 0.9;
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
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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

        /* Stats */
        .calendar-stats {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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

        /* Loading */
        .loading-spinner {
            text-align: center;
            padding: 60px;
            color: #999;
        }

        .loading-spinner i {
            font-size: 40px;
            margin-bottom: 15px;
            color: #3498db;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px;
            color: #999;
            background: white;
            border-radius: 12px;
        }

        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .calendar-container {
                padding: 10px;
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
        }
    </style>
</head>
<body>
    <div class="calendar-container">
        <!-- Calendar Header with Navigation -->
        <div class="calendar-header">
            <div class="calendar-title">
                <i class="fas fa-calendar-alt"></i>
                <h2><?php echo $month_name; ?></h2>
            </div>
            <div class="calendar-nav">
                <button class="nav-btn" onclick="loadCalendar(<?php echo $prev_month; ?>, <?php echo $prev_year; ?>)">
                    <i class="fas fa-chevron-left"></i>
                    Previous
                </button>
                <button class="nav-btn today-btn" onclick="loadCalendar(<?php echo date('n'); ?>, <?php echo date('Y'); ?>)">
                    <i class="fas fa-calendar"></i>
                    Today
                </button>
                <button class="nav-btn" onclick="loadCalendar(<?php echo $next_month; ?>, <?php echo $next_year; ?>)">
                    Next
                    <i class="fas fa-chevron-right"></i>
                </button>
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
                        <div class="event-item" 
                             style="background: <?php echo $event['color']; ?>;"
                             onclick="parent.viewEventDetails(<?php echo $event['id']; ?>)"
                             title="<?php echo htmlspecialchars($event['title']); ?>">
                            <i class="fas fa-circle"></i>
                            <?php echo htmlspecialchars(substr($event['title'], 0, 15)) . (strlen($event['title']) > 15 ? '...' : ''); ?>
                            <?php if ($event['start_time']): ?>
                                <span class="event-time"><?php echo $event['formatted_time']; ?></span>
                            <?php endif; ?>
                        </div>
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
            $remaining_cells = 42 - $total_cells; // Fill to 42 cells (6 rows)
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

    <script>
        function loadCalendar(month, year) {
            // Show loading state
            const modalBody = window.parent.document.getElementById('calendarModalBody');
            modalBody.innerHTML = `
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading calendar...</p>
                </div>
            `;
            
            // Fetch new calendar data
            fetch('get_calendar_data.php?month=' + month + '&year=' + year)
                .then(response => response.text())
                .then(html => {
                    modalBody.innerHTML = html;
                })
                .catch(error => {
                    modalBody.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-exclamation-circle" style="color: #e74c3c;"></i>
                            <h3>Error Loading Calendar</h3>
                            <p>Please try again</p>
                            <button onclick="loadCalendar(${month}, ${year})" style="margin-top: 15px; padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                Retry
                            </button>
                        </div>
                    `;
                });
        }

        // Make viewEventDetails available to parent
        function viewEventDetails(id) {
            if (window.parent && window.parent.viewEventDetails) {
                window.parent.viewEventDetails(id);
            }
        }
    </script>
</body>
</html>