<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

$db = getDB();

// Get all birthdays with age calculation and categories
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
}

.btn-primary:hover {
    background: rgba(255,255,255,0.25);
    border-color: rgba(255,255,255,0.5);
}

/* Stats Row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    display: flex;
    align-items: flex-start;
    gap: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    transition: transform 0.3s, box-shadow 0.3s;
    cursor: pointer;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 24px;
    font-weight: 600;
    color: #333;
    line-height: 1.2;
    margin-bottom: 3px;
}

.stat-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
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
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
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
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
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
    border-radius: 10px;
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
    max-width: 1000px;
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

/* Greeting Card Styles */
.greeting-cards-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    padding: 10px;
}

.greeting-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    page-break-inside: avoid;
    break-inside: avoid;
}

.greeting-card-header {
    background: linear-gradient(135deg, #ff6b6b, #ee5253);
    color: white;
    padding: 30px 20px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.greeting-card-header::before {
    content: '🎂';
    position: absolute;
    top: -20px;
    right: -20px;
    font-size: 100px;
    opacity: 0.2;
    transform: rotate(15deg);
}

.greeting-card-header::after {
    content: '🎈';
    position: absolute;
    bottom: -20px;
    left: -20px;
    font-size: 80px;
    opacity: 0.2;
    transform: rotate(-15deg);
}

.greeting-card-header h3 {
    margin: 0 0 10px;
    font-size: 28px;
    font-weight: 700;
    position: relative;
    z-index: 1;
}

.greeting-card-header p {
    margin: 0;
    font-size: 16px;
    opacity: 0.9;
    position: relative;
    z-index: 1;
}

.greeting-card-body {
    padding: 25px;
    text-align: center;
}

.greeting-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    margin: -50px auto 15px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 40px;
    font-weight: 600;
    border: 4px solid white;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    position: relative;
    z-index: 2;
    overflow: hidden;
}

.greeting-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.greeting-name {
    font-size: 24px;
    font-weight: 700;
    color: #333;
    margin: 10px 0 5px;
}

.greeting-position {
    font-size: 14px;
    color: #666;
    margin-bottom: 15px;
}

.greeting-message {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
    font-size: 18px;
    font-style: italic;
    color: #555;
    line-height: 1.6;
    border-left: 4px solid #ff6b6b;
}

.greeting-details {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin: 20px 0;
    color: #666;
}

.greeting-details i {
    color: #ff6b6b;
    margin-right: 5px;
}

.greeting-footer {
    border-top: 2px dashed #ddd;
    padding-top: 20px;
    text-align: center;
    color: #999;
    font-size: 12px;
}

.greeting-signature {
    font-family: 'Brush Script MT', cursive;
    font-size: 28px;
    color: #ff6b6b;
    margin-top: 10px;
}

/* Print Styles */
@media print {
    .no-print {
        display: none !important;
    }
    
    .greeting-cards-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        padding: 10px;
    }
    
    .greeting-card {
        box-shadow: none;
        border: 1px solid #ddd;
        page-break-inside: avoid;
    }
    
    .greeting-card-header {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .greeting-avatar {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}

/* Card Color Variations */
.greeting-card.today-template .greeting-card-header {
    background: linear-gradient(135deg, #ff6b6b, #ee5253);
}

.greeting-card.week-template .greeting-card-header {
    background: linear-gradient(135deg, #feca57, #ff9f43);
}

.greeting-card.month-template .greeting-card-header {
    background: linear-gradient(135deg, #54a0ff, #2e86de);
}

.greeting-card.upcoming-template .greeting-card-header {
    background: linear-gradient(135deg, #5f27cd, #341f97);
}

/* Responsive */
@media (max-width: 1024px) {
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .greeting-cards-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .welcome-banner {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .stats-row {
        grid-template-columns: 1fr;
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
    
    .greeting-cards-grid {
        grid-template-columns: 1fr;
    }
    
    .greeting-details {
        flex-direction: column;
        gap: 10px;
    }
}

@media (max-width: 480px) {
    .stats-row {
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

<div class="dashboard-container">
    <!-- Page Header -->
    <div class="welcome-banner">
        <div class="welcome-content">
            <h1><i class="fas fa-birthday-cake"></i> Employee Birthdays</h1>
            <p>View and manage all employee birthdays</p>
        </div>
        <div class="welcome-actions">
            <button class="btn-primary" onclick="exportBirthdays()">
                <i class="fas fa-download"></i> Export List
            </button>
            <button class="btn-primary" onclick="openGreetingCards()">
                <i class="fas fa-gift"></i> Greeting Cards
            </button>
            <button class="btn-primary" onclick="printBirthdays()">
                <i class="fas fa-print"></i> Print List
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="stats-row">
        <div class="stat-card" onclick="showCategory('today')">
            <div class="stat-icon" style="background: #ff6b6b20; color: #ff6b6b;">
                <i class="fas fa-birthday-cake"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo count($birthdays_by_category['today']); ?></div>
                <div class="stat-label">Today</div>
            </div>
        </div>
        <div class="stat-card" onclick="showCategory('week')">
            <div class="stat-icon" style="background: #feca5720; color: #f39c12;">
                <i class="fas fa-calendar-week"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo count($birthdays_by_category['week']); ?></div>
                <div class="stat-label">This Week</div>
            </div>
        </div>
        <div class="stat-card" onclick="showCategory('month')">
            <div class="stat-icon" style="background: #54a0ff20; color: #3498db;">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo count($birthdays_by_category['month']); ?></div>
                <div class="stat-label">This Month</div>
            </div>
        </div>
        <div class="stat-card" onclick="showCategory('upcoming')">
            <div class="stat-icon" style="background: #9b59b620; color: #9b59b6;">
                <i class="fas fa-calendar"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo count($birthdays_by_category['upcoming']); ?></div>
                <div class="stat-label">Upcoming</div>
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
            <div class="birthday-card today" onclick="window.location.href='employee_profile.php?id=<?php echo $bday['id']; ?>'">
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
            <div class="birthday-card" onclick="window.location.href='employee_profile.php?id=<?php echo $bday['id']; ?>'">
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
            <div class="birthday-card" onclick="window.location.href='employee_profile.php?id=<?php echo $bday['id']; ?>'">
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
            <div class="birthday-card" onclick="window.location.href='employee_profile.php?id=<?php echo $bday['id']; ?>'">
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

<!-- Greeting Cards Modal -->
<div class="modal" id="greetingCardsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h5><i class="fas fa-gift"></i> Birthday Greeting Cards</h5>
            <button class="modal-close" onclick="closeModal('greetingCardsModal')">&times;</button>
        </div>
        <div class="modal-body" id="greetingCardsBody">
            <div class="greeting-cards-grid">
                <?php 
                $all_for_cards = array_merge(
                    $birthdays_by_category['today'],
                    $birthdays_by_category['week'],
                    $birthdays_by_category['month'],
                    $birthdays_by_category['upcoming']
                );
                
                foreach ($all_for_cards as $bday): 
                    $template_class = $bday['category'] . '-template';
                    $greeting_message = "";
                    
                    if ($bday['category'] == 'today') {
                        $greeting_message = "Happy Birthday! Wishing you a fantastic day filled with joy and laughter. May all your dreams come true!";
                    } elseif ($bday['category'] == 'week') {
                        $greeting_message = "Happy Birthday in advance! Hope you have a wonderful celebration and an amazing year ahead!";
                    } elseif ($bday['category'] == 'month') {
                        $greeting_message = "Happy Birthday this month! May your special day be filled with happiness and wonderful surprises!";
                    } else {
                        $greeting_message = "Wishing you a very Happy Birthday! May your day be as special as you are!";
                    }
                ?>
                <div class="greeting-card <?php echo $template_class; ?>">
                    <div class="greeting-card-header">
                        <h3>🎂 Happy Birthday! 🎂</h3>
                        <p><?php echo date('F d, Y'); ?></p>
                    </div>
                    <div class="greeting-card-body">
                        <div class="greeting-avatar">
                            <?php if (!empty($bday['profile_picture'])): ?>
                                <img src="uploads/profiles/<?php echo $bday['profile_picture']; ?>" alt="Profile">
                            <?php else: ?>
                                <?php echo strtoupper(substr($bday['first_name'], 0, 1) . substr($bday['last_name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="greeting-name"><?php echo htmlspecialchars($bday['first_name'] . ' ' . $bday['last_name']); ?></div>
                        <div class="greeting-position"><?php echo htmlspecialchars($bday['position'] ?? 'Employee'); ?></div>
                        <div class="greeting-message">
                            "<?php echo $greeting_message; ?>"
                        </div>
                        <div class="greeting-details">
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('F d', strtotime($bday['birth_date'])); ?></span>
                            <span><i class="fas fa-birthday-cake"></i> Turning <?php echo $bday['next_age']; ?></span>
                            <span><i class="fas fa-building"></i> <?php echo htmlspecialchars($bday['office_name'] ?? 'HR'); ?></span>
                        </div>
                        <div class="greeting-footer">
                            <p>With warmest wishes from the HR Team</p>
                            <div class="greeting-signature">Municipal HRMO</div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-primary" onclick="printGreetingCards()">
                <i class="fas fa-print"></i> Print Cards
            </button>
            <button class="btn-primary" onclick="closeModal('greetingCardsModal')">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
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

// Show today's birthdays by default
showCategory('today');

function exportBirthdays() {
    window.location.href = 'export_birthdays.php';
}

function printBirthdays() {
    window.print();
}

// Modal Functions
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

function openGreetingCards() {
    const modal = document.getElementById('greetingCardsModal');
    modal.classList.add('show');
}

function printGreetingCards() {
    const printContent = document.getElementById('greetingCardsBody').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Birthday Greeting Cards</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 0;
                        padding: 20px;
                        background: #f5f5f5;
                    }
                    .greeting-cards-grid {
                        display: grid;
                        grid-template-columns: repeat(2, 1fr);
                        gap: 20px;
                        max-width: 1200px;
                        margin: 0 auto;
                    }
                    .greeting-card {
                        background: white;
                        border-radius: 15px;
                        overflow: hidden;
                        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
                        page-break-inside: avoid;
                        break-inside: avoid;
                        margin-bottom: 20px;
                    }
                    .greeting-card-header {
                        background: linear-gradient(135deg, #ff6b6b, #ee5253);
                        color: white;
                        padding: 30px 20px;
                        text-align: center;
                        position: relative;
                        overflow: hidden;
                    }
                    .greeting-card-header::before {
                        content: '🎂';
                        position: absolute;
                        top: -20px;
                        right: -20px;
                        font-size: 100px;
                        opacity: 0.2;
                        transform: rotate(15deg);
                    }
                    .greeting-card-header::after {
                        content: '🎈';
                        position: absolute;
                        bottom: -20px;
                        left: -20px;
                        font-size: 80px;
                        opacity: 0.2;
                        transform: rotate(-15deg);
                    }
                    .greeting-card-header h3 {
                        margin: 0 0 10px;
                        font-size: 28px;
                        font-weight: 700;
                    }
                    .greeting-card-header p {
                        margin: 0;
                        font-size: 16px;
                        opacity: 0.9;
                    }
                    .greeting-card-body {
                        padding: 25px;
                        text-align: center;
                    }
                    .greeting-avatar {
                        width: 100px;
                        height: 100px;
                        border-radius: 50%;
                        margin: -50px auto 15px;
                        background: linear-gradient(135deg, #667eea, #764ba2);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: white;
                        font-size: 40px;
                        font-weight: 600;
                        border: 4px solid white;
                        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                        position: relative;
                        z-index: 2;
                        overflow: hidden;
                    }
                    .greeting-avatar img {
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                    }
                    .greeting-name {
                        font-size: 24px;
                        font-weight: 700;
                        color: #333;
                        margin: 10px 0 5px;
                    }
                    .greeting-position {
                        font-size: 14px;
                        color: #666;
                        margin-bottom: 15px;
                    }
                    .greeting-message {
                        background: #f8f9fa;
                        padding: 20px;
                        border-radius: 10px;
                        margin: 20px 0;
                        font-size: 18px;
                        font-style: italic;
                        color: #555;
                        line-height: 1.6;
                        border-left: 4px solid #ff6b6b;
                    }
                    .greeting-details {
                        display: flex;
                        justify-content: center;
                        gap: 30px;
                        margin: 20px 0;
                        color: #666;
                    }
                    .greeting-details i {
                        color: #ff6b6b;
                        margin-right: 5px;
                    }
                    .greeting-footer {
                        border-top: 2px dashed #ddd;
                        padding-top: 20px;
                        text-align: center;
                        color: #999;
                        font-size: 12px;
                    }
                    .greeting-signature {
                        font-family: 'Brush Script MT', cursive;
                        font-size: 28px;
                        color: #ff6b6b;
                        margin-top: 10px;
                    }
                    @media print {
                        body {
                            background: white;
                            padding: 0;
                        }
                        .greeting-card {
                            box-shadow: none;
                            border: 1px solid #ddd;
                            page-break-inside: avoid;
                            break-inside: avoid;
                        }
                        .greeting-card-header {
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }
                        .greeting-avatar {
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }
                    }
                    .greeting-card.today-template .greeting-card-header {
                        background: linear-gradient(135deg, #ff6b6b, #ee5253);
                    }
                    .greeting-card.week-template .greeting-card-header {
                        background: linear-gradient(135deg, #feca57, #ff9f43);
                    }
                    .greeting-card.month-template .greeting-card-header {
                        background: linear-gradient(135deg, #54a0ff, #2e86de);
                    }
                    .greeting-card.upcoming-template .greeting-card-header {
                        background: linear-gradient(135deg, #5f27cd, #341f97);
                    }
                </style>
            </head>
            <body>
                <div class="greeting-cards-grid">
                    ${printContent}
                </div>
                <script>
                    window.onload = function() { window.print(); window.close(); }
                <\/script>
            </body>
        </html>
    `);
    printWindow.document.close();
}

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});
</script>

<?php include 'includes/footer.php'; ?>