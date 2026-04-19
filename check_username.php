<?php
// check_username.php
include 'connect.php';

// Always return JSON format
header('Content-Type: application/json');

if (isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $emp_id = isset($_POST['emp_id']) ? trim($_POST['emp_id']) : '';

    if (!empty($emp_id)) {
        // Edit Mode: Check if this username is taken by a DIFFERENT employee
        $stmt = $conn->prepare("SELECT id FROM user_account WHERE username = ? AND emp_id != ?");
        $stmt->bind_param("si", $username, $emp_id);
    } else {
        // Add Mode: Check if this username exists anywhere
        $stmt = $conn->prepare("SELECT id FROM user_account WHERE username = ?");
        $stmt->bind_param("s", $username);
    }

    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Username taken
        echo json_encode(['available' => false]);
    } else {
        // Username available
        echo json_encode(['available' => true]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['available' => false, 'error' => 'No username provided']);
}
?>