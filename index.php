<?php
session_start();
// Set timezone to Manila
date_default_timezone_set('Asia/Manila');

include 'connect.php';
if (!$pdo) {
    die('Database connection failed: ' . ($db_error ?? 'Unknown error'));
}

// Ensure unlocked_dates table exists for holiday overrides
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS unlocked_dates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        target_date DATE UNIQUE
    )");
} catch (PDOException $e) {
    // ignore initialization errors; attendance logic will still work if table is unavailable
}

// Initialize variables
$employees = [];
$db_error = null;
$message = null;
$messageType = null;
$current_date = date('Y-m-d');
$is_monday = (date('l') === 'Monday'); // Check if today is Monday
$is_unlocked = false;
$can_sign_in = false;

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM unlocked_dates WHERE target_date = ?");
    $stmt->execute([$current_date]);
    $is_unlocked = ($stmt->fetchColumn() > 0);
} catch (PDOException $e) {
    $is_unlocked = false;
}

$can_sign_in = $is_monday || $is_unlocked;

// Auto-add 'status' column to the database if it doesn't exist yet (Kept for database backward compatibility)
try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM attendance_record LIKE 'status'");
    if ($colCheck->rowCount() == 0) {
        $pdo->exec("ALTER TABLE attendance_record ADD COLUMN status ENUM('On Time', 'Late') DEFAULT 'On Time' AFTER is_asean");
    }
} catch (PDOException $e) {}

// Auto-add 'photo_path' column to the database for the image capture
try {
    $colCheckPhoto = $pdo->query("SHOW COLUMNS FROM attendance_record LIKE 'photo_path'");
    if ($colCheckPhoto->rowCount() == 0) {
        $pdo->exec("ALTER TABLE attendance_record ADD COLUMN photo_path VARCHAR(255) NULL AFTER status");
    }
} catch (PDOException $e) {}

// Auto-add 'is_compliant' column to the database
try {
    $colCheckComp = $pdo->query("SHOW COLUMNS FROM attendance_record LIKE 'is_compliant'");
    if ($colCheckComp->rowCount() == 0) {
        $pdo->exec("ALTER TABLE attendance_record ADD COLUMN is_compliant TINYINT(1) DEFAULT 0 AFTER photo_path");
    }
} catch (PDOException $e) {}
    
// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'mark_attendance' && isset($_POST['emp_id'])) {
        
        // Backend safeguard: Block submission if attendance is not open today
        if (!$can_sign_in) {
            $message = "Action denied. Attendance is only open on Mondays or on admin-unlocked dates.";
            $messageType = "error";
        } else {
            $emp_id = $_POST['emp_id'];
            // Check if already submitted today
            $checkSql = "SELECT COUNT(*) FROM attendance_record WHERE emp_id = ? AND DATE(time_recorded) = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$emp_id, $current_date]);
            if ($checkStmt->fetchColumn() > 0) {
                $message = "Attendance already submitted for today.";
                $messageType = "error";
            } else {
                // 1. Gather Basic Variables
                $with_id = $_POST['with_id'] ?? 'No';
                $is_asean = $_POST['is_asean'] ?? 'No';
                $photo_data = $_POST['photo_data'] ?? null;
                $photo_path = null;

<<<<<<< Updated upstream
            // 2. NEW COMPLIANCE LOGIC & TIME CHECK
            $current_time = date('H:i');
            $is_late = ($current_time >= '08:30'); // True if it's 8:30 AM or later
            $status = $is_late ? 'Late' : 'On Time';
            
            $is_compliant = 0; // Default to 0 (non-compliant)
            
            // Check if conditions are met: Has ID, Proper Attire, AND is On Time
            if ($with_id === 'Yes' && $is_asean === 'Yes' && !$is_late) {
=======
            // 2. NEW COMPLIANCE LOGIC (ID and Attire)
            $is_compliant = 0; // Default to 0 (non-compliant)
            if ($with_id === 'Yes' && $is_asean === 'Yes') {
>>>>>>> Stashed changes
                $is_compliant = 1; // 1 means compliant
            }

            // NEW STATUS LOGIC: On Time (Before/At 8:30 AM) vs Late (After 8:30 AM)
            $currentTime = date('H:i');
            $status = ($currentTime <= '08:30') ? 'On Time' : 'Late';

            // 3. Handle Image Upload
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

            // 4. Fetch employee data to get their designation
            $empStmt = $pdo->prepare("SELECT full, designation FROM employees WHERE emp_id = ?");
            $empStmt->execute([$emp_id]);
            $empData = $empStmt->fetch(PDO::FETCH_ASSOC);

            if ($empData) {
                $designation = $empData['designation'] ?? 'Employee';

                try {
                    // 5. Execute ONE SINGLE INSERT Statement including the Status
                    $sql = "INSERT INTO attendance_record 
<<<<<<< Updated upstream
                            (emp_id, designation, with_id, is_asean, status, photo_path, is_compliant, time_recorded) 
                            VALUES (:emp_id, :designation, :with_id, :is_asean, :status, :photo_path, :is_compliant, NOW())";
=======
                            (emp_id, designation, with_id, is_asean, photo_path, is_compliant, status, time_recorded) 
                            VALUES (:emp_id, :designation, :with_id, :is_asean, :photo_path, :is_compliant, :status, NOW())";
>>>>>>> Stashed changes

                    $stmt = $pdo->prepare($sql);
                    
                    $execResult = $stmt->execute([
                        ':emp_id' => $emp_id,
                        ':designation' => $designation,
                        ':with_id' => $with_id,
                        ':is_asean' => $is_asean,
                        ':status' => $status,
                        ':photo_path' => $photo_path,
                        ':is_compliant' => $is_compliant,
                        ':status' => $status
                    ]);

                    if ($execResult) {
                        $message = "Attendance successfully recorded for " . htmlspecialchars($empData['full']) . "!";
                        $messageType = "success";
                    }
                } catch (PDOException $e) {
                    $message = "Database Error: " . $e->getMessage();
                    $messageType = "error";
                }
            } else {
                $message = "Invalid employee selected.";
                $messageType = "error";
            }
        }
    }
}

// Close POST handler
}

// Fetch Employees for the Dropdown Form
try {
    $empListStmt = $pdo->query("SELECT emp_id, full, designation FROM employees WHERE full IS NOT NULL AND full != '' ORDER BY full ASC");
    $employees = $empListStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $db_error = "Database query failed: " . $e->getMessage();
}

<<<<<<< Updated upstream
// Note: Table fetching and pagination logic has been moved to recent_records.php
=======
// --- DATA TABLE PAGINATION & FETCHING ---
// Define allowed limits and get current page/limit from URL
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], [10, 25, 50]) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total records for calculating pagination pages
$countSql = "SELECT COUNT(*) FROM attendance_record a JOIN employees e ON a.emp_id = e.emp_id";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute();
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Fetch the attendance records across all employees (Added a.status)
$tableSql = "SELECT a.designation, a.with_id, a.is_asean, a.is_compliant, a.status, a.time_recorded, a.photo_path, 
               e.full, e.area_of_assignment, e.department, e.unit 
        FROM attendance_record a
        JOIN employees e ON a.emp_id = e.emp_id
        ORDER BY a.time_recorded DESC
        LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

