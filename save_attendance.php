<?php
// save_attendance.php

// Set timezone to local (Philippines) to match your frontend setup
date_default_timezone_set('Asia/Manila');

include 'connect.php';

// Tell the browser we are sending JSON data back
header('Content-Type: application/json');

// 2. Retrieve POST data sent from the frontend
// We use isset() to check if checkboxes were checked (they send '1' or 'true' if checked, nothing if unchecked)
$employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
$with_id = isset($_POST['with_id']) ? 1 : 0;
$asean = isset($_POST['asean']) ? 1 : 0;
$present = isset($_POST['present']) ? 1 : 0;
$sign = isset($_POST['sign']) ? 1 : 0;

// Grab the newly added date sent from the frontend Date Input. 
// If somehow missing, default fallback is today's date.
$attendance_date = (isset($_POST['attendance_date']) && !empty($_POST['attendance_date'])) ? $_POST['attendance_date'] : date('Y-m-d');

// Basic validation
if ($employee_id === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a valid name.']);
    exit();
}

if (empty($attendance_date)) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide a valid attendance date.']);
    exit();
}

// 3. Prepare and execute the SQL query
// Using INSERT ... ON DUPLICATE KEY UPDATE so if they already saved attendance for today, it updates instead of duplicating
$sql = "INSERT INTO attendance_records (employee_id, attendance_date, with_id, asean, present, sign) 
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        with_id = VALUES(with_id), asean = VALUES(asean), present = VALUES(present), sign = VALUES(sign)";

$stmt = $conn->prepare($sql);

if ($stmt) {
    // Bind the parameters (i = integer, s = string)
    // employee_id(i), date(s), with_id(i), asean(i), present(i), sign(i)
    $stmt->bind_param("isiiii", $employee_id, $attendance_date, $with_id, $asean, $present, $sign);
    
    if ($stmt->execute()) {
        // Return a customized success message confirming the date
        echo json_encode(['status' => 'success', 'message' => 'Attendance saved successfully for ' . date('M d, Y', strtotime($attendance_date)) . '!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save record: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database query preparation failed.']);
}

$conn->close();
?>