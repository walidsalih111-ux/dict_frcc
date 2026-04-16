<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Only admins can update account status.']);
    exit();
}

require 'connect.php';

if (!$pdo) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method. Please use POST.']);
    exit();
}

$empId = isset($_POST['emp_id']) ? intval($_POST['emp_id']) : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';

if ($empId <= 0 || $action !== 'deactivate') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id FROM user_account WHERE emp_id = ? LIMIT 1");
    $stmt->execute([$empId]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$account) {
        echo json_encode(['status' => 'error', 'message' => 'No user account found for this employee.']);
        exit();
    }

    $deleteStmt = $pdo->prepare("DELETE FROM user_account WHERE emp_id = ?");
    $deleteStmt->execute([$empId]);

    if ($deleteStmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Account deactivated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to deactivate account.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
