<?php
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';

$auth = new AuthCheck();
$auth->requireLogin();

// Include header
include 'includes/header.php';
?>

<div class="loyalty-awards-container">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fas fa-award"></i> Loyalty Awards Management</h2>
        <p>Recognize employees for their years of service</p>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="card">
            <div class="card-body">
                <div class="summary-icon">🏆</div>
                <div class="summary-value">156</div>
                <div class="summary-label">Total Awardees</div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="summary-icon">📅</div>
                <div class="summary-value">24</div>
                <div class="summary-label">This Year</div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="summary-icon">⏳</div>
                <div class="summary-value">15</div>
                <div class="summary-label">Upcoming</div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="summary-icon">⭐</div>
                <div class="summary-value">8</div>
                <div class="summary-label">Special Awards</div>
            </div>
        </div>
    </div>

    <!-- Award Categories -->
    <div class="card">
        <div class="card-header">
            <h3>Award Categories</h3>
        </div>
        <div class="card-body">
            <table class="simple-table">
                <thead>
                    <tr>
                        <th>Award</th>
                        <th>Years</th>
                        <th>Recipients</th>
                        <th>Benefits</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Bronze Award</td>
                        <td>5 Years</td>
                        <td>45</td>
                        <td>₱5,000 + Bronze Pin</td>
                    </tr>
                    <tr>
                        <td>Silver Award</td>
                        <td>10 Years</td>
                        <td>32</td>
                        <td>₱10,000 + Silver Pin</td>
                    </tr>
                    <tr>
                        <td>Gold Award</td>
                        <td>15 Years</td>
                        <td>24</td>
                        <td>₱15,000 + Gold Pin</td>
                    </tr>
                    <tr>
                        <td>Platinum Award</td>
                        <td>20 Years</td>
                        <td>18</td>
                        <td>₱20,000 + Platinum Pin</td>
                    </tr>
                    <tr>
                        <td>Diamond Award</td>
                        <td>25 Years</td>
                        <td>12</td>
                        <td>₱25,000 + Diamond Pin</td>
                    </tr>
                    <tr>
                        <td>Presidential Award</td>
                        <td>30+ Years</td>
                        <td>5</td>
                        <td>₱50,000 + Gold Watch</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Awardees -->
    <div class="card">
        <div class="card-header">
            <h3>Recent Awardees (2026)</h3>
        </div>
        <div class="card-body">
            <table class="simple-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Award</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Juan Dela Cruz</td>
                        <td>Operations</td>
                        <td>5 Years Service</td>
                        <td>Jan 15, 2026</td>
                        <td><a href="employee_profile.php?id=1" class="btn-small">View</a></td>
                    </tr>
                    <tr>
                        <td>Maria Santos</td>
                        <td>Finance</td>
                        <td>10 Years Service</td>
                        <td>Feb 20, 2026</td>
                        <td><a href="employee_profile.php?id=2" class="btn-small">View</a></td>
                    </tr>
                    <tr>
                        <td>Pedro Reyes</td>
                        <td>IT</td>
                        <td>15 Years Service</td>
                        <td>Mar 10, 2026</td>
                        <td><a href="employee_profile.php?id=3" class="btn-small">View</a></td>
                    </tr>
                    <tr>
                        <td>Ana Gonzales</td>
                        <td>HR</td>
                        <td>20 Years Service</td>
                        <td>Apr 05, 2026</td>
                        <td><a href="employee_profile.php?id=4" class="btn-small">View</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Upcoming Milestones -->
    <div class="card">
        <div class="card-header">
            <h3>Upcoming Service Milestones</h3>
        </div>
        <div class="card-body">
            <table class="simple-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Current Years</th>
                        <th>Milestone</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Jose Rizal</td>
                        <td>IT</td>
                        <td>4 yrs 11 mos</td>
                        <td>5 Years</td>
                        <td>May 15, 2026</td>
                    </tr>
                    <tr>
                        <td>Emilio Jacinto</td>
                        <td>Finance</td>
                        <td>9 yrs 10 mos</td>
                        <td>10 Years</td>
                        <td>Jun 20, 2026</td>
                    </tr>
                    <tr>
                        <td>Andres Bonifacio</td>
                        <td>Operations</td>
                        <td>14 yrs 8 mos</td>
                        <td>15 Years</td>
                        <td>Jul 10, 2026</td>
                    </tr>
                    <tr>
                        <td>Gabriela Silang</td>
                        <td>HR</td>
                        <td>19 yrs 6 mos</td>
                        <td>20 Years</td>
                        <td>Aug 05, 2026</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h3>Quick Actions</h3>
        </div>
        <div class="card-body">
            <div class="action-buttons">
                <button class="btn-primary"><i class="fas fa-plus"></i> New Award</button>
                <button class="btn-secondary"><i class="fas fa-print"></i> Print Certificates</button>
                <button class="btn-secondary"><i class="fas fa-calendar"></i> Schedule Ceremony</button>
            </div>
        </div>
    </div>
</div>

<style>
.loyalty-awards-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px 0;
}

/* Page Header */
.page-header {
    margin-bottom: 30px;
}

.page-header h2 {
    font-size: 24px;
    color: #333;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.page-header h2 i {
    color: #3498db;
}

.page-header p {
    color: #666;
    font-size: 14px;
}

/* Summary Cards */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.summary-cards .card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.summary-cards .card-body {
    padding: 20px;
    text-align: center;
}

.summary-icon {
    font-size: 32px;
    margin-bottom: 10px;
}

.summary-value {
    font-size: 28px;
    font-weight: 600;
    color: #333;
    line-height: 1.2;
    margin-bottom: 5px;
}

.summary-label {
    font-size: 13px;
    color: #666;
    text-transform: uppercase;
}

/* Cards */
.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    margin-bottom: 25px;
    overflow: hidden;
}

.card-header {
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.card-header h3 {
    font-size: 16px;
    color: #333;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.card-header h3 i {
    color: #3498db;
}

.card-body {
    padding: 20px;
}

/* Simple Table */
.simple-table {
    width: 100%;
    border-collapse: collapse;
}

.simple-table th {
    text-align: left;
    padding: 12px;
    background: #f8f9fa;
    font-size: 13px;
    color: #555;
    font-weight: 600;
    border-bottom: 2px solid #e0e0e0;
}

.simple-table td {
    padding: 12px;
    border-bottom: 1px solid #e0e0e0;
    font-size: 13px;
    color: #333;
}

.simple-table tr:hover td {
    background: #f5f5f5;
}

/* Buttons */
.btn-small {
    background: #3498db;
    color: white;
    padding: 4px 10px;
    border-radius: 3px;
    text-decoration: none;
    font-size: 12px;
    display: inline-block;
}

.btn-small:hover {
    background: #2980b9;
}

.btn-primary {
    background: #3498db;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-primary:hover {
    background: #2980b9;
}

.btn-secondary {
    background: #ecf0f1;
    color: #333;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-secondary:hover {
    background: #bdc3c7;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* Responsive */
@media (max-width: 1024px) {
    .summary-cards {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .summary-cards {
        grid-template-columns: 1fr;
    }
    
    .simple-table {
        display: block;
        overflow-x: auto;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn-primary, .btn-secondary {
        width: 100%;
        justify-content: center;
    }
}
</style>
