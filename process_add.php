<?php
include 'connect.php';

// Enable MySQLi exceptions for proper try-catch error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Retrieve Employee Data
    $full = $_POST['full'] ?? '';
    $emp_email = $_POST['emp_email'] ?? '';
    $age = !empty($_POST['age']) ? (int)$_POST['age'] : null;
    $gender = $_POST['gender'] ?? '';
    $department = $_POST['department'] ?? '';
    $area_of_assignment = $_POST['area_of_assignment'] ?? '';
    $designation = $_POST['designation'] ?? '';
    $unit = $_POST['unit'] ?? '';
    $status = $_POST['status'] ?? '';
    
    // Retrieve User Account Data
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    // 0. Check for existing employee with the same Name OR Email
    // Using emp_email != '' to ensure empty emails don't trigger false duplicate positives
    $check_sql = "SELECT emp_id FROM employees WHERE full = ? OR (emp_email = ? AND emp_email != '')";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("ss", $full, $emp_email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        // A duplicate employee exists!
        $stmt_check->close();
        // Redirect back to management page with duplicate employee error
        header("Location: employee_management.php?error=duplicate_employee");
        exit();
    }
    $stmt_check->close();


    // Hash the password securely
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Begin Transaction
        $conn->begin_transaction();

        // 1. Insert into employees table
        $sql_emp = "INSERT INTO employees (full, emp_email, age, gender, department, area_of_assignment, designation, unit, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_emp = $conn->prepare($sql_emp);
        $stmt_emp->bind_param("ssissssss", $full, $emp_email, $age, $gender, $department, $area_of_assignment, $designation, $unit, $status);
        $stmt_emp->execute();
        
        // Get the newly generated emp_id
        $new_emp_id = $conn->insert_id;
        
        // 2. Insert into user_account table using the new emp_id
        $sql_user = "INSERT INTO user_account (emp_id, username, password, role) VALUES (?, ?, ?, ?)";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("isss", $new_emp_id, $username, $hashed_password, $role);
        $stmt_user->execute();

        // Commit Transaction (Save changes to database)
        $conn->commit();

        // Redirect back to management page with success message
        header("Location: employee_management.php?msg=add_success");
        exit();

    } catch (mysqli_sql_exception $e) {
        // Rollback if something failed (e.g., duplicate username)
        $conn->rollback();
        
        // Check if the error is a duplicate username
        if ($e->getCode() == 1062) { // 1062 is MySQL duplicate entry error
            header("Location: employee_management.php?error=duplicate_username");
            exit();
        } else {
            // Log general db errors and redirect
            error_log("Database error during insert: " . $e->getMessage());
            header("Location: employee_management.php?error=db_error");
            exit();
        }
    }
} else {
    // Redirect if accessed directly without POST
    header("Location: employee_management.php");
    exit();
}