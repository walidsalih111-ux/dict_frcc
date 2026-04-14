<?php
session_start();

// Set header to return JSON for AJAX/SweetAlert responses
header('Content-Type: application/json');

// Disable error output to prevent JSON corruption
ini_set('display_errors', 0);
error_reporting(0);

// Security check: Only allow admins to perform deletions
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Only admins can delete records.']);
    exit();
}

require 'connect.php';

// Check if PDO connection is available
if (!$pdo) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit();
}

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Support both standard Form POST and JSON payload (Fetch API/Axios)
    $emp_id = 0;

    if (isset($_POST['id'])) {
        $emp_id = intval($_POST['id']);
    } elseif (isset($_POST['emp_id'])) {
        $emp_id = intval($_POST['emp_id']);
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['id'])) {
            $emp_id = intval($input['id']);
        } elseif (isset($input['emp_id'])) {
            $emp_id = intval($input['emp_id']);
        }
    }

    if ($emp_id > 0) {
        // Begin Transaction to ensure data integrity
        $pdo->beginTransaction();

        try {
            // 1. Delete associated user account to completely revoke login access
            // (Even though the DB constraint is ON DELETE SET NULL, deleting the account is safer)
            $stmt_user = $pdo->prepare("DELETE FROM user_account WHERE emp_id = ?");
            $stmt_user->execute([$emp_id]);

            // 2. Delete the employee record
            // Note: Attendance records will be automatically deleted due to ON DELETE CASCADE
            $stmt_emp = $pdo->prepare("DELETE FROM employees WHERE emp_id = ?");
            $stmt_emp->execute([$emp_id]);

            if ($stmt_emp->rowCount() > 0) {
                // Commit transaction if successful
                $pdo->commit();
                echo json_encode(['status' => 'success', 'message' => 'Employee deleted successfully.']);
            } else {
                // Rollback if the employee was not found
                $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'Employee not found or already deleted.']);
            }

        } catch (PDOException $e) {
            // Rollback the transaction on any error
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete employee: ' . $e->getMessage()]);
        }

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid employee ID provided.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method. Please use POST.']);
}
?>