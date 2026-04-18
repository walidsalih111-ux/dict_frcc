<?php
// Include your central database connection instead of hardcoding it!
include 'connect.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Retrieve basic employee info
    $emp_id = $_POST['emp_id'] ?? null;
    $full = $_POST['full'] ?? '';
    $emp_email = $_POST['emp_email'] ?? '';
    
    $age = !empty($_POST['age']) ? $_POST['age'] : null;
    $gender = $_POST['gender'] ?? '';
    $area_of_assignment = $_POST['area_of_assignment'] ?? '';
    $unit = $_POST['unit'] ?? '';
    
    $department = $_POST['department'] ?? '';
    $designation = $_POST['designation'] ?? '';
    $status = $_POST['status'] ?? '';
    
    // Retrieve Account Settings info
    $role = $_POST['role'] ?? 'user';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($emp_id)) {
        die("Error: Employee ID is missing.");
    }

    // ---------------------------------------------------------------
    // ADMIN LIMIT CHECK
    // If the new role is 'admin', verify the current employee is NOT
    // already an admin, then count existing admins. Block if >= 3.
    // ---------------------------------------------------------------
    if ($role === 'admin') {
        // Get this employee's current role (so we don't count them twice)
        $sql_current_role = "SELECT role FROM user_account WHERE emp_id = ?";
        $stmt_current = $conn->prepare($sql_current_role);
        $stmt_current->bind_param("s", $emp_id);
        $stmt_current->execute();
        $stmt_current->bind_result($current_role);
        $stmt_current->fetch();
        $stmt_current->close();

        // Only enforce the cap when this employee is NOT already an admin
        if ($current_role !== 'admin') {
            $sql_count = "SELECT COUNT(*) FROM user_account WHERE role = 'admin'";
            $stmt_count = $conn->query($sql_count);
            $admin_count = $stmt_count->fetch_row()[0];

            if ($admin_count >= 3) {
                header("Location: employee_management.php?error=admin_limit");
                exit();
            }
        }
    }
    // ---------------------------------------------------------------

    try {
        // Begin Transaction
        $conn->begin_transaction();

        // 1. Update the employees table
        $sql_emp = "UPDATE employees 
                    SET full = ?, emp_email = ?, age = ?, gender = ?, department = ?, area_of_assignment = ?, designation = ?, unit = ?, status = ? 
                    WHERE emp_id = ?";
        
        $stmt_emp = $conn->prepare($sql_emp);
        $stmt_emp->bind_param("ssssssssss", $full, $emp_email, $age, $gender, $department, $area_of_assignment, $designation, $unit, $status, $emp_id);
        $stmt_emp->execute();

        // 2. Update OR Create the user_account table
        if (!empty($username)) {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $sql_user = "INSERT INTO user_account (emp_id, username, password, role) 
                             VALUES (?, ?, ?, ?)
                             ON DUPLICATE KEY UPDATE username = VALUES(username), password = VALUES(password), role = VALUES(role)";
                $stmt_user = $conn->prepare($sql_user);
                $stmt_user->bind_param("ssss", $emp_id, $username, $hashed_password, $role);
            } else {
                $sql_user = "INSERT INTO user_account (emp_id, username, role) 
                             VALUES (?, ?, ?)
                             ON DUPLICATE KEY UPDATE username = VALUES(username), role = VALUES(role)";
                $stmt_user = $conn->prepare($sql_user);
                $stmt_user->bind_param("sss", $emp_id, $username, $role);
            }
            $stmt_user->execute();
        }

        // Commit Transaction
        $conn->commit();

        header("Location: employee_management.php?msg=edit_success");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Database error during update: " . $e->getMessage());
    }

} else {
    header("Location: employee_management.php");
    exit();
}
?>