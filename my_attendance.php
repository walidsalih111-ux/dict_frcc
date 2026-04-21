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
    <link rel="icon" type="image/png" href="img/logo/DICT.png">

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

        /* CRITICAL FIX: Page Wrapper Margin to prevent overlapping with fixed sidebar */
        #page-wrapper {
            margin-left: 220px; /* Standard Inspinia sidebar width */
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        body.mini-navbar #page-wrapper {
            margin-left: 70px; /* Minimized sidebar width */
        }
        @media (max-width: 768px) {
            #page-wrapper { margin-left: 0 !important; }
            body.mini-navbar #page-wrapper { margin-left: 0 !important; }
        }

        /* Solid Blue Background matching reference image */
        body.gray-bg, #page-wrapper, .wrapper.wrapper-content {
            background-color: #4e73df !important;
            min-height: 100vh;
        }

        /* Ibox Card Styling matching dashboard */
        .ibox {
            border-radius: 15px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
            background: rgba(255, 255, 255, 0.98) !important;
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
            padding: 25px 30px !important;
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

        /* Modern Stats Cards */
        .stats-card { 
            border-radius: 12px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.04); 
            background: #ffffff !important;
        }
        .border-start-4 { border-left-width: 4px !important; }
        
        /* Subtle colored backgrounds for icons */
        .bg-info-subtle { background-color: rgba(54, 185, 204, 0.1); }
        .bg-success-subtle { background-color: rgba(28, 200, 138, 0.1); }
        .bg-danger-subtle { background-color: rgba(231, 74, 59, 0.1); }

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
            color: #4e73df !important;
            font-weight: 700 !important;
        }

        /* Highlight the enabled (recorded) dates inside the calendar dropdown */
        .flatpickr-day:not(.flatpickr-disabled) {
            color: #000000 !important;
            font-weight: bold !important;
        }
        .flatpickr-day.selected {
            color: #ffffff !important;
            background: #4e73df !important;
            border-color: #4e73df !important;
        }

        /* Table & Layout */
        .table-responsive { 
            max-height: 60vh; 
            overflow-y: auto; 
            background: #fff; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); 
            border: 1px solid #e3e6f0;
        }
        
        /* Modernized Table Headers */
        thead th { 
            position: sticky; 
            top: 0; 
            background-color: #f8f9fc !important; 
            z-index: 10; 
            border-bottom: 2px solid #e3e6f0 !important;
            color: #5a5c69;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 1rem !important;
        }
        
        .table > :not(caption) > * > * {
            padding: 1rem 0.75rem;
            border-bottom-color: #e3e6f0;
        }
        .table { margin-bottom: 0; }
        .align-middle { vertical-align: middle !important; }
        
        /* Hover state for rows */
        tbody tr:hover {
            background-color: #f8f9fc;
        }

        /* Pagination matching theme */
        .pagination > li > a, .pagination > li > span {
            color: #4e73df;
            background-color: #ffffff;
            border: 1px solid #e3e6f0;
            margin-left: -1px;
            padding: 6px 14px;
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
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(78, 115, 223, 0.15);
            border-left: 5px solid #4e73df;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 18px 22px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .page-callout .callout-title {
            margin: 0;
            font-weight: 800;
            color: #2f4050;
            font-size: 16px;
        }

        .page-callout .callout-subtitle {
            margin: 4px 0 0;
            color: #5a5c69;
            font-size: 13px;
        }

        .page-callout .callout-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(78, 115, 223, 0.1);
            color: #4e73df;
            flex-shrink: 0;
            font-size: 18px;
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

    <!-- Main Wrapper containing the Sidebar and Page Wrapper -->
    <div id="wrapper">
        
        <!-- Inject User Sidebar -->
        <?php include 'sidebar_user.php'; ?>
        
        <!-- Inject User Topbar (Which opens #page-wrapper) -->
        <?php include 'topbar_user.php'; ?>

        <!-- Main Content Area -->
        <div class="wrapper wrapper-content animated fadeInRight mt-3">
            <div class="row justify-content-center">
                <div class="col-lg-12 px-xl-4">
                    <div class="page-callout">
                        <div>
                            <p class="callout-title">My Attendance Timeline</p>
                            <p class="callout-subtitle">Track your compliance history, filter by date, and review your submitted attendance records.</p>
                        </div>
                        <span class="callout-icon"><i class="fa fa-calendar-check-o"></i></span>
                    </div>
                    
                    <!-- IBox Panel -->
                    <div class="ibox">
                        <div class="ibox-title d-flex justify-content-between align-items-center">
                            <h4 class="mb-0 text-primary fw-bold"><i class="fa fa-history me-2"></i> Record History</h4>
                        </div>
                        
                        <div class="ibox-content">

                            <!-- Compliance Summary Stats Cards (Modern Side-by-Side flex layout) -->
                            <div class="row mb-4" id="attendance_stats_container">
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <div class="p-4 bg-white border-start border-info border-start-4 stats-card d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="text-info mb-1 fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">Total Records</h6>
                                            <h3 class="mb-0 text-dark fw-bold"><?php echo $totalRecords; ?></h3>
                                        </div>
                                        <div class="bg-info-subtle text-info rounded p-2 px-3">
                                            <i class="fa fa-list-ul fa-lg"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <div class="p-4 bg-white border-start border-success border-start-4 stats-card d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="text-success mb-1 fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">Compliant</h6>
                                            <h3 class="mb-0 text-dark fw-bold"><?php echo $compliantCount; ?></h3>
                                        </div>
                                        <div class="bg-success-subtle text-success rounded p-2 px-3">
                                            <i class="fa fa-check-circle fa-lg"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3 mb-md-0">
                                    <div class="p-4 bg-white border-start border-danger border-start-4 stats-card d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="text-danger mb-1 fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">Non-Compliant</h6>
                                            <h3 class="mb-0 text-dark fw-bold"><?php echo $nonCompliantCount; ?></h3>
                                        </div>
                                        <div class="bg-danger-subtle text-danger rounded p-2 px-3">
                                            <i class="fa fa-times-circle fa-lg"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Show Entries Dropdown and Date Search -->
                            <div class="row mb-4 align-items-center justify-content-between">
                                <div class="col-md-5 col-lg-4 mb-3 mb-md-0">
                                    <form method="GET" action="my_attendance.php" id="entriesForm">
                                        <div class="input-group filter-group">
                                            <span class="input-group-text fw-bold">
                                                <i class="fa fa-list me-2"></i> Show
                                            </span>
                                            <select name="limit" id="limitSelect" class="form-select fw-semibold">
                                                <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10 Entries</option>
                                                <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25 Entries</option>
                                                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 Entries</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-md-7 col-lg-5">
                                    <form method="GET" action="my_attendance.php" id="dateSearchForm">
                                        <div class="input-group filter-group">
                                            <span class="input-group-text fw-bold">
                                                <i class="fa fa-calendar me-2"></i> Filter Date
                                            </span>
                                            <!-- Real-time Flatpickr date input -->
                                            <input type="text" name="date" id="attendanceDatePicker" class="form-control fw-semibold" placeholder="Select a recorded date..." value="<?php echo htmlspecialchars($dateFilter ?? ''); ?>">
                                            <button type="button" id="clearDateBtn" class="btn btn-clear px-3 d-flex align-items-center <?php echo $dateFilter ? '' : 'd-none'; ?>" title="Clear Filter">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Main Table -->
                            <div class="table-responsive border-0" id="table_container" style="transition: opacity 0.3s ease;">
                                <table class="table mb-0 mt-0">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
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
                                                    <td class="align-middle text-nowrap">
                                                        <strong class="text-dark"><i class="fa fa-calendar-o text-muted me-1"></i> <?php echo date('M d, Y', strtotime($record['time_recorded'])); ?></strong><br>
                                                        <div class="mt-1 text-muted" style="font-size: 12px;">
                                                            <i class="fa fa-clock-o me-1"></i><?php echo date('h:i A', strtotime($record['time_recorded'])); ?>
                                                        </div>
                                                    </td>
                                                    <td class="align-middle text-dark fw-semibold"><?php echo htmlspecialchars($record['designation'] ?? 'N/A'); ?></td>
                                                    <td class="align-middle text-muted"><?php echo htmlspecialchars($record['department'] ?? 'N/A'); ?></td>
                                                    <td class="align-middle text-muted"><?php echo htmlspecialchars($record['unit'] ?? 'N/A'); ?></td>
                                                    <td class="align-middle text-muted"><?php echo htmlspecialchars($record['area_of_assignment'] ?? 'N/A'); ?></td>
                                                    
                                                    <td class="text-center align-middle"><?php echo $withIdCheck; ?></td>
                                                    <td class="text-center align-middle"><?php echo $aseanCheck; ?></td>
                                                    <td class="text-center align-middle">
                                                        <span class="badge <?php echo $compliantClass; ?> px-3 py-2 shadow-sm rounded-pill fw-bold" style="font-size: 12px;"><?php echo $compliantText; ?></span>
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
                                                <td colspan="9" class="text-center py-5">
                                                    <div class="d-flex flex-column align-items-center justify-content-center py-4">
                                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                                            <i class="fa fa-folder-open-o fa-3x text-primary opacity-50"></i>
                                                        </div>
                                                        <h5 class="fw-bold text-dark mb-1">No Records Found</h5>
                                                        <p class="text-muted mb-0">You have no attendance records matching your criteria.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Footer Pagination -->
                            <div class="row mt-4 align-items-center" id="pagination_container">
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
                                                <a class="page-link fw-semibold" href="?limit=<?php echo $limit; ?>&page=<?php echo $page - 1; ?><?php echo $dateFilter ? '&date=' . urlencode($dateFilter) : ''; ?>">Previous</a>
                                            </li>
                                            
                                            <!-- Page Numbers -->
                                            <?php
                                                // Show a window of max 5 pages around the current page
                                                $startPage = max(1, $page - 2);
                                                $endPage = min($totalPages, $page + 2);
                                                
                                                if ($startPage > 1) {
                                                    echo '<li class="page-item"><a class="page-link fw-semibold" href="?limit=' . $limit . '&page=1' . ($dateFilter ? '&date=' . urlencode($dateFilter) : '') . '">1</a></li>';
                                                    if ($startPage > 2) {
                                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                    }
                                                }

                                                for ($i = $startPage; $i <= $endPage; $i++): ?>
                                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                                        <a class="page-link fw-semibold" href="?limit=<?php echo $limit; ?>&page=<?php echo $i; ?><?php echo $dateFilter ? '&date=' . urlencode($dateFilter) : ''; ?>"><?php echo $i; ?></a>
                                                    </li>
                                                <?php endfor; 
                                                
                                                if ($endPage < $totalPages) {
                                                    if ($endPage < $totalPages - 1) {
                                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                    }
                                                    echo '<li class="page-item"><a class="page-link fw-semibold" href="?limit=' . $limit . '&page=' . $totalPages . ($dateFilter ? '&date=' . urlencode($dateFilter) : '') . '">' . $totalPages . '</a></li>';
                                                }
                                            ?>

                                            <!-- Next Button -->
                                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                                <a class="page-link fw-semibold" href="?limit=<?php echo $limit; ?>&page=<?php echo $page + 1; ?><?php echo $dateFilter ? '&date=' . urlencode($dateFilter) : ''; ?>">Next</a>
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

    </div> <!-- Close page-wrapper opened inside topbar_user.php -->
    </div> <!-- Close main wrapper -->

    <!-- INCLUDE EXTERNAL MODALS -->
    <?php include 'my_attendance_modals.php'; ?>

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
                    fetchAttendanceData(1);
                }
            });
        });

        // AJAX Function to fetch data and update UI seamlessly
        function fetchAttendanceData(page = 1) {
            const limit = document.getElementById('limitSelect').value;
            const dateInput = document.getElementById('attendanceDatePicker');
            const date = dateInput.value;
            const clearBtn = document.getElementById('clearDateBtn');

            // Show/hide clear button dynamically
            if (date) {
                clearBtn.classList.remove('d-none');
            } else {
                clearBtn.classList.add('d-none');
            }

            // Build URL parameters
            const url = new URL(window.location.href);
            url.searchParams.set('limit', limit);
            url.searchParams.set('page', page);
            if (date) {
                url.searchParams.set('date', date);
            } else {
                url.searchParams.delete('date');
            }

            // Update browser history/URL
            window.history.pushState({ path: url.href }, '', url.href);

            // Add visual feedback to table
            const tableContainer = document.getElementById('table_container');
            if(tableContainer) tableContainer.style.opacity = '0.5';

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Update Stats Container
                const newStats = doc.getElementById('attendance_stats_container');
                if (newStats) document.getElementById('attendance_stats_container').innerHTML = newStats.innerHTML;

                // Update Table
                const newTable = doc.getElementById('table_container');
                if (newTable && tableContainer) {
                    tableContainer.innerHTML = newTable.innerHTML;
                    tableContainer.style.opacity = '1';
                }

                // Update Pagination
                const newPagination = doc.getElementById('pagination_container');
                if (newPagination) document.getElementById('pagination_container').innerHTML = newPagination.innerHTML;

                // Re-bind pagination clicks
                bindPagination();
            })
            .catch(error => {
                console.error('Error fetching data:', error);
                if(tableContainer) tableContainer.style.opacity = '1';
            });
        }

        // Limit dropdown change listener
        document.getElementById('limitSelect').addEventListener('change', function() {
            fetchAttendanceData(1);
        });

        // Clear date button listener
        document.getElementById('clearDateBtn').addEventListener('click', function() {
            const fp = document.getElementById("attendanceDatePicker")._flatpickr;
            if (fp) {
                fp.clear(); // Clears Flatpickr value
            } else {
                document.getElementById('attendanceDatePicker').value = '';
            }
            fetchAttendanceData(1);
        });

        // Bind AJAX to pagination links
        function bindPagination() {
            const pageLinks = document.querySelectorAll('#pagination_container .page-link');
            pageLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Prevent default behavior only if button is disabled/active
                    if (this.parentElement.classList.contains('disabled') || this.parentElement.classList.contains('active')) {
                        e.preventDefault();
                    } else {
                        e.preventDefault();
                        const url = new URL(this.href);
                        const page = url.searchParams.get('page');
                        if (page) fetchAttendanceData(page);
                    }
                });
            });
        }

        // Initialize pagination bindings and history support on page load
        bindPagination();
        window.addEventListener('popstate', function(e) {
            window.location.reload(); 
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

        // Setup Username Real-Time Validation
        var isUsernameValid = true;
        const usernameInput = document.getElementById('acc_username');
        const usernameFeedback = document.getElementById('usernameFeedback');
        const saveAccBtn = document.getElementById('saveAccountBtn');
        const empIdInput = document.getElementById('acc_emp_id');
        let usernameTimeout = null;

        if (usernameInput) {
            const originalUsername = usernameInput.value.trim();

            usernameInput.addEventListener('input', function() {
                clearTimeout(usernameTimeout);
                const currentVal = this.value.trim();
                const empId = empIdInput ? empIdInput.value : '';

                // Reset visual state
                usernameFeedback.textContent = '';
                usernameInput.classList.remove('is-invalid', 'is-valid');
                saveAccBtn.disabled = false;
                isUsernameValid = true;

                if (currentVal === '') return;
                
                // If it is identical to their current username, no check needed
                if (currentVal === originalUsername) return;

                // Debounce API call
                usernameTimeout = setTimeout(() => {
                    const formData = new FormData();
                    formData.append('username', currentVal);
                    formData.append('emp_id', empId);

                    fetch('check_username.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.available) {
                            usernameInput.classList.add('is-invalid');
                            usernameFeedback.textContent = 'Username is already taken.';
                            usernameFeedback.className = 'text-danger mt-1 d-block small fw-bold';
                            saveAccBtn.disabled = true;
                            isUsernameValid = false;
                        } else {
                            usernameInput.classList.add('is-valid');
                            usernameFeedback.textContent = 'Username is available.';
                            usernameFeedback.className = 'text-success mt-1 d-block small fw-bold';
                            saveAccBtn.disabled = false;
                            isUsernameValid = true;
                        }
                    })
                    .catch(err => console.error('Error checking username:', err));
                }, 500); 
            });
        }

        // Validate account settings form before submit
        var accountForm = document.getElementById('accountSettingsForm');
        if (accountForm) {
            accountForm.addEventListener('submit', function(e) {
                if (!isUsernameValid) {
                    e.preventDefault();
                    Swal.fire({icon:'error', title:'Invalid Username', text: 'Please choose an available username.', confirmButtonColor: '#4e73df'});
                    usernameInput.focus();
                    return false;
                }

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