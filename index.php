<?php
session_start();
// Set timezone to Manila to ensure correct 8:00 AM detection
date_default_timezone_set('Asia/Manila');

include 'connect.php';
if (!$pdo) {
    die('Database connection failed: ' . ($db_error ?? 'Unknown error'));
}

// Initialize variables
$employees = [];
$db_error = null;
$message = null;
$messageType = null;
$is_monday = (date('l') === 'Monday'); // Check if today is Monday

// Auto-add 'status' column to the database if it doesn't exist yet
$hasStatusColumn = false;
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM attendance_record LIKE 'status'");
    if ($colCheck->rowCount() == 0) {
        $pdo->exec("ALTER TABLE attendance_record ADD COLUMN status ENUM('On Time', 'Late') DEFAULT 'On Time' AFTER is_asean");
        $hasStatusColumn = true;
    } else {
        $hasStatusColumn = true;
    }
} catch (PDOException $e) {
    $hasStatusColumn = false; // Fallback if user doesn't have ALTER privileges
}

// Auto-add 'photo_path' column to the database for the image capture
$hasPhotoColumn = false;
try {
    $colCheckPhoto = $pdo->query("SHOW COLUMNS FROM attendance_record LIKE 'photo_path'");
    if ($colCheckPhoto->rowCount() == 0) {
        $pdo->exec("ALTER TABLE attendance_record ADD COLUMN photo_path VARCHAR(255) NULL AFTER status");
        $hasPhotoColumn = true;
    } else {
        $hasPhotoColumn = true;
    }
} catch (PDOException $e) {
    $hasPhotoColumn = false;
}
    
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'mark_attendance' && isset($_POST['emp_id'])) {
        
        // Backend safeguard: Block submission if it's not Monday
        if (!$is_monday) {
            $message = "Action denied. Attendance can only be recorded on Mondays.";
            $messageType = "error";
        } else {
            // 1. Gather Basic Variables
            $emp_id = $_POST['emp_id'];
            $with_id = $_POST['with_id'] ?? 'No';
            $is_asean = $_POST['is_asean'] ?? 'No';
            $photo_data = $_POST['photo_data'] ?? null;
            $photo_path = null;

            // 2. Logic to determine if On Time or Late
            $currentTime = date('H:i:s');
            $status = ($currentTime > '08:00:00') ? 'Late' : 'On Time';

            // 3. NEW COMPLIANCE LOGIC
            $is_compliant = 0; // Default to 0 (non-compliant)
            // Check if ALL conditions are met: On Time, Has ID, Proper Attire
            if ($status === 'On Time' && $with_id === 'Yes' && $is_asean === 'Yes') {
                $is_compliant = 1; // 1 means compliant
            }

            // 4. Handle Image Upload
            if (!empty($photo_data)) {
                $image_parts = explode(";base64,", $photo_data);
                if (count($image_parts) == 2) {
                    $image_type_aux = explode("image/", $image_parts[0]);
                    $image_type = isset($image_type_aux[1]) ? $image_type_aux[1] : 'jpeg';
                    $image_base64 = base64_decode($image_parts[1]);
                    
                    // Define upload directory
                    $upload_dir = 'uploads/attendance/' . $emp_id . '/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Set filename and save file
                    $current_datetime = date('Y-m-d_H-i-s');
                    $file_name = $emp_id . '_' . $current_datetime . '.' . $image_type;
                    $photo_path = $upload_dir . $file_name;
                    file_put_contents($photo_path, $image_base64);
                }
            }

            // 5. Fetch employee data to get their designation
            $empStmt = $pdo->prepare("SELECT full, designation FROM employees WHERE emp_id = ?");
            $empStmt->execute([$emp_id]);
            $empData = $empStmt->fetch(PDO::FETCH_ASSOC);

            if ($empData) {
                $designation = $empData['designation'] ?? 'Employee';

                try {
                    // 6. Execute ONE SINGLE INSERT Statement
                    $sql = "INSERT INTO attendance_record 
                            (emp_id, designation, with_id, is_asean, status, photo_path, is_compliant, time_recorded) 
                            VALUES (:emp_id, :designation, :with_id, :is_asean, :status, :photo_path, :is_compliant, NOW())";

                    $stmt = $pdo->prepare($sql);
                    
                    $execResult = $stmt->execute([
                        ':emp_id' => $emp_id,
                        ':designation' => $designation,
                        ':with_id' => $with_id,
                        ':is_asean' => $is_asean,
                        ':status' => $status,
                        ':photo_path' => $photo_path, // Included correctly here
                        ':is_compliant' => $is_compliant
                    ]);

                    if ($execResult) {
                        if ($status === 'Late') {
                            $message = "Attendance recorded for " . htmlspecialchars($empData['full']) . ". Note: You are marked as LATE.";
                            $messageType = "warning";
                        } else {
                            $message = "Attendance successfully recorded for " . htmlspecialchars($empData['full']) . "!";
                            $messageType = "success";
                        }
                    }
                } catch (PDOException $e) {
                    $message = "Database Error: " . $e->getMessage();
                    $messageType = "error";
                }
            } else {
                $message = "Invalid employee selected.";
                $messageType = "error";
            }
        } // End of monday check
    }
}

