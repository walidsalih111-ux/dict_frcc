<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: view_attendance.php');
    exit();
}

$attendance_id = isset($_POST['attendance_id']) ? (int) $_POST['attendance_id'] : 0;
$emp_id = isset($_POST['emp_id']) ? (int) $_POST['emp_id'] : 0;
$original_time_recorded = $_POST['original_time_recorded'] ?? '';
$time_recorded = $_POST['time_recorded'] ?? '';
// Designation input retrieval removed
$with_id = ($_POST['with_id'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
$proper_attire = ($_POST['proper_attire'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
$is_compliant = isset($_POST['is_compliant']) && (string) $_POST['is_compliant'] === '1' ? 1 : 0;
$date = $_POST['date'] ?? '';
$area = $_POST['area'] ?? '';

// Designation empty check removed
if ($attendance_id <= 0 || empty($time_recorded)) {
    $redirect = 'view_attendance.php?date=' . urlencode($date);
    if ($area !== '') {
        $redirect .= '&area=' . urlencode($area);
    }
    $redirect .= '&error=attendance_update_failed';
    header('Location: ' . $redirect);
    exit();
}

$parsed_time = DateTime::createFromFormat('Y-m-d\TH:i', $time_recorded);
if (!$parsed_time) {
    $redirect = 'view_attendance.php?date=' . urlencode($date);
    if ($area !== '') {
        $redirect .= '&area=' . urlencode($area);
    }
    $redirect .= '&error=attendance_update_failed';
    header('Location: ' . $redirect);
    exit();
}

$new_time_recorded = $parsed_time->format('Y-m-d H:i:00');

try {
    $checkSql = "SELECT COUNT(*) FROM attendance_record WHERE attendance_id = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$attendance_id]);

    if ((int) $checkStmt->fetchColumn() === 0) {
        $redirect = 'view_attendance.php?date=' . urlencode($date);
        if ($area !== '') {
            $redirect .= '&area=' . urlencode($area);
        }
        $redirect .= '&error=attendance_update_failed';
        header('Location: ' . $redirect);
        exit();
    }

    // Designation removed from the UPDATE statement
    $sql = "UPDATE attendance_record
            SET time_recorded = ?, with_id = ?, is_asean = ?, is_compliant = ?
            WHERE attendance_id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $new_time_recorded,
        // Designation removed from execution parameters
        $with_id,
        $proper_attire,
        $is_compliant,
        $attendance_id,
    ]);

    $redirect = 'view_attendance.php?date=' . urlencode($date);
    if ($area !== '') {
        $redirect .= '&area=' . urlencode($area);
    }
    $redirect .= '&msg=attendance_updated';
    header('Location: ' . $redirect);
    exit();
} catch (PDOException $e) {
    error_log('Attendance update failed: ' . $e->getMessage());

    $redirect = 'view_attendance.php?date=' . urlencode($date);
    if ($area !== '') {
        $redirect .= '&area=' . urlencode($area);
    }
    $redirect .= '&error=attendance_update_failed';
    header('Location: ' . $redirect);
    exit();
}
?>