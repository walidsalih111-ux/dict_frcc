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

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">

    <!-- Theme & Custom CSS -->
    <style>
        /* Base typography and body */
        body {
            background-color: #f3f3f4;
            color: #676a6c;
            font-family: "Open Sans", "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 13px;
            margin: 0;
            padding-bottom: 40px;
        }

        h1, h2, h3, h4, h5, h6 {
            font-weight: 300;
        }

        /* Top Navbar */
        .navbar-top {
            background: #ffffff;
            border-bottom: 1px solid #e7eaec;
            margin-bottom: 30px;
            min-height: 60px;
        }
        
        .navbar-brand-custom {
            background-color: #1ab394;
            color: #ffffff !important;
            padding: 18px 25px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            margin-right: 20px;
        }

        .navbar-top .nav-link {
            color: #888888;
            font-weight: 600;
            padding: 20px 15px;
        }

        .navbar-top .nav-link:hover {
            color: #1ab394;
        }

        /* The 'ibox' Panel */
        .ibox {
            clear: both;
            margin-bottom: 25px;
            margin-top: 0;
            padding: 0;
            background-color: #ffffff;
            border-top: 4px solid #e7eaec;
            box-shadow: 0 2px 2px 0 rgba(0,0,0,0.05);
        }

        .ibox-title {
            background-color: #ffffff;
            border-color: #e7eaec;
            border-image: none;
            border-style: solid solid none;
            border-width: 1px 0 0;
            color: inherit;
            margin-bottom: 0;
            padding: 15px 15px 8px;
            min-height: 48px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ibox-title h5 {
            display: inline-block;
            font-size: 14px;
            margin: 0 0 7px;
            padding: 0;
            text-overflow: ellipsis;
            float: left;
            font-weight: 600;
        }

        .ibox-content {
            background-color: #ffffff;
            color: inherit;
            padding: 15px 20px 20px 20px;
            border-color: #e7eaec;
            border-image: none;
            border-style: solid solid none;
            border-width: 1px 0;
        }

        /* Labels (Badges) */
        .label {
            font-family: 'Open Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            font-weight: 600;
            padding: 3px 8px;
            text-shadow: none;
            border-radius: 0.25em;
        }
        .label-primary { background-color: #1ab394; color: #FFFFFF; }
        .label-danger { background-color: #ed5565; color: #FFFFFF; }
        .label-info { background-color: #23c6c8; color: #FFFFFF; }
        .label-success { background-color: #1c84c6; color: #FFFFFF; } /* Inspinia success is blueish */
        .label-warning { background-color: #f8ac59; color: #FFFFFF; }
        .label-plain { background-color: #d1dade; color: #5e5e5e; }

        /* Tables */
        .table > thead > tr > th {
            border-bottom: 1px solid #e7eaec;
            font-weight: 600;
            color: #333;
        }
        .table > tbody > tr > td {
            border-top: 1px solid #e7eaec;
            vertical-align: middle;
        }

        /* Pagination overrides for Inspinia look */
        .pagination > li > a, .pagination > li > span {
            color: #676a6c;
            background-color: #ffffff;
            border: 1px solid #e7eaec;
            margin-left: -1px;
            padding: 5px 10px;
            font-size: 12px;
        }
        .pagination > li.active > a, .pagination > li.active > span,
        .pagination > li.active > a:hover, .pagination > li.active > span:hover {
            background-color: #1ab394;
            border-color: #1ab394;
            color: #fff;
            z-index: 3;
        }
        .pagination > li > a:hover, .pagination > li > span:hover {
            background-color: #eee;
            border-color: #dddddd;
            color: #676a6c;
        }
        .pagination > li.disabled > a, .pagination > li.disabled > span {
            color: #d1dade;
            background-color: #fff;
            border-color: #e7eaec;
        }

        /* Forms Elements */
        .form-select-sm {
            border-radius: 2px;
            border: 1px solid #e5e6e7;
            color: #676a6c;
        }
        .form-select-sm:focus {
            border-color: #1ab394;
            box-shadow: none;
        }
        
        /* Links */
        .text-inspinia {
            color: #1ab394;
            font-weight: 600;
        }
        .text-inspinia:hover {
            color: #18a689;
        }

        /* Profile Modal specific overrides to match Admin side */
        .profile-avatar {
            width: 100px;
            height: 100px;
            object-fit: cover;
            background-color: #e9ecef;
            color: #6c757d;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }
        .modal-content.profile-modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .modal-content.profile-modal-content .modal-header {
            border-radius: 15px 15px 0 0;
            background-color: #f8f9fa;
        }

        /* Entry Animation */
        @keyframes fadeInRight {
            0% { opacity: 0; transform: translateX(20px); }
            100% { opacity: 1; transform: translateX(0); }
        }
        .animated.fadeInRight {
            animation-name: fadeInRight;
            animation-duration: 0.6s;
            animation-fill-mode: both;
        }

        .page-callout {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(26, 179, 148, 0.25);
            border-left: 5px solid #1ab394;
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
            background: rgba(26, 179, 148, 0.12);
            color: #1ab394;
            flex-shrink: 0;
        }

        @media (max-width: 767px) {
            .page-callout {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-top p-0">
        <a class="navbar-brand-custom text-decoration-none d-inline-flex align-items-center" href="#">
            <img src="img/logo/DICT.png" alt="DICT Logo" style="height: 24px;" class="me-2">
            DICT Monday Flag Raising 
        </a>
        <button class="navbar-toggler me-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse px-3" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <span class="text-muted me-3">Attendance Records</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="modal" data-bs-target="#profileModal" style="cursor: pointer;">
                        <i class="bi bi-person-circle me-1"></i> <strong><?php echo $fullname; ?></strong>
                    </a>
                </li>
                <li class="nav-item ms-2 border-start ps-3">
                    <a class="nav-link" href="#" id="logoutBtn">
                        <i class="bi bi-box-arrow-right"></i> Log out
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="container wrapper wrapper-content animated fadeInRight">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="page-callout">
                    <div>
                        <p class="callout-title">My Attendance Timeline</p>
                        <p class="callout-subtitle">Track your compliance history, filter by date, and review your submitted attendance records.</p>
                    </div>
                    <span class="callout-icon"><i class="bi bi-calendar-check"></i></span>
                </div>
                
                <!-- IBox Panel -->
                <div class="ibox">
                    
                    <div class="ibox-content">

                        <!-- Compliance Summary Card -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #1ab394 0%, #18a689 100%); color: white;">
                                    <div class="card-body text-center">
                                        <h5 class="card-title mb-3"><i class="bi bi-bar-chart-line me-2"></i>Attendance Compliance Summary</h5>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="d-flex flex-column align-items-center">
                                                    <span class="h2 mb-1"><?php echo $compliantCount; ?></span>
                                                    <span class="small">Compliant</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="d-flex flex-column align-items-center">
                                                    <span class="h2 mb-1"><?php echo $nonCompliantCount; ?></span>
                                                    <span class="small">Non-Compliant</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Show Entries Dropdown and Date Search -->
                        <div class="row mb-3 align-items-center">
                            <div class="col-sm-6">
                                <form method="GET" action="my_attendance.php" class="d-inline-flex align-items-center" id="entriesForm">
                                    <label class="mb-0 me-2 text-muted fw-normal">Show</label>
                                    <select name="limit" class="form-select form-select-sm w-auto d-inline-block shadow-none" onchange="document.getElementById('entriesForm').submit();">
                                        <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                                        <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                    </select>
                                    <label class="mb-0 ms-2 text-muted fw-normal">entries</label>
                                    
                                    <!-- Preserve page and date context -->
                                    <input type="hidden" name="page" value="1">
                                    <?php if ($dateFilter): ?>
                                        <input type="hidden" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>">
                                    <?php endif; ?>
                                </form>
                            </div>
                            <div class="col-sm-6 d-flex justify-content-end">
                                <form method="GET" action="my_attendance.php" class="d-flex" id="dateSearchForm">
                                    <input type="date" name="date" class="form-control form-control-sm me-2" value="<?php echo htmlspecialchars($dateFilter ?? ''); ?>" style="width: 150px;">
                                    <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                                    <button type="submit" class="btn btn-sm btn-primary me-2">Search</button>
                                    <?php if ($dateFilter): ?>
                                        <a href="my_attendance.php?limit=<?php echo $limit; ?>" class="btn btn-sm btn-secondary">Clear</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Main Table -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
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
                                            ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo date('M d, Y', strtotime($record['time_recorded'])); ?></strong><br>
                                                    <div class="mt-1 d-flex align-items-center">
                                                        <small class="text-muted"><i class="bi bi-clock me-1"></i><?php echo date('h:i A', strtotime($record['time_recorded'])); ?></small>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($record['designation'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($record['department'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($record['unit'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($record['area_of_assignment'] ?? 'N/A'); ?></td>
                                                
                                                <td class="text-center">
                                                    <?php if ($hasId): ?>
                                                        <span class="label label-primary"><i class="bi bi-check me-1"></i>Yes</span>
                                                    <?php else: ?>
                                                        <span class="label label-danger"><i class="bi bi-x me-1"></i>No</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($hasProperAttire): ?>
                                                        <span class="label label-primary"><i class="bi bi-check me-1"></i>Yes</span>
                                                    <?php else: ?>
                                                        <span class="label label-danger"><i class="bi bi-x me-1"></i>No</span>
                                                    <?php endif; ?>
                                                </td>
                                                
                                                <!-- Dynamic Compliant Column Data -->
                                                <td class="text-center">
                                                    <?php if ($isCompliant): ?>
                                                        <span class="label label-primary"><i class="bi bi-check me-1"></i>Yes</span>
                                                    <?php else: ?>
                                                        <span class="label label-danger"><i class="bi bi-x me-1"></i>No</span>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- Dynamic Photo Column moved to the end -->
                                                <td class="text-center">
                                                    <?php if (!empty($record['photo_path'])): ?>
                                                        <a href="javascript:void(0);" onclick="viewPhoto('<?php echo htmlspecialchars($record['photo_path']); ?>', '<?php echo $formattedDateTime; ?>')" class="text-decoration-none text-inspinia">
                                                            <i class="bi bi-camera-fill me-1"></i>View
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted small">None</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                <h3 class="fw-light mb-1 mt-3">No Data Found</h3>
                                                <p class="small text-muted mb-4">You have no attendance records at this time.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer Pagination (Inspinia Style) -->
                        <div class="row mt-3 align-items-center">
                            <div class="col-sm-12 col-md-5">
                                <?php
                                    $startEntry = ($totalRecords > 0) ? $offset + 1 : 0;
                                    $endEntry = min($offset + $limit, $totalRecords);
                                ?>
                                <div class="text-muted" style="font-size: 13px;">
                                    Showing <?php echo $startEntry; ?> to <?php echo $endEntry; ?> of <?php echo $totalRecords; ?> entries
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                                <?php if ($totalPages > 1): ?>
                                    <ul class="pagination pagination-sm mb-0">
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

    <!-- VIEW MODAL (Matched to view_employee.php style) -->
    <div class="modal fade" id="profileModal" tabindex="-1" role="dialog" aria-labelledby="profileModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
        <div class="modal-content profile-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileModalLabel"><i class="bi bi-person-circle me-1 text-secondary"></i> Employee Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="profile-avatar rounded-circle mb-2 mx-auto">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <h3 class="mt-2 fw-bold"><?php echo htmlspecialchars($userProfile['full'] ?? $fullname); ?></h3>
                    <p class="text-muted mb-1"><?php echo htmlspecialchars($userProfile['designation'] ?? 'Designation Not Set'); ?>  |  <?php echo htmlspecialchars($userProfile['department'] ?? 'N/A'); ?></p>
                    <?php 
                        $status = htmlspecialchars($userProfile['status'] ?? 'N/A');
                        $badgeClass = 'bg-secondary';
                        $lowerStatus = strtolower($status);
                        
                        // Badge logic mapping to match Admin view colors
                        if(in_array($lowerStatus, ['plantilla', 'active', 'permanent', 'regular'])) {
                            $badgeClass = 'bg-success';
                        } elseif(in_array($lowerStatus, ['job order', 'contractual', 'temporary'])) {
                            $badgeClass = 'bg-warning text-dark';
                        } elseif(in_array($lowerStatus, ['inactive', 'resigned'])) {
                            $badgeClass = 'bg-danger';
                        }
                    ?>
                    <span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span>
                </div>
                <hr>
                <table class="table table-borderless table-sm m-0">
                    <tbody>
                        <tr>
                            <th class="text-end text-muted" width="40%">Employee ID:</th>
                            <td class="fw-bold"><?php echo htmlspecialchars($userProfile['emp_id'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th class="text-end text-muted">Full Name:</th>
                            <td><?php echo htmlspecialchars($userProfile['full'] ?? $fullname); ?></td>
                        </tr>
                        <tr>
                            <th class="text-end text-muted">Email Address:</th>
                            <td><?php echo !empty($userProfile['emp_email']) ? htmlspecialchars($userProfile['emp_email']) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <th class="text-end text-muted">Age:</th>
                            <td><?php echo htmlspecialchars($userProfile['age'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th class="text-end text-muted">Gender:</th>
                            <td><?php echo htmlspecialchars($userProfile['gender'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th class="text-end text-muted">Unit:</th>
                            <td><?php echo htmlspecialchars($userProfile['unit'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th class="text-end text-muted">Area of Assignment:</th>
                            <td><?php echo htmlspecialchars($userProfile['area_of_assignment'] ?? 'N/A'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary me-auto" id="openAccountSettingsBtn">Edit Account</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
        </div>
    </div>

    <!-- Account Settings Modal -->
    <div class="modal fade" id="accountSettingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content profile-modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-gear me-1 text-secondary"></i> Account Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="accountSettingsForm" method="POST" action="process_account_settings.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-muted">Username</label>
                        <input type="text" name="username" id="acc_username" class="form-control" required value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Email</label>
                        <input type="email" name="emp_email" id="acc_email" class="form-control" value="<?php echo htmlspecialchars($userProfile['emp_email'] ?? ''); ?>">
                    </div>
                    <hr>
                    <p class="small text-muted mb-2">Change Password (leave blank to keep current password)</p>
                    <div class="mb-3 position-relative">
                        <label class="form-label small text-muted">Current Password</label>
                        <input type="password" name="current_password" id="acc_current_password" class="form-control">
                        <span class="position-absolute top-50 end-0 translate-middle-y me-3 toggle-password" onclick="togglePassword('acc_current_password', this)"><i class="bi bi-eye"></i></span>
                    </div>
                    <div class="mb-3 position-relative">
                        <label class="form-label small text-muted">New Password</label>
                        <input type="password" name="new_password" id="acc_new_password" class="form-control" minlength="8" placeholder="At least 8 characters">
                        <span class="position-absolute top-50 end-0 translate-middle-y me-3 toggle-password" onclick="togglePassword('acc_new_password', this)"><i class="bi bi-eye"></i></span>
                    </div>
                    <div class="mb-3 position-relative">
                        <label class="form-label small text-muted">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="acc_confirm_password" class="form-control">
                        <span class="position-absolute top-50 end-0 translate-middle-y me-3 toggle-password" onclick="togglePassword('acc_confirm_password', this)"><i class="bi bi-eye"></i></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-white" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveAccountBtn">Save Changes</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Photo Viewer Modal -->
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    
    <script>
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
                icon.classList.remove("bi-eye");
                icon.classList.add("bi-eye-slash");
            } else {
                pass.type = "password";
                icon.classList.remove("bi-eye-slash");
                icon.classList.add("bi-eye");
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
                        Swal.fire({icon:'warning', title:'Weak password', text: 'New password must be at least 8 characters.'});
                        return false;
                    }
                    if (newPass !== confPass) {
                        e.preventDefault();
                        Swal.fire({icon:'warning', title:'Password mismatch', text: 'New password and confirmation do not match.'});
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
                confirmButtonColor: '#1ab394', // Match Inspinia Primary Color
                cancelButtonColor: '#ed5565',  // Match Inspinia Danger Color
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
            Swal.fire({icon:'success', title: 'Account updated', text: 'Your account settings have been updated.'});
        <?php elseif (isset($_GET['account_update']) && $_GET['account_update'] === 'error'): ?>
            Swal.fire({icon:'error', title: 'Update failed', text: <?php echo json_encode($_GET['reason'] ?? 'An error occurred'); ?>});
        <?php endif; ?>
    </script>
</body>
</html> 