$tableStmt = $pdo->prepare($tableSql);
$tableStmt->execute();
$attendance_records = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
>>>>>>> Stashed changes

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DICT Monday Flag Raising - Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Base page styles */
        body {
            background-color: #f3f3f4;
            background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('img/bg/philippine_bg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #676a6c;
            font-family: 'Open Sans', helvetica, arial, sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center; /* Center vertically */
            justify-content: center; /* Center horizontally */
            padding: 20px;
        }

        /* --- LANDSCAPE KIOSK STYLES --- */
        .landscape-kiosk {
            background-color: rgba(255, 255, 255, 0.98);
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.4);
            width: 100%;
            max-width: 900px;
            overflow: hidden; /* Contains the border-radius for child elements */
        }

        /* Left Side (Branding & Clock) */
        .brand-side {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 50px 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border-right: 1px solid #e7eaec;
            text-align: center;
        }
        .brand-side h3 { font-weight: 700; margin-top: 15px; font-size: 24px; color: #2f4050; }
        .brand-side p { color: #888; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; font-size: 13px; }

        /* Live Clock styling inside left side */
        .clock-panel { margin-top: 30px; padding-top: 30px; border-top: 1px dashed #d1dade; width: 100%; }
        #live-clock { font-size: 2.2rem; font-weight: 700; color: #1ab394; line-height: 1.2; letter-spacing: -1px; }
        #live-date { font-size: 0.9rem; font-weight: 600; color: #a7b1c2; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Right Side (Form) */
        .form-side {
            padding: 50px 40px;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Form Controls */
        .form-control {
            border: 1px solid #e5e6e7;
            border-radius: 4px;
            padding: 10px 14px;
            font-size: 14px;
            box-shadow: none;
        }
        .form-control:focus { border-color: #1ab394; box-shadow: none; }
        .form-control:disabled { background-color: #f8f9fa; cursor: not-allowed; }

        /* Buttons */
        .btn { border-radius: 4px; font-size: 14px; font-weight: 600; padding: 10px 15px; }
        .btn-primary { background-color: #1ab394; border-color: #1ab394; color: #FFFFFF; }
        .btn-primary:hover, .btn-primary:focus, .btn-primary:active { background-color: #18a689 !important; border-color: #18a689 !important; color: #FFFFFF !important; }
        .btn-white { color: inherit; background: white; border: 1px solid #e7eaec; }
        .btn-white:hover, .btn-white:focus { color: inherit; border: 1px solid #d2d2d2; background: #f8f9fa; }
        .btn-info-custom { background-color: #23c6c8; border-color: #23c6c8; color: #FFFFFF; }
        .btn-info-custom:hover { background-color: #21b9bb; border-color: #21b9bb; color: #FFFFFF; }

<<<<<<< Updated upstream
        /* Toggle Switches */
        .toggle-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f3f3f4; }
        .toggle-row:last-child { border-bottom: none; }
=======
        /* Live Clock styling */
        .clock-panel { text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px dashed #e7eaec; }
        #live-clock { font-size: 2.5rem; font-weight: 700; color: #1ab394; margin-bottom: 0; line-height: 1.2; }
        #live-date { font-size: 0.95rem; font-weight: 600; color: #a7b1c2; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Toggle Switches adapted for Inspinia */
        .toggle-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f3f3f4; }
        .toggle-row:last-child { border-bottom: none; padding-bottom: 0; }
>>>>>>> Stashed changes
        .toggle-row label { font-weight: 600; color: #676a6c; margin-bottom: 0; cursor: pointer; font-size: 14px; }
        .form-check-input { width: 3.5em !important; height: 1.75em !important; cursor: pointer; border-color: #e5e6e7; }
        .form-check-input:checked { background-color: #1ab394; border-color: #1ab394; }
        .form-check-input:focus { box-shadow: 0 0 0 0.25rem rgba(26, 179, 148, 0.25); border-color: #1ab394; }
        .form-check-input:disabled { cursor: not-allowed; opacity: 0.5; }

        /* Select2 Integration */
        .select2-container--default .select2-selection--single {
            border: 1px solid #e5e6e7 !important; border-radius: 4px !important; height: 42px !important; display: flex; align-items: center;
        }
        .select2-container--default.select2-container--open .select2-selection--single,
        .select2-container--default .select2-selection--single:focus { border-color: #1ab394 !important; outline: none; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { color: #676a6c !important; padding-left: 14px !important; font-size: 14px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px !important; right: 10px !important; }
        .select2-dropdown { border-color: #e5e6e7 !important; border-radius: 4px !important; box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important; }
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable { background-color: #1ab394 !important; }

        /* Camera overlay style */
        .camera-overlay { position: absolute; top: 10%; bottom: 10%; left: 15%; right: 15%; border: 2px dashed rgba(255, 255, 255, 0.7); border-radius: 20px; pointer-events: none; }
        .countdown-timer { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 6rem; color: rgba(255, 255, 255, 0.9); font-weight: 700; text-shadow: 0px 4px 15px rgba(0, 0, 0, 0.6); z-index: 10; pointer-events: none; }

        /* Mobile Adjustments */
        @media (max-width: 767.98px) {
            .landscape-kiosk { flex-direction: column; max-width: 450px; }
            .brand-side { border-right: none; border-bottom: 1px solid #e7eaec; padding: 30px 20px; }
            .form-side { padding: 30px 20px; }
            #live-clock { font-size: 1.8rem; }
        }

        /* --- DATA TABLE STYLES --- */
<<<<<<< Updated upstream
        .label { font-size: 10px; font-weight: 600; padding: 3px 8px; border-radius: 0.25em; }
=======
        .ibox-table {
            background-color: rgba(255, 255, 255, 0.95);
            border-top: 4px solid #1ab394;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            padding: 20px;
            height: 100%;
        }

        /* Labels (Badges) */
        .label {
            font-family: 'Open Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            font-weight: 600;
            padding: 4px 10px;
            text-shadow: none;
            border-radius: 0.25em;
            display: inline-block;
        }
>>>>>>> Stashed changes
        .label-primary { background-color: #1ab394; color: #FFFFFF; }
        .label-danger { background-color: #ed5565; color: #FFFFFF; }
        .label-info { background-color: #23c6c8; color: #FFFFFF; }
        .label-success { background-color: #1c84c6; color: #FFFFFF; }
        .label-warning { background-color: #f8ac59; color: #FFFFFF; }
        .label-plain { background-color: #d1dade; color: #5e5e5e; }

<<<<<<< Updated upstream
=======
        /* Tables */
        .table { font-size: 14px; background-color: transparent; }
>>>>>>> Stashed changes
        .table > thead > tr > th { border-bottom: 1px solid #e7eaec; font-weight: 600; color: #333; }
        .table > tbody > tr > td { border-top: 1px solid #e7eaec; vertical-align: middle; }

        .pagination > li > a, .pagination > li > span { color: #676a6c; border: 1px solid #e7eaec; margin-left: -1px; padding: 5px 10px; text-decoration: none; }
        .pagination > li.active > a, .pagination > li.active > span { background-color: #1ab394; border-color: #1ab394; color: #fff; }

        .profile-modal-content { border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .table-modal-content { border-top: 4px solid #1ab394; border-radius: 8px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    </style>
</head>
<body>

<<<<<<< Updated upstream
<!-- ========================================== -->
<!-- CENTERED: THE LANDSCAPE KIOSK              -->
<!-- ========================================== -->
<div class="landscape-kiosk mx-auto row g-0">
    
    <!-- LEFT SIDE: Branding & Info -->
    <div class="col-md-5 brand-side">
        <div class="logo-container">
            <img src="img/logo/DICT.png" alt="DICT Logo" class="img-fluid" style="max-height: 140px; object-fit: contain;">
        </div>
        
        <h3>DICT Monday Flag Raising</h3>
        <p>Attendance Checker</p>

        <!-- Live Clock -->
        <div class="clock-panel">
            <div id="live-clock">00:00:00 AM</div>
            <div id="live-date">Loading Date...</div>
=======
<div class="container-fluid px-4">
    
    <div class="row justify-content-center mb-5">
        <div class="col-lg-6 col-md-8">
            <div class="loginscreen mx-auto">
                
                <div class="logo-container">
                    <img src="img/logo/DICT.png" alt="DICT Logo" class="img-fluid" style="max-height: 130px; object-fit: contain;">
                </div>
                
                <h3>DICT Monday Flag Raising</h3>
                <p class="text-muted">Attendance Checker</p>

                <?php if ($db_error): ?>
                    <div class="alert alert-danger text-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($db_error); ?>
                    </div>
                <?php endif; ?>

                <div class="ibox-content mb-3">
                    
                    <div class="clock-panel">
                        <div id="live-clock">00:00:00 AM</div>
                        <div id="live-date">Loading Date...</div>
                    </div>

                    <form id="attendance-form" method="POST" action="index.php">
                        <input type="hidden" name="action" value="mark_attendance">
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
                            <button type="button" onclick="confirmSignIn()" class="btn btn-primary w-100 d-block py-2 fs-5">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                            </button>
                        <?php else: ?>
                            <div class="alert alert-warning text-center p-2 mb-3" style="font-size: 14px; background-color: #fcf8e3; border-color: #faebcc; color: #8a6d3b;">
                                <i class="bi bi-info-circle-fill"></i> Attendance is only available on Mondays.
                            </div>
                            <button type="button" class="btn w-100 d-block py-2 fs-5" disabled style="background-color: #e5e6e7; border-color: #e5e6e7; color: #888; cursor: not-allowed;">
                                <i class="bi bi-lock-fill me-1"></i> Sign In Locked
                            </button>
                        <?php endif; ?>
                    </form>
                </div>

                <a href="login.php" class="btn btn-white w-100 py-2">
                    <i class="bi bi-person-circle me-1"></i> Admin / Staff Login
                </a>

            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-12 mb-4">
            <div class="ibox-table">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0 text-dark fw-bold fs-4"><i class="bi bi-card-list me-2"></i> Recent Attendance Records</h5>
                </div>

                <div class="row mb-3 align-items-center">
                    <div class="col-sm-12">
                        <form method="GET" action="index.php" class="d-inline-flex align-items-center" id="entriesForm">
                            <label class="mb-0 me-2 text-muted fw-normal">Show</label>
                            <select name="limit" class="form-select form-select-sm w-auto d-inline-block shadow-none" onchange="document.getElementById('entriesForm').submit();">
                                <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                            </select>
                            <label class="mb-0 ms-2 text-muted fw-normal">entries</label>
                            <input type="hidden" name="page" value="1"> 
                        </form>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Employee Name</th>
                                <th class="text-center">Status</th>   
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($attendance_records) > 0): ?>
                                <?php foreach ($attendance_records as $record): ?>
                                    <?php 
                                        $hasId = ($record['with_id'] === 'Yes');
                                        $hasProperAttire = ($record['is_asean'] === 'Yes');
                                        
                                        // Retrieve the new status check from the database ('On Time' or 'Late')
                                        $recordStatus = $record['status'] ?? 'Late';

                                        // Format Date & Time for passing to photo modal
                                        $formattedDateTime = date('M d, Y - h:i A', strtotime($record['time_recorded']));
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo date('M d, Y', strtotime($record['time_recorded'])); ?></strong><br>
                                            <div class="mt-1 d-flex align-items-center">
                                                <small class="text-muted"><i class="bi bi-clock me-1"></i><?php echo date('h:i A', strtotime($record['time_recorded'])); ?></small>
                                            </div>
                                        </td>
                                        
                                        <td>
                                            <strong><?php echo htmlspecialchars($record['full'] ?? 'N/A'); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($record['designation'] ?? 'N/A'); ?></small>
                                        </td>
                                        
                                        <td class="text-center">
                                            <?php if ($recordStatus === 'On Time'): ?>
                                                <span class="label label-primary fs-6"><i class="bi bi-check-circle me-1"></i>On Time</span>
                                            <?php else: ?>
                                                <span class="label label-danger fs-6"><i class="bi bi-exclamation-circle me-1"></i>Late</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <h4 class="fw-light mb-1 mt-3">No Records Yet</h4>
                                        <p class="small text-muted mb-4">Waiting for the first attendance entry.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row mt-3 align-items-center">
                    <div class="col-sm-12 col-md-5">
                        <?php
                            $startEntry = ($totalRecords > 0) ? $offset + 1 : 0;
                            $endEntry = min($offset + $limit, $totalRecords);
                        ?>
                        <div class="text-muted" style="font-size: 14px;">
                            Showing <?php echo $startEntry; ?> to <?php echo $endEntry; ?> of <?php echo $totalRecords; ?> entries
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                        <?php if ($totalPages > 1): ?>
                            <ul class="pagination pagination-sm mb-0 list-unstyled d-flex">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?limit=<?php echo $limit; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                                </li>
                                
                                <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    
                                    if ($startPage > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?limit=' . $limit . '&page=1">1</a></li>';
                                        if ($startPage > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }

                                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?limit=<?php echo $limit; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; 
                                    
                                    if ($endPage < $totalPages) {
                                        if ($endPage < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        echo '<li class="page-item"><a class="page-link" href="?limit=' . $limit . '&page=' . $totalPages . '">' . $totalPages . '</a></li>';
                                    }
                                ?>

                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?limit=<?php echo $limit; ?>&page=<?php echo $page + 1; ?>">Next</a>
                                </li>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
>>>>>>> Stashed changes
        </div>
    </div>

    <!-- RIGHT SIDE: Form & Actions -->
    <div class="col-md-7 form-side">
        <!-- Display Database Errors -->
        <?php if ($db_error): ?>
            <div class="alert alert-danger text-center py-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($db_error); ?>
            </div>
        <?php endif; ?>

        <form id="attendance-form" method="POST" action="index.php">
            <input type="hidden" name="action" value="mark_attendance">
            <!-- Hidden field for the captured photo -->
            <input type="hidden" name="photo_data" id="photo_data" value="">
            
            <div class="mb-4">
                <label class="form-label text-muted fw-bold small mb-2 text-uppercase letter-spacing-1">Select Employee</label>
                <select id="emp_id" name="emp_id" class="form-control" required <?php echo !$can_sign_in ? 'disabled' : ''; ?>>
                    <option value=""></option> 
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?php echo htmlspecialchars($emp['emp_id']); ?>">
                            <?php echo htmlspecialchars($emp['full']); ?> 
                            <?php if(!empty($emp['designation'])) echo ' (' . htmlspecialchars($emp['designation']) . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4 bg-light rounded p-3 border">
                <div class="toggle-row pt-0">
                    <label class="form-check-label" for="with_id">
                        <i class="bi bi-person-vcard me-2 text-muted fs-5 align-middle"></i> Wearing your ID?
                    </label>
                    <div class="form-check form-switch m-0 p-0">
                        <input class="form-check-input m-0 float-end" type="checkbox" role="switch" name="with_id" id="with_id" value="Yes" <?php echo !$can_sign_in ? 'disabled' : ''; ?>>
                    </div>
                </div>
                <div class="toggle-row pb-0">
                    <label class="form-check-label" for="is_asean">
                        <i class="bi bi-suit-tie me-2 text-muted fs-5 align-middle"></i> Wearing Formal Attire?
                    </label>
                    <div class="form-check form-switch m-0 p-0">
                        <input class="form-check-input m-0 float-end" type="checkbox" role="switch" name="is_asean" id="is_asean" value="Yes" <?php echo !$can_sign_in ? 'disabled' : ''; ?>>
                    </div>
                </div>
            </div>

            <?php if ($is_unlocked && !$is_monday): ?>
                <div class="alert alert-info text-center p-2 mb-3" style="font-size: 13px;">
                    <i class="bi bi-info-circle-fill"></i> Attendance is open today because an admin unlocked this date.
                </div>
            <?php endif; ?>

            <?php if ($can_sign_in): ?>
                <button type="button" onclick="confirmSignIn()" class="btn btn-primary w-100 d-block py-2 mb-4">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Submit Attendance
                </button>
            <?php else: ?>
                <div class="alert alert-warning text-center p-2 mb-3" style="font-size: 13px; background-color: #fcf8e3; border-color: #faebcc; color: #8a6d3b;">
                    <i class="bi bi-info-circle-fill"></i> Attendance is only available on Mondays unless an admin unlocks today.
                </div>
                <button type="button" class="btn w-100 d-block py-2 mb-4" disabled style="background-color: #e5e6e7; border-color: #e5e6e7; color: #888; cursor: not-allowed;">
                    <i class="bi bi-lock-fill me-1"></i> Sign In Locked
                </button>
            <?php endif; ?>
        </form>

        <hr class="my-2 text-muted" style="opacity: 0.15;">

        <div class="row g-2 mt-2">
            <div class="col-6">
                <a href="login.php" class="btn btn-white w-100 h-100 d-flex align-items-center justify-content-center">
                    <i class="bi bi-person-circle me-2"></i> Admin Login
                </a>
            </div>
            <div class="col-6">
                <!-- Button to trigger Recent Records Modal -->
                <button type="button" class="btn btn-info-custom w-100 h-100 d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#tableRecordsModal">
                    <i class="bi bi-table me-2"></i> View Records
                </button>
            </div>
        </div>
        
    </div>
</div>

<<<<<<< Updated upstream
<!-- ========================================== -->
<!-- INCLUDE: RECENT RECORDS MODAL              -->
<!-- ========================================== -->
<?php include 'recent_records.php'; ?>

<!-- Photo Viewer Modal -->
=======
>>>>>>> Stashed changes
<div class="modal fade" id="photoViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content profile-modal-content">
            <div class="modal-header border-0 pb-0 bg-white">
                <h5 class="modal-title text-muted"><i class="bi bi-camera-fill me-2"></i>Attendance Snapshot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pt-3 pb-4 px-4 bg-light mt-2 rounded-bottom">
                <img id="attendanceImagePreview" src="" alt="Captured Attendance Photo" class="img-fluid rounded shadow-sm" style="max-height: 500px; width: 100%; object-fit: contain; background: #000;">
                <div class="mt-3">
                    <span class="badge bg-secondary text-white px-3 py-2 fs-6">
                        <i class="bi bi-clock-history me-1"></i> <span id="photoDateTimePreview"></span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="js/attendance.js?v=<?php echo time(); ?>"></script>
<script>
    // Keep the PHP dynamic alerts inside index.php
    <?php if ($message): ?>
        Swal.fire({
            icon: '<?php echo $messageType; ?>',
            title: '<?php echo $messageType === 'success' ? 'Recorded!' : 'Error'; ?>',
            text: '<?php echo addslashes($message); ?>',
            confirmButtonColor: '#1ab394'
        });
    <?php endif; ?>

    // Function to handle viewing the specific photo path and timestamp in the modal
    function viewPhoto(imagePath, dateTime) {
        // Ensure the table records modal is properly closed if open 
        var recordsModal = bootstrap.Modal.getInstance(document.getElementById('tableRecordsModal'));
        if(recordsModal) { recordsModal.hide(); }

        document.getElementById('attendanceImagePreview').src = imagePath;
        document.getElementById('photoDateTimePreview').innerText = dateTime;
        var photoModal = new bootstrap.Modal(document.getElementById('photoViewerModal'));
        photoModal.show();
    }

    // Auto-open modal if user is navigating table pagination via GET params
    <?php if (isset($_GET['page']) || isset($_GET['limit'])): ?>
    document.addEventListener("DOMContentLoaded", function() {
        var myModal = new bootstrap.Modal(document.getElementById('tableRecordsModal'));
        myModal.show();
    });
    <?php endif; ?>
</script>

</body>
</html>