try {
    $stmt = $pdo->query("SELECT emp_id, full, designation FROM employees WHERE full IS NOT NULL AND full != '' ORDER BY full ASC");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $db_error = "Database query failed: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DICT Monday Flag Raising - Attendance</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts: Open Sans for Inspinia Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Select2 & SweetAlert -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Inspinia Theme Core Variables & Styles matched from index.php */
        body {
            background-color: #f3f3f4;
            background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('img/bg/philippine_bg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #676a6c;
            font-family: 'Open Sans', helvetica, arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px 0;
        }

        .loginscreen.middle-box {
            width: 420px;
            max-width: 90%;
            z-index: 100;
            text-align: center;
            background-color: rgba(255, 255, 255, 0.85);
            padding: 30px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-container {
            margin-bottom: 15px;
        }

        h3 {
            font-weight: 600;
            margin-top: 10px;
            font-size: 24px;
            color: #333;
        }

        .ibox-content {
            background-color: #ffffff;
            color: inherit;
            padding: 25px 20px 20px 20px;
            border-color: #e7eaec;
            border-style: solid solid none;
            border-width: 1px 0;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            text-align: left;
        }

        /* Form Controls */
        .form-control {
            border: 1px solid #e5e6e7;
            border-radius: 2px;
            padding: 8px 12px;
            font-size: 14px;
            box-shadow: none;
            transition: border-color 0.15s ease-in-out 0s;
        }
        .form-control:focus {
            border-color: #1ab394;
            box-shadow: none;
        }
        .form-control:disabled {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }

        /* Buttons */
        .btn {
            border-radius: 3px;
            font-size: 14px;
            font-weight: 600;
            padding: 8px 12px;
        }
        
        .btn-primary { 
            background-color: #1ab394;
            border-color: #1ab394;
            color: #FFFFFF;
        }
        .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
            background-color: #18a689 !important;
            border-color: #18a689 !important;
            color: #FFFFFF !important;
        }

        .btn-white {
            color: inherit;
            background: white;
            border: 1px solid #e7eaec;
        }
        .btn-white:hover, .btn-white:focus {
            color: inherit;
            border: 1px solid #d2d2d2;
            background: #f8f9fa;
        }

        /* Live Clock styling */
        .clock-panel {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #e7eaec;
        }
        #live-clock {
            font-size: 2rem;
            font-weight: 700;
            color: #1ab394;
            margin-bottom: 0;
            line-height: 1.2;
        }
        #live-date {
            font-size: 0.85rem;
            font-weight: 600;
            color: #a7b1c2;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Toggle Switches adapted for Inspinia */
        .toggle-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f3f3f4;
        }
        .toggle-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .toggle-row label {
            font-weight: 600;
            color: #676a6c;
            margin-bottom: 0;
            cursor: pointer;
            font-size: 14px;
        }
        .form-check-input {
            width: 3em !important;
            height: 1.5em !important;
            cursor: pointer;
            border-color: #e5e6e7;
        }
        .form-check-input:checked {
            background-color: #1ab394;
            border-color: #1ab394;
        }
        .form-check-input:focus {
            box-shadow: 0 0 0 0.25rem rgba(26, 179, 148, 0.25);
            border-color: #1ab394;
        }
        .form-check-input:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        /* Select2 Inspinia Integration */
        .select2-container--default .select2-selection--single {
            border: 1px solid #e5e6e7 !important;
            border-radius: 2px !important;
            height: 36px !important;
            display: flex;
            align-items: center;
        }
        .select2-container--default.select2-container--open .select2-selection--single,
        .select2-container--default .select2-selection--single:focus {
            border-color: #1ab394 !important;
            outline: none;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #676a6c !important;
            padding-left: 12px !important;
            font-size: 14px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 34px !important;
            right: 6px !important;
        }
        .select2-dropdown {
            border-color: #e5e6e7 !important;
            border-radius: 2px !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
        }
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: #1ab394 !important;
        }

        /* Camera overlay style */
        .camera-overlay {
            position: absolute;
            top: 10%;
            bottom: 10%;
            left: 15%;
            right: 15%;
            border: 2px dashed rgba(255, 255, 255, 0.7);
            border-radius: 20px;
            pointer-events: none;
        }

        /* Countdown text */
        .countdown-timer {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 6rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 700;
            text-shadow: 0px 4px 15px rgba(0, 0, 0, 0.6);
            z-index: 10;
            pointer-events: none;
        }

        .alert {
            border-radius: 3px;
            font-size: 13px;
            padding: 10px;
        }
    </style>
