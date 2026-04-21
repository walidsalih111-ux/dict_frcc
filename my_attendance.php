<?php
// Start the session to access login variables
session_start();

// 1. Check if the user is actually logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// 2. Check if the logged-in user has the correct role ('user')
if ($_SESSION['role'] !== 'user') {
    if ($_SESSION['role'] === 'admin') {
        header('Location: dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

// Safely get the user's full name
$fullname = htmlspecialchars($_SESSION['fullname']);

// 3. Database Connection
include 'connect.php';
if (!$pdo) {
    die('ERROR: Could not connect to the database. ' . ($db_error ?? 'Unknown error'));
}

// 4. Fetch the User's Profile Details
$profileSql = "SELECT * FROM employees WHERE full = :fullname LIMIT 1";
$profileStmt = $pdo->prepare($profileSql);
$profileStmt->bindParam(':fullname', $_SESSION['fullname'], PDO::PARAM_STR);
$profileStmt->execute();
$userProfile = $profileStmt->fetch(PDO::FETCH_ASSOC);

// --- FETCH AVAILABLE DATES FOR CALENDAR FILTER ---
// Only fetch dates where this specific employee has recorded attendance
$datesSql = "SELECT DISTINCT DATE(a.time_recorded) 
             FROM attendance_record a 
             JOIN employees e ON a.emp_id = e.emp_id 
             WHERE e.full = :fullname";
$datesStmt = $pdo->prepare($datesSql);
$datesStmt->bindParam(':fullname', $_SESSION['fullname'], PDO::PARAM_STR);
$datesStmt->execute();
$availableDates = $datesStmt->fetchAll(PDO::FETCH_COLUMN);
$availableDatesJson = json_encode($availableDates);
// -------------------------------------------------

// 5. Pagination Setup
// Define allowed limits and get current page/limit from URL
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], [10, 25, 50]) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get date filter
$dateFilter = isset($_GET['date']) && !empty($_GET['date']) ? $_GET['date'] : null;

// Fetch total records for calculating pagination pages
$countSql = "SELECT COUNT(*) FROM attendance_record a JOIN employees e ON a.emp_id = e.emp_id WHERE e.full = :fullname";
if ($dateFilter) {
    $countSql .= " AND DATE(a.time_recorded) = :date";
}
$countStmt = $pdo->prepare($countSql);
$countStmt->bindParam(':fullname', $_SESSION['fullname'], PDO::PARAM_STR);
if ($dateFilter) {
    $countStmt->bindParam(':date', $dateFilter, PDO::PARAM_STR);
}
$countStmt->execute();
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Fetch compliance summary
$complianceSql = "SELECT 
    SUM(CASE WHEN TIME(a.time_recorded) <= '08:00:00' AND a.with_id = 'Yes' AND a.is_asean = 'Yes' THEN 1 ELSE 0 END) as compliant,
    SUM(CASE WHEN NOT (TIME(a.time_recorded) <= '08:00:00' AND a.with_id = 'Yes' AND a.is_asean = 'Yes') THEN 1 ELSE 0 END) as non_compliant
FROM attendance_record a
JOIN employees e ON a.emp_id = e.emp_id
WHERE e.full = :fullname";
$complianceStmt = $pdo->prepare($complianceSql);
$complianceStmt->bindParam(':fullname', $_SESSION['fullname'], PDO::PARAM_STR);
$complianceStmt->execute();
$complianceData = $complianceStmt->fetch(PDO::FETCH_ASSOC);
$compliantCount = $complianceData['compliant'] ?? 0;
$nonCompliantCount = $complianceData['non_compliant'] ?? 0;

// 6. Fetch the attendance records for the logged-in user with pagination
$sql = "SELECT a.designation, a.with_id, a.is_asean, a.is_compliant, a.time_recorded, a.photo_path, 
               e.area_of_assignment, e.department, e.unit 
        FROM attendance_record a
        JOIN employees e ON a.emp_id = e.emp_id
        WHERE e.full = :fullname";
if ($dateFilter) {
    $sql .= " AND DATE(a.time_recorded) = :date";
}
$sql .= " ORDER BY a.time_recorded DESC
        LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':fullname', $_SESSION['fullname'], PDO::PARAM_STR);
