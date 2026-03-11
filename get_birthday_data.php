<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();

// Get all birthdays with age calculation
$query = "
    SELECT 
        u.id,
        u.first_name,
        u.last_name,
        u.birth_date,
        u.profile_picture,
        u.email,
        u.contact_number,
        o.office_name,
        ee.position,
        TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) as current_age,
        DATE_FORMAT(u.birth_date, '%M %d') as birthday_formatted,
        DATE_FORMAT(u.birth_date, '%Y') as birth_year,
        DATE_FORMAT(u.birth_date, '%W') as day_of_week,
        DATEDIFF(
            DATE_ADD(
                u.birth_date, 
                INTERVAL TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) + 
                CASE 
                    WHEN DATE_ADD(u.birth_date, INTERVAL TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) YEAR) < CURDATE() 
                    THEN 1 ELSE 0 
                END YEAR
            ),
            CURDATE()
        ) as days_until_birthday,
        TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) + 1 as next_age,
        CASE 
            WHEN DATEDIFF(
                DATE_ADD(
                    u.birth_date, 
                    INTERVAL TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) + 
                    CASE 
                        WHEN DATE_ADD(u.birth_date, INTERVAL TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) YEAR) < CURDATE() 
                        THEN 1 ELSE 0 
                    END YEAR
                ),
                CURDATE()
            ) = 0 THEN 'today'
            WHEN DATEDIFF(
                DATE_ADD(
                    u.birth_date, 
                    INTERVAL TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) + 
                    CASE 
                        WHEN DATE_ADD(u.birth_date, INTERVAL TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) YEAR) < CURDATE() 
                        THEN 1 ELSE 0 
                    END YEAR
                ),
                CURDATE()
            ) BETWEEN 1 AND 7 THEN 'week'
            WHEN MONTH(u.birth_date) = MONTH(CURDATE()) AND YEAR(u.birth_date) <= YEAR(CURDATE()) THEN 'month'
            ELSE 'upcoming'
        END as category
    FROM users u
    LEFT JOIN employee_employment ee ON u.id = ee.user_id
    LEFT JOIN offices o ON ee.office_id = o.id
    WHERE u.role = 'employee' 
    AND u.birth_date IS NOT NULL
    ORDER BY 
        CASE 
            WHEN category = 'today' THEN 1
            WHEN category = 'week' THEN 2
            WHEN category = 'month' THEN 3
            ELSE 4
        END,
        days_until_birthday ASC
";

$result = $db->query($query);
$all_birthdays = [];
$birthdays_by_category = ['today' => [], 'week' => [], 'month' => [], 'upcoming' => []];

