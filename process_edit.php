<?php
include 'connect.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Retrieve basic employee info
    $emp_id             = $_POST['emp_id']             ?? null;
    $full               = $_POST['full']               ?? '';
    $emp_email          = $_POST['emp_email']          ?? '';
    $age                = !empty($_POST['age']) ? $_POST['age'] : null;
    $gender             = $_POST['gender']             ?? '';
    $area_of_assignment = $_POST['area_of_assignment'] ?? '';
    $unit               = $_POST['unit']               ?? '';
    $department         = $_POST['department']         ?? '';
    $designation        = $_POST['designation']        ?? '';
    $status             = $_POST['status']             ?? '';
    
    // Retrieve Account Settings info
    $role     = $_POST['role']     ?? 'user';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($emp_id)) {
        die("Error: Employee ID is missing.");
    }

    // ---------------------------------------------------------------
    // 1. ADMIN LIMIT CHECK
    // ---------------------------------------------------------------
    if ($role === 'admin') {
        $sql_current_role = "SELECT role FROM user_account WHERE emp_id = ?";
        $stmt_current     = $conn->prepare($sql_current_role);
        $stmt_current->bind_param("s", $emp_id);
        $stmt_current->execute();
        $stmt_current->bind_result($current_role);
        $stmt_current->fetch();
        $stmt_current->close();

        if ($current_role !== 'admin') {
            $sql_count   = "SELECT COUNT(*) FROM user_account WHERE role = 'admin'";
            $stmt_count  = $conn->query($sql_count);
            $admin_count = $stmt_count->fetch_row()[0];

            if ($admin_count >= 3) {
                header("Location: employee_management.php?error=admin_limit");
                exit();
            }
        }
    }

    // ---------------------------------------------------------------
    // 2. DUPLICATE USERNAME PRE-CHECK
    // Ensure the username isn't taken by *someone else*
    // ---------------------------------------------------------------
    if (!empty($username)) {
        $check_user_sql = "SELECT id FROM user_account WHERE username = ? AND emp_id != ?";
        $stmt_check_user = $conn->prepare($check_user_sql);
        $stmt_check_user->bind_param("si", $username, $emp_id);
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
    // 3. UPDATE DATA
    // ---------------------------------------------------------------
    try {
        $conn->begin_transaction();

        // Update the employees table
        $sql_emp  = "UPDATE employees 
                     SET full = ?, emp_email = ?, age = ?, gender = ?, department = ?, area_of_assignment = ?, designation = ?, unit = ?, status = ? 
                     WHERE emp_id = ?";
        $stmt_emp = $conn->prepare($sql_emp);
        $stmt_emp->bind_param("ssssssssss", $full, $emp_email, $age, $gender, $department, $area_of_assignment, $designation, $unit, $status, $emp_id);
        $stmt_emp->execute();

        // Update OR Create the user_account row safely
        if (!empty($username)) {
            
            // Check if an account already exists for this employee ID
            $check_acc_sql = "SELECT id FROM user_account WHERE emp_id = ?";
            $stmt_acc = $conn->prepare($check_acc_sql);
            $stmt_acc->bind_param("i", $emp_id);
            $stmt_acc->execute();
            $stmt_acc->store_result();
            $account_exists = $stmt_acc->num_rows > 0;
            $stmt_acc->close();

            if ($account_exists) {
                // UPDATE existing account
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql_user = "UPDATE user_account SET username = ?, password = ?, role = ? WHERE emp_id = ?";
                    $stmt_user = $conn->prepare($sql_user);
                    $stmt_user->bind_param("sssi", $username, $hashed_password, $role, $emp_id);
                } else {
                    $sql_user = "UPDATE user_account SET username = ?, role = ? WHERE emp_id = ?";
                    $stmt_user = $conn->prepare($sql_user);
                    $stmt_user->bind_param("ssi", $username, $role, $emp_id);
                }
                $stmt_user->execute();
            } else {
                // INSERT new account for existing employee
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql_user = "INSERT INTO user_account (emp_id, username, password, role) VALUES (?, ?, ?, ?)";
                $stmt_user = $conn->prepare($sql_user);
                $stmt_user->bind_param("isss", $emp_id, $username, $hashed_password, $role);
                $stmt_user->execute();
            }
        }

        $conn->commit();

        header("Location: employee_management.php?msg=edit_success");
        exit();

    } catch (mysqli_sql_exception $e) {
        $conn->rollback();

        if ($e->getCode() == 1062) {
            header("Location: employee_management.php?error=duplicate_username");
            exit();
        } else {
            error_log("Database error during update: " . $e->getMessage());
            header("Location: employee_management.php?error=db_error");
            exit();
        }
    }

} else {
    header("Location: employee_management.php");
    exit();
}
?>