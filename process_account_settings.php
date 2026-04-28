<?php
session_start();

// Only accept POST from logged-in users
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my_attendance.php');
    exit;
}

include 'connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$userId = (int)$_SESSION['user_id'];
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$emp_email = isset($_POST['emp_email']) ? trim($_POST['emp_email']) : '';
$full = isset($_POST['full']) ? trim($_POST['full']) : '';
$current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
$new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

try {
    $conn->begin_transaction();

    // Fetch existing account data
    $stmt = $conn->prepare("SELECT id, emp_id, username, password FROM user_account WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($db_id, $db_emp_id, $db_username, $db_password_hash);
    if (!$stmt->fetch()) {
        $stmt->close();
        $conn->rollback();
        header('Location: my_attendance.php?account_update=error&reason=' . urlencode('Account not found'));
        exit;
    }
    $stmt->close();

    // Username cannot be empty
    if ($username === '') {
        $conn->rollback();
        header('Location: my_attendance.php?account_update=error&reason=' . urlencode('Username cannot be empty'));
        exit;
    }

    // Check for username change and uniqueness
    if ($username !== $db_username) {
        $chk = $conn->prepare("SELECT id FROM user_account WHERE username = ? AND id != ?");
        $chk->bind_param('si', $username, $userId);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $chk->close();
            $conn->rollback();
            header('Location: my_attendance.php?account_update=error&reason=' . urlencode('Username already taken'));
            exit;
        }
        $chk->close();
    }

    // Determine if password update is requested
    $doPasswordUpdate = false;
    if ($new_password !== '' || $confirm_password !== '') {
        // require current password
        if ($current_password === '') {
            $conn->rollback();
            header('Location: my_attendance.php?account_update=error&reason=' . urlencode('Current password is required to change password'));
            exit;
        }

        // verify current password
        $verified = false;
        if (is_string($db_password_hash) && password_verify($current_password, $db_password_hash)) {
            $verified = true;
        } elseif ($current_password === $db_password_hash) {
            // fallback for legacy plain text
            $verified = true;
        }

        if (!$verified) {
            $conn->rollback();
            header('Location: my_attendance.php?account_update=error&reason=' . urlencode('Current password is incorrect'));
            exit;
        }

        if ($new_password !== $confirm_password) {
            $conn->rollback();
            header('Location: my_attendance.php?account_update=error&reason=' . urlencode('New password and confirmation do not match'));
            exit;
        }

        if (strlen($new_password) < 8) {
            $conn->rollback();
            header('Location: my_attendance.php?account_update=error&reason=' . urlencode('New password must be at least 8 characters'));
            exit;
        }

        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $doPasswordUpdate = true;
    }

    // Update user_account (username and optionally password)
    if ($username !== $db_username && !$doPasswordUpdate) {
        $u = $conn->prepare("UPDATE user_account SET username = ? WHERE id = ?");
        $u->bind_param('si', $username, $userId);
        $u->execute();
        $u->close();
    } elseif ($username !== $db_username && $doPasswordUpdate) {
        $u = $conn->prepare("UPDATE user_account SET username = ?, password = ? WHERE id = ?");
        $u->bind_param('ssi', $username, $hashed, $userId);
        $u->execute();
        $u->close();
    } elseif ($doPasswordUpdate) {
        $u = $conn->prepare("UPDATE user_account SET password = ? WHERE id = ?");
        $u->bind_param('si', $hashed, $userId);
        $u->execute();
        $u->close();
    }

    // Update employee email if emp_id is available
    if (!empty($db_emp_id)) {
        if (!empty($emp_email)) {
            $e = $conn->prepare("UPDATE employees SET emp_email = ? WHERE emp_id = ?");
            $e->bind_param('si', $emp_email, $db_emp_id);
            $e->execute();
            $e->close();
        }

        // Allow updating full name for admins changing their own profile
        if (!empty($full)) {
            $f = $conn->prepare("UPDATE employees SET full = ? WHERE emp_id = ?");
            $f->bind_param('si', $full, $db_emp_id);
            $f->execute();
            $f->close();

            // Refresh session fullname if this is the current user
            $_SESSION['fullname'] = $full;
        }
    }

    $conn->commit();

    // Refresh session username
    $_SESSION['username'] = $username;

    // Redirect based on role: admin -> admin_account.php, others -> my_account.php
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: admin_account.php?account_update=success');
    } else {
        header('Location: my_account.php?account_update=success');
    }
    exit;

} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    if ($e->getCode() == 1062) {
        // Preserve role-based redirect for errors
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            header('Location: admin_account.php?account_update=error&reason=' . urlencode('Username already taken'));
        } else {
            header('Location: my_account.php?account_update=error&reason=' . urlencode('Username already taken'));
        }
        exit;
    }
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: admin_account.php?account_update=error&reason=' . urlencode('Database error'));
    } else {
        header('Location: my_account.php?account_update=error&reason=' . urlencode('Database error'));
    }
    exit;
}

?>
