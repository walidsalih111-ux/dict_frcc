<?php
// Include your central database connection instead of hardcoding it!
include 'connect.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Retrieve basic employee info
    $emp_id = $_POST['emp_id'] ?? null;
    $full = $_POST['full'] ?? '';
    $emp_email = $_POST['emp_email'] ?? '';
    
    // --> FIXED: Retrieve the missing fields from the form
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

    try {
        // Begin Transaction
        $conn->begin_transaction();

        // 1. Update the employees table (INCLUDING ALL MISSING FIELDS)
        $sql_emp = "UPDATE employees 
                    SET full = ?, emp_email = ?, age = ?, gender = ?, department = ?, area_of_assignment = ?, designation = ?, unit = ?, status = ? 
                    WHERE emp_id = ?";
        
        $stmt_emp = $conn->prepare($sql_emp);
        // Bind parameters: all as strings 's' for maximum safety (MySQL will convert to INT automatically where needed)
        $stmt_emp->bind_param("ssssssssss", $full, $emp_email, $age, $gender, $department, $area_of_assignment, $designation, $unit, $status, $emp_id);
        $stmt_emp->execute();

        // 2. Update OR Create the user_account table
        // --> FIXED: Using INSERT ON DUPLICATE KEY UPDATE in case the employee didn't have an account yet
        if (!empty($username)) {
            if (!empty($password)) {
                // Password is provided, hash it and update role, username, and password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $sql_user = "INSERT INTO user_account (emp_id, username, password, role) 
                             VALUES (?, ?, ?, ?)
                             ON DUPLICATE KEY UPDATE username = VALUES(username), password = VALUES(password), role = VALUES(role)";
                $stmt_user = $conn->prepare($sql_user);
                $stmt_user->bind_param("ssss", $emp_id, $username, $hashed_password, $role);
            } else {
                // Password left blank, only update role and username
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

        // Redirect back with success message
        header("Location: employee_management.php?msg=edit_success");
        exit();

    } catch (Exception $e) { // Catching generic exceptions ensures PDO/MySQLi issues are caught
        $conn->rollback();
        die("Database error during update: " . $e->getMessage());
    }

} else {
    // Redirect if accessed directly without POST
    header("Location: employee_management.php");
    exit();
}
?>