<?php
require_once 'includes/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login first.";
    header("Location: login.php");
    exit();
}

// Get action
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

switch ($action) {
    case 'add':
        // Get and validate form data
        $annex = isset($_POST['annex']) ? trim($_POST['annex']) : '';
        $salary_grade = isset($_POST['salary_grade']) ? trim($_POST['salary_grade']) : '';
        $step = isset($_POST['step']) ? intval($_POST['step']) : 0;
        $monthly_salary = isset($_POST['monthly_salary']) ? floatval($_POST['monthly_salary']) : 0;
        $annual_salary = $monthly_salary * 12;
        $effective_date = isset($_POST['effective_date']) ? trim($_POST['effective_date']) : '';
        $position_category = isset($_POST['position_category']) ? trim($_POST['position_category']) : '';

        // Validate required fields
        $errors = [];
        if (empty($annex)) $errors[] = "Annex is required";
        if (empty($salary_grade)) $errors[] = "Salary grade is required";
        if ($step < 1 || $step > 8) $errors[] = "Step must be between 1 and 8";
        if ($monthly_salary <= 0) $errors[] = "Monthly salary must be greater than 0";
        if (empty($effective_date)) $errors[] = "Effective date is required";

        if (!empty($errors)) {
            $_SESSION['error'] = implode("<br>", $errors);
            header("Location: salary_grade.php?annex=" . urlencode($annex));
            exit();
        }

        // Check if combination already exists in the SAME annex ONLY
        $check_sql = "SELECT id FROM salary_grades WHERE salary_grade = ? AND step = ? AND annex = ?";
        $check_stmt = $conn->prepare($check_sql);
        
        if (!$check_stmt) {
            $_SESSION['error'] = "Database error: " . $conn->error;
            header("Location: salary_grade.php?annex=" . urlencode($annex));
            exit();
        }
        
        $check_stmt->bind_param("sis", $salary_grade, $step, $annex);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $_SESSION['error'] = "Salary grade $salary_grade step $step already exists in $annex! Each annex can only have one entry per grade and step.";
            $check_stmt->close();
            header("Location: salary_grade.php?annex=" . urlencode($annex));
            exit();
        }
        $check_stmt->close();

        // Insert new salary grade
        $insert_sql = "INSERT INTO salary_grades (annex, salary_grade, step, monthly_salary, annual_salary, effective_date, position_category) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        
        if (!$insert_stmt) {
            $_SESSION['error'] = "Database error: " . $conn->error;
            header("Location: salary_grade.php?annex=" . urlencode($annex));
            exit();
        }
        
        $insert_stmt->bind_param("ssiddss", $annex, $salary_grade, $step, $monthly_salary, $annual_salary, $effective_date, $position_category);
        
        try {
            if ($insert_stmt->execute()) {
                $_SESSION['success'] = "Salary grade added successfully to $annex!";
                
                // Check if activity_logs table exists before inserting
                $table_check = $conn->query("SHOW TABLES LIKE 'activity_logs'");
                if ($table_check->num_rows > 0) {
                    // Log the activity
                    $log_sql = "INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) 
                                VALUES (?, 'add_salary_grade', ?, ?, NOW())";
                    $log_stmt = $conn->prepare($log_sql);
                    $description = "Added $salary_grade Step $step with salary ₱" . number_format($monthly_salary, 2) . " to $annex";
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $log_stmt->bind_param("iss", $_SESSION['user_id'], $description, $ip);
                    $log_stmt->execute();
                    $log_stmt->close();
                }
            } else {
                $_SESSION['error'] = "Error adding salary grade: " . $insert_stmt->error;
            }
        } catch (mysqli_sql_exception $e) {
            // Check if it's a duplicate entry error
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $_SESSION['error'] = "This salary grade and step combination already exists in $annex. Each annex can only have one entry per grade and step.";
            } else {
                $_SESSION['error'] = "Database error: " . $e->getMessage();
            }
        }
        
        $insert_stmt->close();
        
        // Redirect back to the appropriate annex
        header("Location: salary_grade.php?annex=" . urlencode($annex));
        break;

    case 'edit':
        $id = intval($_POST['id']);
        $monthly_salary = floatval($_POST['monthly_salary']);
        $annual_salary = $monthly_salary * 12;
        $effective_date = trim($_POST['effective_date']);
        $position_category = isset($_POST['position_category']) ? trim($_POST['position_category']) : '';

        // Get annex for redirect
        $annex_sql = "SELECT annex FROM salary_grades WHERE id = ?";
        $annex_stmt = $conn->prepare($annex_sql);
        $annex_stmt->bind_param("i", $id);
        $annex_stmt->execute();
        $annex_result = $annex_stmt->get_result();
        $annex_row = $annex_result->fetch_assoc();
        $annex = $annex_row['annex'];
        $annex_stmt->close();

        $update_sql = "UPDATE salary_grades SET monthly_salary = ?, annual_salary = ?, effective_date = ?, position_category = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ddssi", $monthly_salary, $annual_salary, $effective_date, $position_category, $id);
        
        try {
            if ($update_stmt->execute()) {
                $_SESSION['success'] = "Salary grade updated successfully!";
                
                // Check if activity_logs table exists
                $table_check = $conn->query("SHOW TABLES LIKE 'activity_logs'");
                if ($table_check->num_rows > 0) {
                    // Log the activity
                    $log_sql = "INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) 
                                VALUES (?, 'edit_salary_grade', ?, ?, NOW())";
                    $log_stmt = $conn->prepare($log_sql);
                    $description = "Updated salary grade ID $id to ₱" . number_format($monthly_salary, 2);
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $log_stmt->bind_param("iss", $_SESSION['user_id'], $description, $ip);
                    $log_stmt->execute();
                    $log_stmt->close();
                }
            } else {
                $_SESSION['error'] = "Error updating salary grade: " . $update_stmt->error;
            }
        } catch (mysqli_sql_exception $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
        
        $update_stmt->close();
        
        header("Location: salary_grade.php?annex=" . urlencode($annex));
        break;

    case 'delete':
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $id = intval($_GET['id']);
            
            // Get details before deleting
            $details_sql = "SELECT annex, salary_grade, step, monthly_salary FROM salary_grades WHERE id = ?";
            $details_stmt = $conn->prepare($details_sql);
            $details_stmt->bind_param("i", $id);
            $details_stmt->execute();
            $details_result = $details_stmt->get_result();
            
            if ($details_result->num_rows == 0) {
                $_SESSION['error'] = "Record not found.";
                header("Location: salary_grade.php");
                exit();
            }
            
            $row = $details_result->fetch_assoc();
            $annex = $row['annex'];
            $salary_grade = $row['salary_grade'];
            $step = $row['step'];
            $monthly_salary = $row['monthly_salary'];
            $details_stmt->close();

            $delete_sql = "DELETE FROM salary_grades WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $id);
            
            try {
                if ($delete_stmt->execute()) {
                    $_SESSION['success'] = "Salary grade deleted successfully!";
                    
                    // Check if activity_logs table exists
                    $table_check = $conn->query("SHOW TABLES LIKE 'activity_logs'");
                    if ($table_check->num_rows > 0) {
                        // Log the activity
                        $log_sql = "INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) 
                                    VALUES (?, 'delete_salary_grade', ?, ?, NOW())";
                        $log_stmt = $conn->prepare($log_sql);
                        $description = "Deleted $salary_grade Step $step (₱" . number_format($monthly_salary, 2) . ") from $annex";
                        $ip = $_SERVER['REMOTE_ADDR'];
                        $log_stmt->bind_param("iss", $_SESSION['user_id'], $description, $ip);
                        $log_stmt->execute();
                        $log_stmt->close();
                    }
                } else {
                    $_SESSION['error'] = "Error deleting salary grade: " . $delete_stmt->error;
                }
            } catch (mysqli_sql_exception $e) {
                $_SESSION['error'] = "Database error: " . $e->getMessage();
            }
            
            $delete_stmt->close();
            
            header("Location: salary_grade.php?annex=" . urlencode($annex));
        } else {
            $_SESSION['error'] = "Invalid ID for deletion";
            header("Location: salary_grade.php");
        }
        break;

    case 'bulk_import':
        // Handle bulk import from Excel/CSV
        $annex = isset($_POST['annex']) ? $_POST['annex'] : 'A-1';
        
        if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] == 0) {
            $file = $_FILES['import_file']['tmp_name'];
            $handle = fopen($file, "r");
            $success_count = 0;
            $error_count = 0;
            $duplicate_count = 0;
            
            // Skip header row
            $header = fgetcsv($handle);
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) < 5) continue;
                
                $file_annex = !empty($data[0]) ? trim($data[0]) : $annex;
                $salary_grade = trim($data[1]);
                $step = intval($data[2]);
                $monthly_salary = floatval($data[3]);
                $annual_salary = $monthly_salary * 12;
                $effective_date = !empty($data[4]) ? trim($data[4]) : date('Y-m-d');
                $position_category = isset($data[5]) ? trim($data[5]) : '';

                // Check if exists in the SAME annex only
                $check_sql = "SELECT id FROM salary_grades WHERE salary_grade = ? AND step = ? AND annex = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("sis", $salary_grade, $step, $file_annex);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows == 0) {
                    $insert_sql = "INSERT INTO salary_grades (annex, salary_grade, step, monthly_salary, annual_salary, effective_date, position_category) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param("ssiddss", $file_annex, $salary_grade, $step, $monthly_salary, $annual_salary, $effective_date, $position_category);
                    
                    try {
                        if ($insert_stmt->execute()) {
                            $success_count++;
                        } else {
                            $error_count++;
                        }
                    } catch (mysqli_sql_exception $e) {
                        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                            $duplicate_count++;
                        } else {
                            $error_count++;
                        }
                    }
                    $insert_stmt->close();
                } else {
                    $duplicate_count++;
                }
                $check_stmt->close();
            }
            fclose($handle);
            
            $_SESSION['success'] = "Import completed: $success_count records added, $duplicate_count duplicates skipped, $error_count errors.";
            
            // Check if activity_logs table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'activity_logs'");
            if ($table_check->num_rows > 0) {
                // Log the import activity
                $log_sql = "INSERT INTO activity_logs (user_id, action, description, ip_address, created_at) 
                            VALUES (?, 'bulk_import', ?, ?, NOW())";
                $log_stmt = $conn->prepare($log_sql);
                $description = "Imported salary grades: $success_count added, $duplicate_count duplicates";
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_stmt->bind_param("iss", $_SESSION['user_id'], $description, $ip);
                $log_stmt->execute();
                $log_stmt->close();
            }
        } else {
            $_SESSION['error'] = "Error uploading file.";
        }
        
        header("Location: salary_grade.php?annex=" . urlencode($annex));
        break;

    default:
        header("Location: salary_grade.php");
        break;
}

exit();
?>