</head>
<body>

<div class="middle-box loginscreen">
    
    <!-- Logo Section -->
    <div class="logo-container">
        <img src="img/logo/DICT.png" alt="DICT Logo" class="img-fluid" style="max-height: 130px; object-fit: contain;">
    </div>
    
    <h3>DICT Monday Flag Raising</h3>
    <p class="text-muted">Attendance Checker</p>

    <!-- Display Database Errors -->
    <?php if ($db_error): ?>
        <div class="alert alert-danger text-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($db_error); ?>
        </div>
    <?php endif; ?>

    <div class="ibox-content mb-3">
        
        <!-- Live Clock -->
        <div class="clock-panel">
            <div id="live-clock">00:00:00 AM</div>
            <div id="live-date">Loading Date...</div>
        </div>

        <form id="attendance-form" method="POST" action="index.php">
            <input type="hidden" name="action" value="mark_attendance">
            <!-- Hidden field for the captured photo -->
            <input type="hidden" name="photo_data" id="photo_data" value="">
            
            <div class="mb-3">
                <label class="form-label text-muted fw-bold small mb-1">Employee Name</label>
                <select id="emp_id" name="emp_id" class="form-control" required <?php echo !$is_monday ? 'disabled' : ''; ?>>
                    <option value=""></option> 
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo htmlspecialchars($emp['emp_id']); ?>">
                            <?php echo htmlspecialchars($emp['full']); ?> 
                            <?php if(!empty($emp['designation'])) echo ' (' . htmlspecialchars($emp['designation']) . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <div class="toggle-row">
                    <label class="form-check-label" for="with_id">
                        <i class="bi bi-person-vcard me-1 text-muted"></i> Wearing your ID?
                    </label>
                    <div class="form-check form-switch m-0 p-0">
                        <input class="form-check-input m-0 float-end" type="checkbox" role="switch" name="with_id" id="with_id" value="Yes" <?php echo !$is_monday ? 'disabled' : ''; ?>>
                    </div>
                </div>
                <div class="toggle-row">
                    <label class="form-check-label" for="is_asean">
                        <i class="bi bi-suit-tie me-1 text-muted"></i> Wearing Formal Attire?
                    </label>
                    <div class="form-check form-switch m-0 p-0">
                        <input class="form-check-input m-0 float-end" type="checkbox" role="switch" name="is_asean" id="is_asean" value="Yes" <?php echo !$is_monday ? 'disabled' : ''; ?>>
                    </div>
                </div>
            </div>

            <?php if ($is_monday): ?>
                <button type="button" onclick="confirmSignIn()" class="btn btn-primary w-100 d-block">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                </button>
            <?php else: ?>
                <div class="alert alert-warning text-center p-2 mb-3" style="font-size: 13px; background-color: #fcf8e3; border-color: #faebcc; color: #8a6d3b;">
                    <i class="bi bi-info-circle-fill"></i> Attendance is only available on Mondays.
                </div>
                <button type="button" class="btn w-100 d-block" disabled style="background-color: #e5e6e7; border-color: #e5e6e7; color: #888; cursor: not-allowed;">
                    <i class="bi bi-lock-fill me-1"></i> Sign In Locked
                </button>
            <?php endif; ?>
        </form>
    </div>

    <!-- Navigation Switch Button -->
    <a href="login.php" class="btn btn-white w-100">
        <i class="bi bi-arrow-left me-1"></i> Back to Login Page
    </a>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="js/attendance.js"></script>

<script>
    // Keep the PHP dynamic alerts inside index.php
    <?php if ($message): ?>
        Swal.fire({
            icon: '<?php echo $messageType; ?>',
            title: '<?php echo $messageType === 'success' ? 'Recorded!' : ($messageType === 'warning' ? 'Recorded (Late)' : 'Error'); ?>',
            text: '<?php echo addslashes($message); ?>',
            confirmButtonColor: '#1ab394'
        });
    <?php endif; ?>
</script>

</body>
</html>