while ($row = $result->fetch_assoc()) {
    $all_birthdays[] = $row;
    $birthdays_by_category[$row['category']][] = $row;
}
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

        .birthday-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Summary Cards */
        .birthday-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .summary-icon {
            width: 55px;
            height: 55px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .summary-details {
            flex: 1;
        }

        .summary-value {
            display: block;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 3px;
        }

        .summary-label {
            display: block;
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Category Tabs */
        .category-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .category-tab {
            padding: 10px 20px;
            border: none;
            background: #f8f9fa;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .category-tab i {
            font-size: 14px;
        }

        .category-tab:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .category-tab.active {
            background: #3498db;
            color: white;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
        }

        .category-tab.active i {
            color: white;
        }

        .tab-count {
            background: rgba(0,0,0,0.1);
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            margin-left: 5px;
        }

        .category-tab.active .tab-count {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        /* Category Sections */
        .category-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .category-title {
            margin: 0 0 20px 0;
            font-size: 18px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3498db;
        }

        .category-title i {
            font-size: 20px;
        }

        .category-count {
            background: #3498db;
            color: white;
            padding: 3px 12px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        /* Birthday Grid */
        .birthday-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .birthday-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            gap: 20px;
            transition: all 0.3s;
            border: 1px solid #f0f0f0;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .birthday-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            background: white;
            border-color: #3498db;
        }

        .birthday-card.today {
            background: linear-gradient(135deg, #ff6b6b, #ee5253);
            color: white;
            border: none;
        }

        .birthday-card.today .birthday-info p,
        .birthday-card.today .birthday-info small,
        .birthday-card.today .birthday-date-info {
            color: rgba(255,255,255,0.9);
        }

        .birthday-card.today .birthday-date-info i {
            color: rgba(255,255,255,0.9);
        }

        .birthday-avatar {
            width: 80px;
            height: 80px;
            min-width: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            font-weight: 600;
            overflow: hidden;
            border: 3px solid white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .birthday-card.today .birthday-avatar {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.5);
        }

        .birthday-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .birthday-info {
            flex: 1;
        }

        .birthday-info h4 {
            margin: 0 0 8px 0;
            font-size: 18px;
            color: #333;
            font-weight: 600;
        }

        .birthday-card.today .birthday-info h4 {
            color: white;
        }

        .birthday-position {
            margin: 0 0 5px 0;
            font-size: 13px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .birthday-department {
            margin: 0 0 10px 0;
            font-size: 12px;
            color: #999;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .birthday-date-info {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
            font-size: 13px;
            color: #666;
            flex-wrap: wrap;
        }

        .birthday-date-info span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .birthday-date-info i {
            width: 16px;
            color: #3498db;
            font-size: 12px;
        }

        .birthday-age-badge {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 4px 12px;
            border-radius: 25px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 8px;
        }

        .birthday-card.today .birthday-age-badge {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .birthday-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 25px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 8px;
        }

        .today-badge {
            background: #ffd700;
            color: #333;
        }

        .week-badge {
            background: #f39c12;
            color: white;
        }

        .month-badge {
            background: #3498db;
            color: white;
        }

        .upcoming-badge {
            background: #9b59b6;
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .empty-state i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            margin: 0 0 10px;
            color: #666;
            font-size: 18px;
        }

        .empty-state p {
            margin: 0;
            font-size: 14px;
        }

        /* Loading Spinner */
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

        /* Responsive */
        @media (max-width: 1024px) {
            .birthday-summary {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .birthday-container {
                padding: 10px;
            }
            
            .birthday-grid {
                grid-template-columns: 1fr;
            }
            
            .category-tabs {
                justify-content: center;
            }
            
            .birthday-card {
                padding: 15px;
            }
            
            .birthday-avatar {
                width: 60px;
                height: 60px;
                min-width: 60px;
                font-size: 22px;
            }
        }

        @media (max-width: 480px) {
            .birthday-summary {
                grid-template-columns: 1fr;
            }
            
            .birthday-date-info {
                flex-direction: column;
                gap: 5px;
            }
            
            .category-tab {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="birthday-container">
        <!-- Summary Cards (Clickable) -->
        <div class="birthday-summary">
            <div class="summary-card" style="background: linear-gradient(135deg, #ff6b6b, #ee5253);" onclick="showCategory('today')">
                <div class="summary-icon">
                    <i class="fas fa-birthday-cake"></i>
                </div>
                <div class="summary-details">
                    <span class="summary-value"><?php echo count($birthdays_by_category['today']); ?></span>
                    <span class="summary-label">Today's Birthdays</span>
                </div>
            </div>
            <div class="summary-card" style="background: linear-gradient(135deg, #feca57, #ff9f43);" onclick="showCategory('week')">
                <div class="summary-icon">
                    <i class="fas fa-calendar-week"></i>
                </div>
                <div class="summary-details">
                    <span class="summary-value"><?php echo count($birthdays_by_category['week']); ?></span>
                    <span class="summary-label">This Week</span>
                </div>
            </div>
            <div class="summary-card" style="background: linear-gradient(135deg, #54a0ff, #2e86de);" onclick="showCategory('month')">
                <div class="summary-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="summary-details">
                    <span class="summary-value"><?php echo count($birthdays_by_category['month']); ?></span>
                    <span class="summary-label">This Month</span>
                </div>
            </div>
            <div class="summary-card" style="background: linear-gradient(135deg, #5f27cd, #341f97);" onclick="showCategory('upcoming')">
                <div class="summary-icon">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="summary-details">
                    <span class="summary-value"><?php echo count($birthdays_by_category['upcoming']); ?></span>
                    <span class="summary-label">Upcoming</span>
                </div>
            </div>
        </div>

        <!-- Category Tabs -->
        <div class="category-tabs">
            <button class="category-tab active" onclick="showCategory('today')" id="tab-today">
                <i class="fas fa-birthday-cake"></i>
                Today
                <span class="tab-count"><?php echo count($birthdays_by_category['today']); ?></span>
            </button>
            <button class="category-tab" onclick="showCategory('week')" id="tab-week">
                <i class="fas fa-calendar-week"></i>
                This Week
                <span class="tab-count"><?php echo count($birthdays_by_category['week']); ?></span>
            </button>
            <button class="category-tab" onclick="showCategory('month')" id="tab-month">
                <i class="fas fa-calendar-alt"></i>
                This Month
                <span class="tab-count"><?php echo count($birthdays_by_category['month']); ?></span>
            </button>
            <button class="category-tab" onclick="showCategory('upcoming')" id="tab-upcoming">
                <i class="fas fa-calendar"></i>
                Upcoming
                <span class="tab-count"><?php echo count($birthdays_by_category['upcoming']); ?></span>
            </button>
        </div>

        <!-- Today's Birthdays -->
        <div id="today-category" class="category-section">
            <h4 class="category-title">
                <i class="fas fa-birthday-cake" style="color: #ff6b6b;"></i>
                Today's Birthdays
                <span class="category-count"><?php echo count($birthdays_by_category['today']); ?></span>
            </h4>
            <?php if (!empty($birthdays_by_category['today'])): ?>
            <div class="birthday-grid">
                <?php foreach ($birthdays_by_category['today'] as $bday): ?>
                <div class="birthday-card today" onclick="parent.viewEmployeeBirthday(<?php echo $bday['id']; ?>)">
                    <div class="birthday-avatar">
                        <?php if (!empty($bday['profile_picture'])): ?>
                            <img src="uploads/profiles/<?php echo $bday['profile_picture']; ?>" alt="Profile">
                        <?php else: ?>
                            <?php echo strtoupper(substr($bday['first_name'], 0, 1) . substr($bday['last_name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="birthday-info">
                        <h4><?php echo htmlspecialchars($bday['first_name'] . ' ' . $bday['last_name']); ?></h4>
                        <p class="birthday-position">
                            <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($bday['position'] ?? 'Employee'); ?>
                        </p>
                        <p class="birthday-department">
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($bday['office_name'] ?? 'No Department'); ?>
                        </p>
                        <div class="birthday-date-info">
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('F d', strtotime($bday['birth_date'])); ?></span>
                            <span><i class="fas fa-birthday-cake"></i> Current Age: <?php echo $bday['current_age']; ?></span>
                        </div>
                        <span class="birthday-badge today-badge">
                            <i class="fas fa-gift"></i> Turning <?php echo $bday['next_age']; ?> Today!
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-birthday-cake"></i>
                <h3>No Birthdays Today</h3>
                <p>Check back tomorrow for more celebrations!</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- This Week's Birthdays -->
        <div id="week-category" class="category-section" style="display: none;">
            <h4 class="category-title">
                <i class="fas fa-calendar-week" style="color: #f39c12;"></i>
                This Week's Birthdays
                <span class="category-count"><?php echo count($birthdays_by_category['week']); ?></span>
            </h4>
            <?php if (!empty($birthdays_by_category['week'])): ?>
            <div class="birthday-grid">
                <?php foreach ($birthdays_by_category['week'] as $bday): ?>
                <div class="birthday-card" onclick="parent.viewEmployeeBirthday(<?php echo $bday['id']; ?>)">
                    <div class="birthday-avatar">
                        <?php if (!empty($bday['profile_picture'])): ?>
                            <img src="uploads/profiles/<?php echo $bday['profile_picture']; ?>" alt="Profile">
                        <?php else: ?>
                            <?php echo strtoupper(substr($bday['first_name'], 0, 1) . substr($bday['last_name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="birthday-info">
                        <h4><?php echo htmlspecialchars($bday['first_name'] . ' ' . $bday['last_name']); ?></h4>
                        <p class="birthday-position">
                            <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($bday['position'] ?? 'Employee'); ?>
                        </p>
                        <p class="birthday-department">
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($bday['office_name'] ?? 'No Department'); ?>
                        </p>
                        <div class="birthday-date-info">
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('F d', strtotime($bday['birth_date'])); ?></span>
                            <span><i class="fas fa-birthday-cake"></i> Current Age: <?php echo $bday['current_age']; ?></span>
                            <span><i class="fas fa-clock"></i> In <?php echo $bday['days_until_birthday']; ?> days</span>
                        </div>
                        <span class="birthday-badge week-badge">
                            <i class="fas fa-gift"></i> Turning <?php echo $bday['next_age']; ?> on <?php echo date('M d', strtotime($bday['birth_date'])); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-week"></i>
                <h3>No Birthdays This Week</h3>
                <p>Check back next week for more celebrations!</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- This Month's Birthdays -->
        <div id="month-category" class="category-section" style="display: none;">
            <h4 class="category-title">
                <i class="fas fa-calendar-alt" style="color: #3498db;"></i>
                This Month's Birthdays
                <span class="category-count"><?php echo count($birthdays_by_category['month']); ?></span>
            </h4>
            <?php if (!empty($birthdays_by_category['month'])): ?>
            <div class="birthday-grid">
                <?php foreach ($birthdays_by_category['month'] as $bday): ?>
                <div class="birthday-card" onclick="parent.viewEmployeeBirthday(<?php echo $bday['id']; ?>)">
                    <div class="birthday-avatar">
                        <?php if (!empty($bday['profile_picture'])): ?>
                            <img src="uploads/profiles/<?php echo $bday['profile_picture']; ?>" alt="Profile">
                        <?php else: ?>
                            <?php echo strtoupper(substr($bday['first_name'], 0, 1) . substr($bday['last_name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="birthday-info">
                        <h4><?php echo htmlspecialchars($bday['first_name'] . ' ' . $bday['last_name']); ?></h4>
                        <p class="birthday-position">
                            <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($bday['position'] ?? 'Employee'); ?>
                        </p>
                        <p class="birthday-department">
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($bday['office_name'] ?? 'No Department'); ?>
                        </p>
                        <div class="birthday-date-info">
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('F d', strtotime($bday['birth_date'])); ?></span>
                            <span><i class="fas fa-birthday-cake"></i> Current Age: <?php echo $bday['current_age']; ?></span>
                        </div>
                        <span class="birthday-badge month-badge">
                            <i class="fas fa-gift"></i> Turning <?php echo $bday['next_age']; ?> this month
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <h3>No Birthdays This Month</h3>
                <p>Check back next month for more celebrations!</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Upcoming Birthdays -->
        <div id="upcoming-category" class="category-section" style="display: none;">
            <h4 class="category-title">
                <i class="fas fa-calendar" style="color: #9b59b6;"></i>
                Upcoming Birthdays
                <span class="category-count"><?php echo count($birthdays_by_category['upcoming']); ?></span>
            </h4>
            <?php if (!empty($birthdays_by_category['upcoming'])): ?>
            <div class="birthday-grid">
                <?php foreach ($birthdays_by_category['upcoming'] as $bday): ?>
                <div class="birthday-card" onclick="parent.viewEmployeeBirthday(<?php echo $bday['id']; ?>)">
                    <div class="birthday-avatar">
                        <?php if (!empty($bday['profile_picture'])): ?>
                            <img src="uploads/profiles/<?php echo $bday['profile_picture']; ?>" alt="Profile">
                        <?php else: ?>
                            <?php echo strtoupper(substr($bday['first_name'], 0, 1) . substr($bday['last_name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="birthday-info">
                        <h4><?php echo htmlspecialchars($bday['first_name'] . ' ' . $bday['last_name']); ?></h4>
                        <p class="birthday-position">
                            <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($bday['position'] ?? 'Employee'); ?>
                        </p>
                        <p class="birthday-department">
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($bday['office_name'] ?? 'No Department'); ?>
                        </p>
                        <div class="birthday-date-info">
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('F d', strtotime($bday['birth_date'])); ?></span>
                            <span><i class="fas fa-birthday-cake"></i> Current Age: <?php echo $bday['current_age']; ?></span>
                            <span><i class="fas fa-clock"></i> In <?php echo $bday['days_until_birthday']; ?> days</span>
                        </div>
                        <span class="birthday-badge upcoming-badge">
                            <i class="fas fa-gift"></i> Turning <?php echo $bday['next_age']; ?> on <?php echo date('M d', strtotime($bday['birth_date'])); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar"></i>
                <h3>No Upcoming Birthdays</h3>
                <p>All birthdays have been celebrated!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function showCategory(category) {
            // Hide all category sections
            document.getElementById('today-category').style.display = 'none';
            document.getElementById('week-category').style.display = 'none';
            document.getElementById('month-category').style.display = 'none';
            document.getElementById('upcoming-category').style.display = 'none';
            
            // Remove active class from all tabs
            document.getElementById('tab-today').classList.remove('active');
            document.getElementById('tab-week').classList.remove('active');
            document.getElementById('tab-month').classList.remove('active');
            document.getElementById('tab-upcoming').classList.remove('active');
            
            // Show selected category
            document.getElementById(category + '-category').style.display = 'block';
            
            // Add active class to selected tab
            document.getElementById('tab-' + category).classList.add('active');
        }

        // Function to handle employee birthday click (will be called from parent)
        function viewEmployeeBirthday(id) {
            if (window.parent && window.parent.viewEmployeeBirthday) {
                window.parent.viewEmployeeBirthday(id);
            } else {
                console.log('View employee birthday:', id);
            }
        }
    </script>
</body>
</html>