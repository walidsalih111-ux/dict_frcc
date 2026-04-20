<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

include 'connect.php';

$ceremony_date = $_GET['date'] ?? '';
$area_filter = $_GET['area'] ?? ''; // New: Area filter
$flash_message = $_GET['msg'] ?? '';
$flash_error = $_GET['error'] ?? '';

if (empty($ceremony_date)) {
    die("<h3 style='text-align:center; margin-top:50px; font-family:sans-serif;'>Error: No date provided.</h3>");
}

$dateObj = new DateTime($ceremony_date);
$formattedDate = $dateObj->format('F d, Y');

// Variables for stats
$totalCount = 0;
$compliantCount = 0;
$nonCompliantCount = 0;
$notAttendedCount = 0;
$records = [];
$areas = [];

try {
    if ($pdo) {
        // 1. Set predefined areas of assignment for the dropdown filter
        $areas = [
            'Regional Office',
            'Zamboanga City',
            'Zamboanga Del Sur',
            'Zamboanga Del Norte',
            'Basilan',
            'Tawi-Tawi',
            'Sulu'
        ];

        // 2. Count total employees (filtered by area if applicable)
        $empQuery = "SELECT COUNT(*) FROM employees WHERE 1=1";
        $empParams = [];
        if ($area_filter !== '') {
            $empQuery .= " AND area_of_assignment = :area";
            $empParams['area'] = $area_filter;
        }
        $empStmt = $pdo->prepare($empQuery);
        $empStmt->execute($empParams);
        $totalEmployees = $empStmt->fetchColumn();

        // 3. Fetch attendance records
        $query = "
            SELECT 
                a.attendance_id,
                e.full as employee_name,
                e.gender,
                e.emp_id,
                a.time_recorded, 
                a.designation, 
                e.department as division,
                e.unit,
                e.area_of_assignment, 
                a.with_id, 
                a.is_asean as proper_attire,
                a.is_compliant,
                a.photo_path
            FROM attendance_record a 
            JOIN employees e ON a.emp_id = e.emp_id 
            WHERE DATE(a.time_recorded) = :ceremony_date
        ";
        
        $params = ['ceremony_date' => $ceremony_date];

        if ($area_filter !== '') {
            $query .= " AND e.area_of_assignment = :area";
            $params['area'] = $area_filter;
        }
        $query .= " ORDER BY e.full ASC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $records = $stmt->fetchAll();
        
        foreach ($records as $row) {
            $totalCount++;
            if ($row['is_compliant'] == 1) {
                $compliantCount++;
            } else {
                $nonCompliantCount++;
            }
        }

        // 4. Calculate Not Attended
        $notAttendedCount = max(0, $totalEmployees - $totalCount);
    }
} catch (\PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Attendance: <?php echo $formattedDate; ?></title>

    <!-- Bootstrap 5 & Inspinia CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet" />
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="css/animate.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />
    
    <style>
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

        /* Ibox Card Styling matching dashboard */
        .ibox {
            border-radius: 15px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
            background: rgba(255, 255, 255, 0.95) !important;
            border: none !important;
            margin-top: 30px;
            margin-bottom: 25px;
            transition: transform 0.3s ease;
        }

        .ibox:hover {
            transform: translateY(-3px);
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

        .border-warning { border-color: #f6c23e !important; }

        /* Stats Cards */
        .stats-card { 
            border-radius: 10px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
            background: #ffffff !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
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
        .filter-group .form-select {
            border: 1px solid #e3e6f0;
            border-left: none;
            color: #5a5c69;
            cursor: pointer;
        }
        .filter-group .form-select:focus {
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
        .table-hover tbody tr:hover { background-color: #f8f9fc; }
        
        /* Table Cell Alignment */
        .align-middle { vertical-align: middle !important; }

        .action-btn {
            min-width: 78px;
            border-radius: 999px;
        }

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
    </style>
</head>
<body class="gray-bg">

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row justify-content-center">
        <div class="col-lg-12 col-xl-11">
            <div class="page-callout">
                <div>
                    <p class="callout-title">Ceremony Attendance Detail</p>
                    <p class="callout-subtitle">Review attendee compliance status and update individual records when needed.</p>
                </div>
                <span class="callout-icon"><i class="fa fa-clipboard"></i></span>
            </div>
            
            <div class="ibox">
                <div class="ibox-title d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 text-primary fw-bold"><i class="fa fa-users me-2"></i> Flag Ceremony Attendees: <strong class="text-dark" id="ceremony_date_display"><?php echo htmlspecialchars($formattedDate); ?></strong></h4>
                    <div>
                        <button id="viewAttendancePdfBtn" class="btn btn-primary btn-sm me-2 fw-bold shadow-sm">
                            <i class="fa fa-eye"></i> View Export
                        </button>
                        <button id="exportAttendancePdfBtn" class="btn btn-danger btn-sm fw-bold shadow-sm me-2">
                            <i class="fa fa-file-pdf-o"></i> Export to PDF
                        </button>
                        <button onclick="window.close();" class="btn btn-secondary btn-sm shadow-sm">
                            <i class="fa fa-times"></i> Close Tab
                        </button>
                    </div>
                </div>

                <div class="ibox-content">

                    <?php if ($flash_message === 'attendance_updated'): ?>
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            Attendance record updated successfully.
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php elseif ($flash_error === 'attendance_update_failed'): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            Unable to update the attendance record. Please try again.
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- AREA FILTER FORM (IMPROVED UI) -->
                    <div class="row mb-4">
                        <div class="col-md-6 col-lg-4">
                            <form method="GET" action="view_attendance.php">
                                <input type="hidden" name="date" value="<?php echo htmlspecialchars($ceremony_date); ?>">
                                <div class="input-group filter-group">
                                    <span class="input-group-text fw-bold">
                                        <i class="fa fa-filter me-2"></i> Filter Area
                                    </span>
                                    <select name="area" class="form-select fw-semibold" onchange="this.form.submit()">
                                        <option value="">All Areas</option>
                                        <?php foreach ($areas as $a): ?>
                                            <option value="<?php echo htmlspecialchars($a); ?>" <?php echo $area_filter === $a ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($a); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if($area_filter !== ''): ?>
                                        <a href="view_attendance.php?date=<?php echo urlencode($ceremony_date); ?>" class="btn btn-clear" title="Clear Filter">
                                            <i class="fa fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- ATTENDANCE STATISTICS CARDS -->
                    <div class="row mb-4" id="attendance_stats_container">
                        <div class="col-md-3 mb-2 mb-md-0">
                            <div class="p-3 bg-white border-start border-info border-start-3 stats-card text-center">
                                <h6 class="text-info mb-1 fw-bold"><i class="fa fa-users"></i> Total Attendees</h6>
                                <h3 class="mb-0 text-info fw-bold" id="stat_total"><?php echo $totalCount; ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <div class="p-3 bg-white border-start border-success border-start-3 stats-card text-center">
                                <h6 class="text-success mb-1 fw-bold"><i class="fa fa-check-circle"></i> Compliant</h6>
                                <h3 class="mb-0 text-success fw-bold" id="stat_compliant"><?php echo $compliantCount; ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <div class="p-3 bg-white border-start border-danger border-start-3 stats-card text-center">
                                <h6 class="text-danger mb-1 fw-bold"><i class="fa fa-times-circle"></i> Non-Compliant</h6>
                                <h3 class="mb-0 text-danger fw-bold" id="stat_noncompliant"><?php echo $nonCompliantCount; ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <div class="p-3 bg-white border-start border-warning border-start-3 stats-card text-center">
                                <h6 class="text-warning mb-1 fw-bold"><i class="fa fa-user-times"></i> Not Attended</h6>
                                <h3 class="mb-0 text-warning fw-bold" id="stat_notattended" title="Total active employees minus attendees"><?php echo $notAttendedCount; ?></h3>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive border-0">
                        <table class="table table-bordered table-hover mb-0 mt-0">
                            <thead>
                                <tr>
                                    <th>Employee Name</th>
                                    <th style="white-space: nowrap;">Time Recorded</th>
                                    <th>Designation</th>
                                    <th>Division</th>
                                    <th>Unit</th>
                                    <th>Area of Assignment</th>
                                    <th class="text-center">M</th>
                                    <th class="text-center">F</th>
                                    <th class="text-center">With ID</th>
                                    <th class="text-center">Proper Attire</th>
                                    <th class="text-center">Compliant</th>
                                    <th class="text-center">Photo</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="attendance_records_body">
                                <?php
                                if ($records) {
                                    foreach ($records as $row) {
                                        $formattedTime = date('h:i A', strtotime($row['time_recorded']));
                                        $fullDateTime = date('M d, Y - h:i A', strtotime($row['time_recorded']));
                                        
                                        $gender = strtoupper($row['gender'] ?? '');
                                        $isMale = ($gender === 'M' || $gender === 'MALE');
                                        $isFemale = ($gender === 'F' || $gender === 'FEMALE');
                                        
                                        $withIdCheck = ($row['with_id'] === 'Yes') ? '<i class="fa fa-check text-success fa-lg"></i>' : '';
                                        $aseanCheck = ($row['proper_attire'] === 'Yes') ? '<i class="fa fa-check text-success fa-lg"></i>' : '';
                                        
                                        // Bootstrap 5 uses bg-success instead of badge-success
                                        $compliantClass = ($row['is_compliant'] == 1) ? 'bg-success' : 'bg-danger';
                                        $compliantText = ($row['is_compliant'] == 1) ? 'Yes' : 'No';

                                        $attendanceDateTimeValue = date('Y-m-d\TH:i', strtotime($row['time_recorded']));

                                        echo "<tr>";
                                        echo "<td class='fw-bold text-dark align-middle'>
                                                <span class='pdf-full-name'>".ucwords(htmlspecialchars($row['employee_name'] ?? 'Unknown'))."</span>
                                                <span class='pdf-sex' style='display:none;'>".strtoupper($row['gender'] ?? '')."</span>
                                                <span class='pdf-division' style='display:none;'>".htmlspecialchars($row['division'] ?? '')."</span>
                                                <span class='pdf-id-number' style='display:none;'>".htmlspecialchars($row['emp_id'])."</span>
                                                <span class='pdf-with-id' style='display:none;'>".htmlspecialchars($row['with_id'] ?? 'No')."</span>
                                                <span class='pdf-proper-attire' style='display:none;'>".htmlspecialchars($row['proper_attire'] ?? 'No')."</span>
                                              </td>";
                                        echo "<td class='text-muted align-middle'>" . $formattedTime . "</td>";
                                        echo "<td class='align-middle'><span class='pdf-designation text-muted'>" . htmlspecialchars($row['designation'] ?? 'N/A') . "</span></td>";
                                        echo "<td class='text-muted align-middle'>" . htmlspecialchars($row['division'] ?? 'N/A') . "</td>";
                                        echo "<td class='text-muted align-middle'>" . htmlspecialchars($row['unit'] ?? 'N/A') . "</td>";
                                        echo "<td class='text-muted align-middle'>" . htmlspecialchars($row['area_of_assignment'] ?? 'N/A') . "</td>";
                                        
                                        echo "<td class='text-center align-middle'>" . ($isMale ? '<i class="fa fa-check text-primary fa-lg"></i>' : '') . "</td>";
                                        echo "<td class='text-center align-middle'>" . ($isFemale ? '<i class="fa fa-check text-primary fa-lg"></i>' : '') . "</td>";
                                        
                                        echo "<td class='text-center align-middle'>{$withIdCheck}</td>";
                                        echo "<td class='text-center align-middle'>{$aseanCheck}</td>";
                                        echo "<td class='text-center align-middle'><span class='badge {$compliantClass} px-3 py-2 shadow-sm rounded-pill'>" . $compliantText . "</span></td>";

                                        if (!empty($row['photo_path'])) {
                                            $safePath = htmlspecialchars($row['photo_path'], ENT_QUOTES, 'UTF-8');
                                            echo "<td class='text-center align-middle'>
                                                    <img src='{$safePath}' 
                                                         alt='Photo' 
                                                         class='shadow-sm'
                                                         style='width: 45px; height: 45px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid #e3e6f0; transition: transform 0.2s;' 
                                                         onmouseover='this.style.transform=\"scale(1.1)\"' 
                                                         onmouseout='this.style.transform=\"scale(1)\"'
                                                         onclick='viewAttendancePhoto(\"{$safePath}\", \"{$fullDateTime}\")' 
                                                         title='Click to view full image'>
                                                  </td>";
                                        } else {
                                            echo "<td class='text-center align-middle'><span class='badge bg-light text-muted border px-2 py-1 fw-normal'><i class='fa fa-eye-slash'></i> No Photo</span></td>";
                                        }

                                        echo "<td class='text-center align-middle'>
                                                <button type='button'
                                                        class='btn btn-sm btn-outline-primary action-btn edit-attendance-btn'
                                                        data-toggle='modal'
                                                        data-target='#editAttendanceModal'
                                                data-attendance-id='" . htmlspecialchars($row['attendance_id'], ENT_QUOTES, 'UTF-8') . "'
                                                        data-emp-id='" . htmlspecialchars($row['emp_id'], ENT_QUOTES, 'UTF-8') . "'
                                                        data-employee-name='" . htmlspecialchars($row['employee_name'] ?? '', ENT_QUOTES, 'UTF-8') . "'
                                                        data-time-recorded='" . htmlspecialchars($attendanceDateTimeValue, ENT_QUOTES, 'UTF-8') . "'
                                                        data-designation='" . htmlspecialchars($row['designation'] ?? '', ENT_QUOTES, 'UTF-8') . "'
                                                        data-with-id='" . htmlspecialchars($row['with_id'] ?? 'No', ENT_QUOTES, 'UTF-8') . "'
                                                        data-proper-attire='" . htmlspecialchars($row['proper_attire'] ?? 'No', ENT_QUOTES, 'UTF-8') . "'
                                                        data-is-compliant='" . htmlspecialchars((string)($row['is_compliant'] ?? 0), ENT_QUOTES, 'UTF-8') . "'
                                                        data-photo-path='" . htmlspecialchars($row['photo_path'] ?? '', ENT_QUOTES, 'UTF-8') . "'>
                                                    <i class='fa fa-pencil'></i> Edit
                                                </button>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr class='no-data-row'><td colspan='13' class='text-center text-muted py-5'>
                                            <i class='fa fa-folder-open-o fa-3x mb-3 d-block text-primary opacity-50'></i>
                                            <em style='font-size: 1.2rem;'>No attendees found for this date.</em>
                                          </td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Photo Viewer Modal (Bootstrap 5) -->
<div class="modal fade" id="photoViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header p-3 bg-light" style="border-radius: 15px 15px 0 0;">
                <h5 class="modal-title text-primary fw-bold mb-0"><i class="fa fa-camera"></i> Attendance Snapshot</h5>
            </div>
            <div class="modal-body text-center bg-dark p-2">
                <img id="attendanceImagePreview" src="" alt="Captured Photo" class="img-fluid rounded" style="max-height: 500px; width: 100%; object-fit: contain;">
                <div class="mt-3 mb-2 text-white">
                    <span class="badge bg-primary p-2 shadow-sm rounded-pill" style="font-size: 0.95rem;">
                        <i class="fa fa-clock-o"></i> Captured on: <span id="attendancePhotoTime"></span>
                    </span>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 d-flex justify-content-center" style="border-radius: 0 0 15px 15px;">
            </div>
        </div>
    </div>
</div>

<!-- Edit Attendance Modal -->
<div class="modal fade" id="editAttendanceModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary font-weight-bold"><i class="fa fa-pencil me-1"></i> Edit Attendance Record</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="process_edit_attendance.php">
                <div class="modal-body">
                    <input type="hidden" name="attendance_id" id="edit_attendance_id">
                    <input type="hidden" name="emp_id" id="edit_attendance_emp_id">
                    <input type="hidden" name="original_time_recorded" id="edit_original_time_recorded">
                    <input type="hidden" name="date" value="<?php echo htmlspecialchars($ceremony_date); ?>">
                    <input type="hidden" name="area" value="<?php echo htmlspecialchars($area_filter); ?>">

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Employee Name</label>
                                <input type="text" id="edit_attendance_employee_name" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Time Recorded</label>
                                <input type="datetime-local" name="time_recorded" id="edit_attendance_time_recorded" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>With ID</label>
                                <select name="with_id" id="edit_attendance_with_id" class="form-control" required>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Proper Attire</label>
                                <select name="proper_attire" id="edit_attendance_proper_attire" class="form-control" required>
                                    <option value="Yes">Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Compliant</label>
                                <select name="is_compliant" id="edit_attendance_is_compliant" class="form-control" required>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mb-0 mt-3">
                        The attached photo stays unchanged when you update this record.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/jquery-3.1.1.min.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
    window.printedByAdmin = "<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') : 'Admin'; ?>";
</script>
<script>
    $(document).ready(function() {
        $('#attendance_records_body').on('click', '.edit-attendance-btn', function() {
            var button = $(this);

            $('#edit_attendance_id').val(button.data('attendance-id'));
            $('#edit_attendance_emp_id').val(button.data('emp-id'));
            $('#edit_attendance_employee_name').val(button.data('employee-name'));
            $('#edit_original_time_recorded').val(button.data('time-recorded'));
            $('#edit_attendance_time_recorded').val(button.data('time-recorded'));
            $('#edit_attendance_with_id').val(button.data('with-id'));
            $('#edit_attendance_proper_attire').val(button.data('proper-attire'));
            $('#edit_attendance_is_compliant').val(String(button.data('is-compliant')));

            $('#editAttendanceModal').modal('show');
        });
    });
</script>
<script src="js/pdf.js"></script>
</body>
</html>