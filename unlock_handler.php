<?php
session_start();
header('Content-Type: application/json');
include 'connect.php';

// Security check: Ensure the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$action = $_POST['action'] ?? '';
$date = $_POST['date'] ?? '';

// Auto-create the unlocked_dates table if it doesn't exist yet
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS unlocked_dates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        target_date DATE UNIQUE
    )");
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}

// Handle the AJAX requests
if ($action === 'unlock') {
    if ($date) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO unlocked_dates (target_date) VALUES (?)");
        if ($stmt->execute([$date])) {
            echo json_encode(['success' => true, 'message' => 'Date unlocked successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to unlock date.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid date provided.']);
    }
} elseif ($action === 'lock') {
    if ($date) {
        $stmt = $pdo->prepare("DELETE FROM unlocked_dates WHERE target_date = ?");
        if ($stmt->execute([$date])) {
            echo json_encode(['success' => true, 'message' => 'Date removed successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to lock date.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid date provided.']);
    }
} elseif ($action === 'fetch') {
    $stmt = $pdo->query("SELECT target_date FROM unlocked_dates ORDER BY target_date DESC");
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['success' => true, 'dates' => $dates]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
?>