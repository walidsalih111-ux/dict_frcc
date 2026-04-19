<?php
include 'connect.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Retrieve Employee Data
    $full               = $_POST['full']               ?? '';
    $emp_email          = $_POST['emp_email']          ?? '';
    $age                = !empty($_POST['age']) ? (int)$_POST['age'] : null;
    $gender             = $_POST['gender']             ?? '';
    $department         = $_POST['department']         ?? '';
    $area_of_assignment = $_POST['area_of_assignment'] ?? '';
    $designation        = $_POST['designation']        ?? '';
    $unit               = $_POST['unit']               ?? '';
    $status             = $_POST['status']             ?? '';
    
    // Retrieve User Account Data
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role']     ?? 'user';

    // ---------------------------------------------------------------
    // 1. ADMIN LIMIT CHECK
    // ---------------------------------------------------------------
    if ($role === 'admin') {
        $sql_count   = "SELECT COUNT(*) FROM user_account WHERE role = 'admin'";
        $stmt_count  = $conn->query($sql_count);
        $admin_count = $stmt_count->fetch_row()[0];

        if ($admin_count >= 3) {
            header("Location: employee_management.php?error=admin_limit");
            exit();
        }
    }

    // ---------------------------------------------------------------
    // 2. DUPLICATE EMPLOYEE CHECK (Name or Email)
    // ---------------------------------------------------------------
    $check_sql  = "SELECT emp_id FROM employees WHERE full = ? OR (emp_email = ? AND emp_email != '')";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("ss", $full, $emp_email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        header("Location: employee_management.php?error=duplicate_employee");
        exit();
    }
    $stmt_check->close();

    // ---------------------------------------------------------------
    // 3. DUPLICATE USERNAME PRE-CHECK (Backend Validation)
    // ---------------------------------------------------------------
    if (!empty($username)) {
        $check_user_sql = "SELECT id FROM user_account WHERE username = ?";
        $stmt_check_user = $conn->prepare($check_user_sql);
        $stmt_check_user->bind_param("s", $username);
        $stmt_check_user->execute();
        $stmt_check_user->store_result();
        
        if ($stmt_check_user->num_rows > 0) {
            $stmt_check_user->close();
            header("Location: employee_management.php?error=duplicate_username");
            exit();
        }
        $stmt_check_user->close();
    }

    // ---------------------------------------------------------------
    // 4. INSERT DATA
    // ---------------------------------------------------------------
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $conn->begin_transaction();

        // Insert into employees table
        $sql_emp  = "INSERT INTO employees (full, emp_email, age, gender, department, area_of_assignment, designation, unit, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_emp = $conn->prepare($sql_emp);
        $stmt_emp->bind_param("ssissssss", $full, $emp_email, $age, $gender, $department, $area_of_assignment, $designation, $unit, $status);
        $stmt_emp->execute();
        
        $new_emp_id = $conn->insert_id;
        
        // Insert into user_account table (if a username was provided)
        if (!empty($username)) {
            $sql_user  = "INSERT INTO user_account (emp_id, username, password, role) VALUES (?, ?, ?, ?)";
            $stmt_user = $conn->prepare($sql_user);
            $stmt_user->bind_param("isss", $new_emp_id, $username, $hashed_password, $role);
            $stmt_user->execute();
        }

        $conn->commit();

        header("Location: employee_management.php?msg=add_success");
        exit();

    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        
        if ($e->getCode() == 1062) {
            // Failsafe: Duplicate entry trigger
            header("Location: employee_management.php?error=duplicate_username");
            exit();
        } else {
            error_log("Database error during insert: " . $e->getMessage());
            header("Location: employee_management.php?error=db_error");
            exit();
        }
    }

} else {
    header("Location: employee_management.php");
    exit();
}
?>