if ($dateFilter) {
    $stmt->bindParam(':date', $dateFilter, PDO::PARAM_STR);
}
$stmt->execute();
$attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance | DICT Monday Flag Raising</title>

    <!-- Bootstrap 5 & Inspinia CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet" />
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="css/animate.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Flatpickr CSS for Date Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Theme & Custom CSS -->
    <style>
        /* Base typography and body */
        body {
            font-family: "Open Sans", "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 13px;
            margin: 0;
            padding-bottom: 40px;
        }

        /* Animated Gradient Background matching dashboard */
        body.gray-bg, .wrapper.wrapper-content {
            background: linear-gradient(135deg, #4e73df, #1cc88a) !important;
            background-size: 200% 200% !important;
            animation: gradientBG 10s ease infinite !important;
            min-height: 100vh;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Top Navbar */
        .navbar-top {
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 0;
            min-height: 60px;
            z-index: 100;
        }
        
        .navbar-brand-custom {
            background-color: #4e73df;
            color: #ffffff !important;
            padding: 18px 25px;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            margin-right: 20px;
        }

        .navbar-top .nav-link {
            color: #5a5c69;
            font-weight: 600;
            padding: 20px 15px;
        }

        .navbar-top .nav-link:hover {
            color: #4e73df;
        }

        /* Ibox Card Styling matching dashboard */
        .ibox {
            border-radius: 15px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
            background: rgba(255, 255, 255, 0.95) !important;
            border: none !important;
            margin-top: 20px;
            margin-bottom: 25px;
        }

        .ibox-title {
            background: transparent !important;
            border-bottom: 1px solid rgba(0,0,0,0.05) !important;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px 25px !important;
        }

        .ibox-content {
            background: transparent !important;
            border-radius: 0 0 15px 15px !important;
            border: none !important;
            padding: 20px 25px !important;
        }

        /* Theme Colors */
        .text-success { color: #1cc88a !important; }
        .text-primary { color: #4e73df !important; }
        .text-info { color: #36b9cc !important; }
        .text-danger { color: #e74a3b !important; }
        .text-warning { color: #f6c23e !important; }
        
        .bg-success { background-color: #1cc88a !important; }
        .bg-primary { background-color: #4e73df !important; }
        .bg-info { background-color: #36b9cc !important; }
        .bg-danger { background-color: #e74a3b !important; }
        .bg-warning { background-color: #f6c23e !important; }

        .border-info { border-color: #36b9cc !important; }
        .border-success { border-color: #1cc88a !important; }
        .border-danger { border-color: #e74a3b !important; }

        /* Stats Cards */
        .stats-card { 
            border-radius: 10px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
            background: #ffffff !important;
        }
        .border-start-3 { border-left-width: 4px !important; }

        /* Filter Form UI */
        .filter-group {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }
        .filter-group .input-group-text {
            background: #f8f9fc;
            border: 1px solid #e3e6f0;
            color: #4e73df;
            border-right: none;
        }
        .filter-group .form-control, .filter-group .form-select {
            border: 1px solid #e3e6f0;
            border-left: none;
            color: #5a5c69;
        }
        .filter-group .form-control:focus, .filter-group .form-select:focus {
            border-color: #e3e6f0;
            box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
            outline: 0;
        }
        .filter-group .btn-clear {
            background-color: #fff;
            border: 1px solid #e3e6f0;
            color: #e74a3b;
            border-left: none;
        }
        .filter-group .btn-clear:hover {
            background-color: #f8f9fc;
            color: #c93a2e;
        }
        /* Make Flatpickr input look like white background instead of readonly gray */
        .flatpickr-input[readonly] {
            background-color: #ffffff !important;
        }
        
        /* HIGHLIGHT CALENDAR FILTER BLACK COLOR */
        #attendanceDatePicker,
        #attendanceDatePicker.flatpickr-input[readonly] {
            color: #000000 !important;
            font-weight: 800 !important;
        }

        /* Highlight the enabled (recorded) dates inside the calendar dropdown */
        .flatpickr-day:not(.flatpickr-disabled) {
            color: #000000 !important;
            font-weight: bold !important;
        }
        .flatpickr-day.selected {
            color: #ffffff !important; /* Keep text white when selected for contrast */
            background: #4e73df !important;
            border-color: #4e73df !important;
        }

        /* Table & Layout */
        .table-responsive { 
            max-height: 60vh; 
            overflow-y: auto; 
            background: #fff; 
            border-radius: 10px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            border: 1px solid rgba(0,0,0,0.05);
        }
        thead th { 
            position: sticky; 
            top: 0; 
            background-color: #f8f9fc !important; 
            z-index: 10; 
            box-shadow: 0 1px 2px rgba(0,0,0,0.1); 
            border-bottom: 2px solid #e3e6f0 !important;
            color: #4e73df;
            font-weight: 700;
        }
        .table { margin-bottom: 0; }
        .align-middle { vertical-align: middle !important; }

        /* Pagination matching theme */
        .pagination > li > a, .pagination > li > span {
            color: #4e73df;
            background-color: #ffffff;
            border: 1px solid #e3e6f0;
            margin-left: -1px;
            padding: 5px 12px;
            font-size: 13px;
        }
        .pagination > li.active > a, .pagination > li.active > span,
        .pagination > li.active > a:hover, .pagination > li.active > span:hover {
            background-color: #4e73df;
            border-color: #4e73df;
            color: #fff;
            z-index: 3;
        }
        .pagination > li > a:hover, .pagination > li > span:hover {
            background-color: #eaecf4;
            border-color: #dddddd;
            color: #4e73df;
        }
        .pagination > li.disabled > a, .pagination > li.disabled > span {
            color: #858796;
            background-color: #fff;
            border-color: #e3e6f0;
        }

        /* Callout Styles */
        .page-callout {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(78, 115, 223, 0.25);
            border-left: 5px solid #4e73df;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            padding: 14px 16px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .page-callout .callout-title {
            margin: 0;
            font-weight: 700;
            color: #2f4050;
            font-size: 15px;
        }

        .page-callout .callout-subtitle {
            margin: 2px 0 0;
            color: #5e6a75;
            font-size: 12px;
        }

        .page-callout .callout-icon {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(78, 115, 223, 0.12);
            color: #4e73df;
            flex-shrink: 0;
        }

        @media (max-width: 767px) {
            .page-callout {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        /* Modals */
        .modal-content.profile-modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .modal-content.profile-modal-content .modal-header {
            border-radius: 15px 15px 0 0;
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            object-fit: cover;
            background-color: #f8f9fc;
            color: #4e73df;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            border: 3px solid #e3e6f0;
        }
    </style>
</head>
<body class="gray-bg">

    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-top p-0 sticky-top">
        <a class="navbar-brand-custom text-decoration-none d-inline-flex align-items-center" href="#">
            <img src="img/logo/DICT.png" alt="DICT Logo" style="height: 24px;" class="me-2">
            DICT Monday Flag Raising 
        </a>
        <button class="navbar-toggler me-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="fa fa-bars text-primary"></span>
        </button>
        <div class="collapse navbar-collapse px-3" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <span class="text-muted me-3 fw-bold">My Attendance</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="modal" data-bs-target="#profileModal" style="cursor: pointer;">
                        <i class="fa fa-user-circle-o me-1 fs-5 align-middle"></i> <strong><?php echo $fullname; ?></strong>
                    </a>
                </li>
                <li class="nav-item ms-2 border-start ps-3">
                    <a class="nav-link text-danger" href="#" id="logoutBtn">
                        <i class="fa fa-sign-out"></i> Log out
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="container wrapper wrapper-content animated fadeInRight mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="page-callout">
                    <div>
                        <p class="callout-title">My Attendance Timeline</p>
                        <p class="callout-subtitle">Track your compliance history, filter by date, and review your submitted attendance records.</p>
                    </div>
                    <span class="callout-icon"><i class="fa fa-calendar-check-o"></i></span>
                </div>
                
                <!-- IBox Panel -->
                <div class="ibox">
                    <div class="ibox-title">
                        <h4 class="mb-0 text-primary fw-bold"><i class="fa fa-history me-2"></i> Attendance Records</h4>
                    </div>
                    
                    <div class="ibox-content">

                        <!-- Compliance Summary Stats Cards -->
                        <div class="row mb-4" id="attendance_stats_container">
                            <div class="col-md-4 mb-2 mb-md-0">
                                <div class="p-3 bg-white border-start border-info border-start-3 stats-card text-center">
                                    <h6 class="text-info mb-1 fw-bold"><i class="fa fa-list-ul"></i> Total Records</h6>
                                    <h3 class="mb-0 text-info fw-bold"><?php echo $totalRecords; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2 mb-md-0">
                                <div class="p-3 bg-white border-start border-success border-start-3 stats-card text-center">
                                    <h6 class="text-success mb-1 fw-bold"><i class="fa fa-check-circle"></i> Compliant</h6>
                                    <h3 class="mb-0 text-success fw-bold"><?php echo $compliantCount; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-4 mb-2 mb-md-0">
                                <div class="p-3 bg-white border-start border-danger border-start-3 stats-card text-center">
                                    <h6 class="text-danger mb-1 fw-bold"><i class="fa fa-times-circle"></i> Non-Compliant</h6>
                                    <h3 class="mb-0 text-danger fw-bold"><?php echo $nonCompliantCount; ?></h3>
                                </div>
                            </div>
                        </div>

                        <!-- Show Entries Dropdown and Date Search -->
                        <div class="row mb-4 align-items-center">
                            <div class="col-md-6 col-lg-4 mb-3 mb-md-0">
                                <form method="GET" action="my_attendance.php" id="entriesForm">
                                    <div class="input-group filter-group">
                                        <span class="input-group-text fw-bold">
                                            <i class="fa fa-list me-2"></i> Show
                                        </span>
                                        <select name="limit" class="form-select fw-semibold" onchange="this.form.submit();">
                                            <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10 Entries</option>
                                            <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25 Entries</option>
                                            <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 Entries</option>
                                        </select>
                                    </div>
                                    <!-- Preserve page and date context -->
                                    <input type="hidden" name="page" value="1">
                                    <?php if ($dateFilter): ?>
                                        <input type="hidden" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>">
                                    <?php endif; ?>
                                </form>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <form method="GET" action="my_attendance.php" id="dateSearchForm">
                                    <div class="input-group filter-group">
                                        <span class="input-group-text fw-bold">
                                            <i class="fa fa-calendar me-2"></i> Date
                                        </span>
                                        <!-- Real-time Flatpickr date input -->
                                        <input type="text" name="date" id="attendanceDatePicker" class="form-control fw-semibold" placeholder="Select a date..." value="<?php echo htmlspecialchars($dateFilter ?? ''); ?>">
                                        <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                                        <?php if ($dateFilter): ?>
                                            <a href="my_attendance.php?limit=<?php echo $limit; ?>" class="btn btn-clear px-3 d-flex align-items-center" title="Clear Filter">
                                                <i class="fa fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Main Table -->
                        <div class="table-responsive border-0">
                            <table class="table table-bordered mb-0 mt-0">
                                <thead>
                                    <tr>
                                        <th>Date & Time Recorded</th>
                                        <th>Designation</th>
                                        <th>Division</th>
                                        <th>Unit</th>
                                        <th>Area of Assignment</th>
                                        <th class="text-center">With ID</th>
                                        <th class="text-center">Proper Attire</th>
                                        <th class="text-center">Compliant</th>
                                        <th class="text-center">Photo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($attendance_records) > 0): ?>
                                        <?php foreach ($attendance_records as $record): ?>
                                            <?php 
                                                // Pre-calculate status variables
                                                $timeOnly = date('H:i:s', strtotime($record['time_recorded']));
                                                $isLate = ($timeOnly > '08:00:00');
                                                $hasId = ($record['with_id'] === 'Yes');
                                                $hasProperAttire = ($record['is_asean'] === 'Yes');

                                                // Format Date & Time for passing to photo modal
                                                $formattedDateTime = date('M d, Y - h:i A', strtotime($record['time_recorded']));

                                                // Determine compliance: Must NOT be late, MUST have ID, MUST have Proper Attire
                                                $isCompliant = (!$isLate && $hasId && $hasProperAttire);
                                                
                                                // Badges and Icons
                                                $withIdCheck = ($hasId) ? '<i class="fa fa-check text-success fa-lg"></i>' : '<i class="fa fa-times text-danger fa-lg"></i>';
                                                $aseanCheck = ($hasProperAttire) ? '<i class="fa fa-check text-success fa-lg"></i>' : '<i class="fa fa-times text-danger fa-lg"></i>';
                                                $compliantClass = ($isCompliant) ? 'bg-success' : 'bg-danger';
                                                $compliantText = ($isCompliant) ? 'Yes' : 'No';
                                            ?>
                                            <tr>
                                                <td class="align-middle">
                                                    <strong class="text-dark"><?php echo date('M d, Y', strtotime($record['time_recorded'])); ?></strong><br>
                                                    <div class="mt-1 d-flex align-items-center text-muted">
                                                        <small><i class="fa fa-clock-o me-1"></i><?php echo date('h:i A', strtotime($record['time_recorded'])); ?></small>
                                                    </div>
                                                </td>
                                                <td class="align-middle text-muted"><?php echo htmlspecialchars($record['designation'] ?? 'N/A'); ?></td>
                                                <td class="align-middle text-muted"><?php echo htmlspecialchars($record['department'] ?? 'N/A'); ?></td>
                                                <td class="align-middle text-muted"><?php echo htmlspecialchars($record['unit'] ?? 'N/A'); ?></td>
                                                <td class="align-middle text-muted"><?php echo htmlspecialchars($record['area_of_assignment'] ?? 'N/A'); ?></td>
                                                
                                                <td class="text-center align-middle"><?php echo $withIdCheck; ?></td>
                                                <td class="text-center align-middle"><?php echo $aseanCheck; ?></td>
                                                <td class="text-center align-middle">
                                                    <span class="badge <?php echo $compliantClass; ?> px-3 py-2 shadow-sm rounded-pill"><?php echo $compliantText; ?></span>
                                                </td>

                                                <td class="text-center align-middle">
                                                    <?php if (!empty($record['photo_path'])): ?>
                                                        <?php $safePath = htmlspecialchars($record['photo_path'], ENT_QUOTES, 'UTF-8'); ?>
                                                        <img src="<?php echo $safePath; ?>" 
                                                             alt="Photo" 
                                                             class="shadow-sm"
                                                             style="width: 45px; height: 45px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid #e3e6f0; transition: transform 0.2s;" 
                                                             onmouseover="this.style.transform='scale(1.1)'" 
                                                             onmouseout="this.style.transform='scale(1)'"
                                                             onclick="viewPhoto('<?php echo $safePath; ?>', '<?php echo $formattedDateTime; ?>')" 
                                                             title="Click to view full image">
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-muted border px-2 py-1 fw-normal"><i class="fa fa-eye-slash"></i> No Photo</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-5">
                                                <i class="fa fa-folder-open-o fa-3x mb-3 d-block text-primary opacity-50"></i>
                                                <em style="font-size: 1.2rem;">You have no attendance records at this time.</em>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer Pagination -->
                        <div class="row mt-4 align-items-center">
                            <div class="col-sm-12 col-md-5">
                                <?php
                                    $startEntry = ($totalRecords > 0) ? $offset + 1 : 0;
                                    $endEntry = min($offset + $limit, $totalRecords);
                                ?>
                                <div class="text-muted fw-bold" style="font-size: 13px;">
                                    Showing <?php echo $startEntry; ?> to <?php echo $endEntry; ?> of <?php echo $totalRecords; ?> entries
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                                <?php if ($totalPages > 1): ?>
                                    <ul class="pagination pagination-sm mb-0 shadow-sm rounded">
                                        <!-- Previous Button -->
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?limit=<?php echo $limit; ?>&page=<?php echo $page - 1; ?><?php echo $dateFilter ? '&date=' . urlencode($dateFilter) : ''; ?>">Previous</a>
                                        </li>
                                        
                                        <!-- Page Numbers -->
                                        <?php
                                            // Show a window of max 5 pages around the current page
                                            $startPage = max(1, $page - 2);
                                            $endPage = min($totalPages, $page + 2);
                                            
                                            if ($startPage > 1) {
                                                echo '<li class="page-item"><a class="page-link" href="?limit=' . $limit . '&page=1' . ($dateFilter ? '&date=' . urlencode($dateFilter) : '') . '">1</a></li>';
                                                if ($startPage > 2) {
                                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                }
                                            }

                                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?limit=<?php echo $limit; ?>&page=<?php echo $i; ?><?php echo $dateFilter ? '&date=' . urlencode($dateFilter) : ''; ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; 
                                            
                                            if ($endPage < $totalPages) {
                                                if ($endPage < $totalPages - 1) {
                                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                }
                                                echo '<li class="page-item"><a class="page-link" href="?limit=' . $limit . '&page=' . $totalPages . ($dateFilter ? '&date=' . urlencode($dateFilter) : '') . '">' . $totalPages . '</a></li>';
                                            }
                                        ?>

                                        <!-- Next Button -->
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?limit=<?php echo $limit; ?>&page=<?php echo $page + 1; ?><?php echo $dateFilter ? '&date=' . urlencode($dateFilter) : ''; ?>">Next</a>
                                        </li>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- VIEW MODAL -->
    <div class="modal fade" id="profileModal" tabindex="-1" role="dialog" aria-labelledby="profileModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
        <div class="modal-content profile-modal-content">
            <div class="modal-header p-3">
                <h5 class="modal-title text-primary fw-bold" id="profileModalLabel"><i class="fa fa-user-circle-o me-1"></i> Employee Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4 mt-2">
                    <div class="profile-avatar rounded-circle mb-3 mx-auto">
                        <i class="fa fa-user"></i>
                    </div>
                    <h3 class="mt-2 fw-bold text-dark"><?php echo htmlspecialchars($userProfile['full'] ?? $fullname); ?></h3>
                    <p class="text-muted mb-2"><?php echo htmlspecialchars($userProfile['designation'] ?? 'Designation Not Set'); ?>  |  <?php echo htmlspecialchars($userProfile['department'] ?? 'N/A'); ?></p>
                    <?php 
                        $status = htmlspecialchars($userProfile['status'] ?? 'N/A');
                        $badgeClass = 'bg-secondary';
                        $lowerStatus = strtolower($status);
                        
                        // Badge logic mapping
                        if(in_array($lowerStatus, ['plantilla', 'active', 'permanent', 'regular'])) {
                            $badgeClass = 'bg-success';
                        } elseif(in_array($lowerStatus, ['job order', 'contractual', 'temporary'])) {
                            $badgeClass = 'bg-warning text-dark';
                        } elseif(in_array($lowerStatus, ['inactive', 'resigned'])) {
                            $badgeClass = 'bg-danger';
                        }
                    ?>
                    <span class="badge <?php echo $badgeClass; ?> px-3 py-2 rounded-pill shadow-sm"><?php echo ucfirst($status); ?></span>
                </div>
                <hr>
                <table class="table table-borderless table-sm m-0">
                    <tbody>
                        <tr>
                            <th class="text-end text-muted" width="40%">Employee ID:</th>
                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($userProfile['emp_id'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th class="text-end text-muted">Full Name:</th>
                            <td class="text-dark"><?php echo htmlspecialchars($userProfile['full'] ?? $fullname); ?></td>
                        </tr>
                        <tr>
                            <th class="text-end text-muted">Email Address:</th>
                            <td class="text-dark"><?php echo !empty($userProfile['emp_email']) ? htmlspecialchars($userProfile['emp_email']) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <th class="text-end text-muted">Age:</th>
                            <td class="text-dark"><?php echo htmlspecialchars($userProfile['age'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th class="text-end text-muted">Gender:</th>
                            <td class="text-dark"><?php echo htmlspecialchars($userProfile['gender'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th class="text-end text-muted">Unit:</th>
                            <td class="text-dark"><?php echo htmlspecialchars($userProfile['unit'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th class="text-end text-muted">Area of Assignment:</th>
                            <td class="text-dark"><?php echo htmlspecialchars($userProfile['area_of_assignment'] ?? 'N/A'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer bg-light" style="border-radius: 0 0 15px 15px;">
                <button type="button" class="btn btn-outline-primary me-auto fw-bold" id="openAccountSettingsBtn"><i class="fa fa-cog me-1"></i> Edit Account</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
        </div>
    </div>

    <!-- Account Settings Modal -->
    <div class="modal fade" id="accountSettingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content profile-modal-content">
                <div class="modal-header p-3">
                    <h5 class="modal-title text-primary fw-bold"><i class="fa fa-cog me-1"></i> Account Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="accountSettingsForm" method="POST" action="process_account_settings.php">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Username</label>
                        <input type="text" name="username" id="acc_username" class="form-control" required value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Email</label>
                        <input type="email" name="emp_email" id="acc_email" class="form-control" value="<?php echo htmlspecialchars($userProfile['emp_email'] ?? ''); ?>">
                    </div>
                    <hr>
                    <p class="small text-info mb-3 fw-bold"><i class="fa fa-lock me-1"></i> Change Password (leave blank to keep current password)</p>
                    <div class="mb-3 position-relative">
                        <label class="form-label small text-muted fw-bold">Current Password</label>
                        <input type="password" name="current_password" id="acc_current_password" class="form-control">
                        <span class="position-absolute top-50 end-0 translate-middle-y me-3 toggle-password" onclick="togglePassword('acc_current_password', this)" style="cursor: pointer; margin-top: 14px; color: #858796;"><i class="fa fa-eye"></i></span>
                    </div>
                    <div class="mb-3 position-relative">
                        <label class="form-label small text-muted fw-bold">New Password</label>
                        <input type="password" name="new_password" id="acc_new_password" class="form-control" minlength="8" placeholder="At least 8 characters">
                        <span class="position-absolute top-50 end-0 translate-middle-y me-3 toggle-password" onclick="togglePassword('acc_new_password', this)" style="cursor: pointer; margin-top: 14px; color: #858796;"><i class="fa fa-eye"></i></span>
                    </div>
                    <div class="mb-3 position-relative">
                        <label class="form-label small text-muted fw-bold">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="acc_confirm_password" class="form-control">
                        <span class="position-absolute top-50 end-0 translate-middle-y me-3 toggle-password" onclick="togglePassword('acc_confirm_password', this)" style="cursor: pointer; margin-top: 14px; color: #858796;"><i class="fa fa-eye"></i></span>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-radius: 0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold" id="saveAccountBtn"><i class="fa fa-save me-1"></i> Save Changes</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Photo Viewer Modal -->
    <div class="modal fade" id="photoViewerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                <div class="modal-header p-3 bg-light" style="border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title text-primary fw-bold mb-0"><i class="fa fa-camera"></i> Attendance Snapshot</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center bg-dark p-2" style="border-radius: 0 0 15px 15px;">
                    <img id="attendanceImagePreview" src="" alt="Captured Attendance Photo" class="img-fluid rounded" style="max-height: 500px; width: 100%; object-fit: contain;">
                    <div class="mt-3 mb-2 text-white">
                        <span class="badge bg-primary p-2 shadow-sm rounded-pill" style="font-size: 0.95rem;">
                            <i class="fa fa-clock-o"></i> Captured on: <span id="photoDateTimePreview"></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <script>
        // Initialize Real-time Flatpickr restricted to Employee's Recorded Dates
        document.addEventListener('DOMContentLoaded', function() {
            const availableDates = <?php echo $availableDatesJson; ?>;
            
            flatpickr("#attendanceDatePicker", {
                enable: availableDates,
                dateFormat: "Y-m-d",
                onChange: function(selectedDates, dateStr, instance) {
                    // Automatically submit form when a date is clicked
                    document.getElementById('dateSearchForm').submit();
                }
            });
        });

        // Function to handle viewing the specific photo path and timestamp in the modal
        function viewPhoto(imagePath, dateTime) {
            document.getElementById('attendanceImagePreview').src = imagePath;
            document.getElementById('photoDateTimePreview').innerText = dateTime;
            var photoModal = new bootstrap.Modal(document.getElementById('photoViewerModal'));
            photoModal.show();
        }

        // Toggle password visibility helper
        function togglePassword(inputId, iconElement) {
            var pass = document.getElementById(inputId);
            var icon = iconElement.querySelector("i");
            if (pass.type === "password") {
                pass.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                pass.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        // Open Account Settings modal from Profile modal
        var openAccBtn = document.getElementById('openAccountSettingsBtn');
        if (openAccBtn) {
            openAccBtn.addEventListener('click', function() {
                var profileModalEl = document.getElementById('profileModal');
                var profileModal = bootstrap.Modal.getInstance(profileModalEl) || new bootstrap.Modal(profileModalEl);
                profileModal.hide();
                var accModal = new bootstrap.Modal(document.getElementById('accountSettingsModal'));
                accModal.show();
            });
        }

        // Validate account settings form before submit
        var accountForm = document.getElementById('accountSettingsForm');
        if (accountForm) {
            accountForm.addEventListener('submit', function(e) {
                var newPass = document.getElementById('acc_new_password').value.trim();
                var confPass = document.getElementById('acc_confirm_password').value.trim();

                if (newPass !== '' || confPass !== '') {
                    if (newPass.length < 8) {
                        e.preventDefault();
                        Swal.fire({icon:'warning', title:'Weak password', text: 'New password must be at least 8 characters.', confirmButtonColor: '#4e73df'});
                        return false;
                    }
                    if (newPass !== confPass) {
                        e.preventDefault();
                        Swal.fire({icon:'warning', title:'Password mismatch', text: 'New password and confirmation do not match.', confirmButtonColor: '#4e73df'});
                        return false;
                    }
                }
                // allow submit
            });
        }

        // SweetAlert2 Logout Confirmation
        document.getElementById('logoutBtn').addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default link behavior
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You will be logged out of your session.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4e73df', // Match Theme Primary Color
                cancelButtonColor: '#e74a3b',  // Match Theme Danger Color
                confirmButtonText: 'Yes, log out',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect to the provided logout.php page if confirmed
                    window.location.href = 'logout.php';
                }
            });
        });

        // Show server-side result messages (account update)
        <?php if (isset($_GET['account_update']) && $_GET['account_update'] === 'success'): ?>
            Swal.fire({icon:'success', title: 'Account updated', text: 'Your account settings have been updated.', confirmButtonColor: '#4e73df'});
        <?php elseif (isset($_GET['account_update']) && $_GET['account_update'] === 'error'): ?>
            Swal.fire({icon:'error', title: 'Update failed', text: <?php echo json_encode($_GET['reason'] ?? 'An error occurred'); ?>, confirmButtonColor: '#4e73df'});
        <?php endif; ?>
    </script>
</body>
</html>