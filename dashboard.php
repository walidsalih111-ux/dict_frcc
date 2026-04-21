<?php
session_start();

// Check if the user is logged in and has an 'admin' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect to the login page if not authenticated
    header("Location: index.php");
    exit();
}

include 'connect.php';

if (!$pdo) {
    die('Database connection failed: ' . ($db_error ?? 'Unknown error'));
}

// ================= NEW: POLLING ENDPOINT FOR LIVE UPDATES =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_new_attendance') {
    $selectedArea = isset($_POST['area']) ? $_POST['area'] : '';
    $clientCount = isset($_POST['current_count']) ? (int)$_POST['current_count'] : -1;
    $clientDate = isset($_POST['current_date']) ? $_POST['current_date'] : '';

    try {
        // Sync with the newest date in data_table
        $stmtLatestDate = $pdo->query("SELECT MAX(DATE(time_recorded)) FROM attendance_record");
        $targetDate = $stmtLatestDate->fetchColumn() ?: date('Y-m-d');

        $stmt = $pdo->prepare("SELECT COUNT(attendance_id) FROM attendance_record WHERE DATE(time_recorded) = :date");
        $stmt->execute(['date' => $targetDate]);
        $totalAttendanceToday = (int)$stmt->fetchColumn();

        if ($totalAttendanceToday !== $clientCount || $targetDate !== $clientDate) {
            // Data has changed! Fetch everything needed for the dashboard dynamically.
            
            // Compliant
            $compliantQuery = "SELECT COUNT(DISTINCT a.emp_id) FROM attendance_record a JOIN employees e ON a.emp_id = e.emp_id WHERE a.is_compliant = 1 AND DATE(a.time_recorded) = :date";
            $compParams = ['date' => $targetDate];
            if ($selectedArea) {
                $compliantQuery .= " AND e.area_of_assignment = :area";
                $compParams['area'] = $selectedArea;
            }
            $stmt = $pdo->prepare($compliantQuery);
            $stmt->execute($compParams);
            $compliantCount = $stmt->fetchColumn();

            // Non-Compliant
            $nonCompliantQuery = "SELECT COUNT(DISTINCT a.emp_id) FROM attendance_record a JOIN employees e ON a.emp_id = e.emp_id WHERE a.is_compliant = 0 AND DATE(a.time_recorded) = :date";
            $nonCompParams = ['date' => $targetDate];
            if ($selectedArea) {
                $nonCompliantQuery .= " AND e.area_of_assignment = :area";
                $nonCompParams['area'] = $selectedArea;
            }
            $stmt = $pdo->prepare($nonCompliantQuery);
            $stmt->execute($nonCompParams);
            $nonCompliantCount = $stmt->fetchColumn();

            // Chart Data (Compliant by Area)
            $areaQuery = "SELECT area_of_assignment FROM employees WHERE area_of_assignment != 'area_of_assignment' AND TRIM(area_of_assignment) != '' AND area_of_assignment IS NOT NULL";
            if ($selectedArea) {
                $areaQuery .= " AND area_of_assignment = :area";
                $stmt = $pdo->prepare($areaQuery . " GROUP BY area_of_assignment");
                $stmt->execute(['area' => $selectedArea]);
            } else {
                $stmt = $pdo->query($areaQuery . " GROUP BY area_of_assignment");
            }
            $areaData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $chartLabels = array_column($areaData, 'area_of_assignment');
            $chartData = [];
            foreach ($areaData as $area) {
                $stmt = $pdo->prepare("SELECT COUNT(DISTINCT a.emp_id) FROM attendance_record a JOIN employees e ON a.emp_id = e.emp_id WHERE a.is_compliant = 1 AND DATE(a.time_recorded) = ? AND e.area_of_assignment = ?");
                $stmt->execute([$targetDate, $area['area_of_assignment']]);
                $chartData[] = $stmt->fetchColumn();
            }

            $latestDateFormatted = date('M d, Y', strtotime($targetDate));

            header('Content-Type: application/json');
            echo json_encode([
                'changed' => true,
                'count' => $totalAttendanceToday,
                'target_date' => $targetDate,
                'compliantCount' => $compliantCount,
                'nonCompliantCount' => $nonCompliantCount,
                'chartLabels' => $chartLabels,
                'chartData' => $chartData,
                'latestDateFormatted' => $latestDateFormatted
            ]);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode(['changed' => false]);
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['changed' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
// ===========================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_compliance_list') {
    $status = isset($_POST['status']) && $_POST['status'] === '1' ? 1 : 0;
    $area = isset($_POST['area']) ? $_POST['area'] : ''; // Fetch area filter from AJAX
    
    // Determine active target date
    $stmtLatestDate = $pdo->query("SELECT MAX(DATE(time_recorded)) FROM attendance_record");
    $targetDate = $stmtLatestDate->fetchColumn() ?: date('Y-m-d');
    
    if (!$pdo) {
        echo "<tr><td colspan='6' class='text-center text-danger'>Database connection failed.</td></tr>";
        exit;
    }
    
    try {
        $query = "SELECT 
                e.emp_id, 
                e.full, 
                e.designation,
                e.department,
                e.unit,
                e.area_of_assignment,
                MAX(a.time_recorded) AS time_recorded
            FROM attendance_record a
            JOIN employees e ON a.emp_id = e.emp_id
            WHERE DATE(a.time_recorded) = :targetDate
              AND a.is_compliant = :status";
              
        $params = ['status' => $status, 'targetDate' => $targetDate];
        
        // Apply area filter if selected
        if ($area !== '') {
            $query .= " AND e.area_of_assignment = :area";
            $params['area'] = $area;
        }

        $query .= " GROUP BY e.emp_id, e.full, e.designation, e.department, e.unit, e.area_of_assignment ORDER BY time_recorded DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($rows) {
            foreach ($rows as $row) {
                $fullName = htmlspecialchars(ucwords($row['full'] ?? 'N/A'));
                $designation = htmlspecialchars($row['designation'] ?? 'N/A');
                $division = htmlspecialchars($row['department'] ?? 'N/A');
                $unit = htmlspecialchars($row['unit'] ?? 'N/A');
                $areaOfAssignment = htmlspecialchars($row['area_of_assignment'] ?? 'N/A');
                $formattedTime = !empty($row['time_recorded']) ? htmlspecialchars(date('M d, Y - h:i A', strtotime($row['time_recorded']))) : 'N/A';

                echo "<tr>";
                echo "<td>" . $fullName . "</td>";
                echo "<td>" . $designation . "</td>";
                echo "<td>" . $division . "</td>";
                echo "<td>" . $unit . "</td>";
                echo "<td>" . $areaOfAssignment . "</td>";
                echo "<td>" . $formattedTime . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6' class='text-center text-muted py-4'><i class='fa fa-folder-open-o fa-2x mb-2 d-block'></i><em>No employees found for this category on this date.</em></td></tr>";
        }
    } catch (PDOException $e) {
        echo "<tr><td colspan='6' class='text-center text-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
    }
    exit;
}

$allAreasList = [];
$selectedArea = isset($_GET['area']) ? $_GET['area'] : '';

try {
    // 0. Fetch all distinct areas to populate the dropdown filter
    $stmtAreas = $pdo->query("SELECT DISTINCT area_of_assignment FROM employees WHERE area_of_assignment != 'area_of_assignment' AND TRIM(area_of_assignment) != '' AND area_of_assignment IS NOT NULL ORDER BY area_of_assignment");
    $allAreasList = $stmtAreas->fetchAll(PDO::FETCH_COLUMN);

    // 1. Get Total Employees
    $totalEmployeesQuery = "SELECT COUNT(emp_id) FROM employees";
    $stmt = $pdo->prepare($totalEmployeesQuery);
    $stmt->execute();
    $totalEmployees = $stmt->fetchColumn();

    // 2. Get Employees per Department
    $deptQuery = "SELECT department, COUNT(emp_id) as count FROM employees GROUP BY department";
    $stmt = $pdo->prepare($deptQuery);
    $stmt->execute();
    $departmentsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get Employees by Gender
    $sexQuery = "SELECT gender, COUNT(emp_id) as count FROM employees GROUP BY gender";
    $stmt = $pdo->prepare($sexQuery);
    $stmt->execute();
    $sexData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Get Employees by Status
    $statusQuery = "SELECT status, COUNT(emp_id) as count FROM employees WHERE status IN ('plantilla', 'job order')";
    if ($selectedArea) {
        $statusQuery .= " AND area_of_assignment = :area";
        $stmt = $pdo->prepare($statusQuery . " GROUP BY status");
        $stmt->execute(['area' => $selectedArea]);
    } else {
        $stmt = $pdo->query($statusQuery . " GROUP BY status");
    }
    $statusData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Get Employees by Area of Assignment (Filtered)
    $areaQuery = "SELECT area_of_assignment, COUNT(emp_id) as count FROM employees WHERE area_of_assignment != 'area_of_assignment' AND TRIM(area_of_assignment) != '' AND area_of_assignment IS NOT NULL";
    if ($selectedArea) {
        $areaQuery .= " AND area_of_assignment = :area";
        $stmt = $pdo->prepare($areaQuery . " GROUP BY area_of_assignment");
        $stmt->execute(['area' => $selectedArea]);
    } else {
        $stmt = $pdo->query($areaQuery . " GROUP BY area_of_assignment");
    }
    $areaData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Get Active Event Date 
    $stmtLatestDate = $pdo->query("SELECT MAX(DATE(time_recorded)) FROM attendance_record");
    $targetDate = $stmtLatestDate->fetchColumn() ?: date('Y-m-d');
    
    // Compliant Employees (Filtered by Area if applicable)
    $compliantQuery = "SELECT COUNT(DISTINCT a.emp_id) FROM attendance_record a JOIN employees e ON a.emp_id = e.emp_id WHERE a.is_compliant = 1 AND DATE(a.time_recorded) = :date";
    $compParams = ['date' => $targetDate];
    if ($selectedArea) {
        $compliantQuery .= " AND e.area_of_assignment = :area";
        $compParams['area'] = $selectedArea;
    }
    $stmt = $pdo->prepare($compliantQuery);
    $stmt->execute($compParams);
    $compliantCount = $stmt->fetchColumn();

    // Non-Compliant Employees (Filtered by Area if applicable)
    $nonCompliantQuery = "SELECT COUNT(DISTINCT a.emp_id) FROM attendance_record a JOIN employees e ON a.emp_id = e.emp_id WHERE a.is_compliant = 0 AND DATE(a.time_recorded) = :date";
    $nonCompParams = ['date' => $targetDate];
    if ($selectedArea) {
        $nonCompliantQuery .= " AND e.area_of_assignment = :area";
        $nonCompParams['area'] = $selectedArea;
    }
    $stmt = $pdo->prepare($nonCompliantQuery);
    $stmt->execute($nonCompParams);
    $nonCompliantCount = $stmt->fetchColumn();

    // Show compliant employees per area for Bar Chart
    $chartLabels = array_column($areaData, 'area_of_assignment');
    $chartData = [];
    foreach ($areaData as $area) {
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT a.emp_id) FROM attendance_record a JOIN employees e ON a.emp_id = e.emp_id WHERE a.is_compliant = 1 AND DATE(a.time_recorded) = ? AND e.area_of_assignment = ?");
        $stmt->execute([$targetDate, $area['area_of_assignment']]);
        $chartData[] = $stmt->fetchColumn();
    }
    $chartLabel = 'Compliant Employees';
    $chartTitle = 'Compliant Employees by Area' . ($selectedArea ? ' (' . htmlspecialchars($selectedArea) . ')' : '');

    $statusTitle = 'Employees by Status' . ($selectedArea ? ' (' . htmlspecialchars($selectedArea) . ')' : '');

    // Get total attendance for the target date (for live polling comparison)
    $stmt = $pdo->prepare("SELECT COUNT(attendance_id) FROM attendance_record WHERE DATE(time_recorded) = :date");
    $stmt->execute(['date' => $targetDate]);
    $totalAttendanceToday = $stmt->fetchColumn();

} catch (PDOException $e) {
    // If DB fails to connect, fallback to empty data to prevent page breaking
    $totalEmployees = 0;
    $departmentsData = [];
    $sexData = [];
    $statusData = [];
    $areaData = [];
    $compliantCount = 0;
    $nonCompliantCount = 0;
    $chartLabels = [];
    $chartData = [];
    $chartLabel = '';
    $chartTitle = 'Chart';
    $statusTitle = 'Employees by Status';
    $totalAttendanceToday = 0;
    $dbError = $e->getMessage();
}

// Convert arrays to JSON so we can use them in JavaScript for Chart.js
$deptLabels = json_encode(array_column($departmentsData, 'department'));
$deptCounts = json_encode(array_column($departmentsData, 'count'));

$sexLabels = json_encode(array_column($sexData, 'gender'));
$sexCounts = json_encode(array_column($sexData, 'count'));

$statusLabels = json_encode(array_column($statusData, 'status'));
$statusCounts = json_encode(array_column($statusData, 'count'));

$areaLabels = json_encode(array_column($areaData, 'area_of_assignment'));
$areaCounts = json_encode(array_column($areaData, 'count'));

$chartLabelsJson = json_encode($chartLabels);
$chartDataJson = json_encode($chartData);
$chartLabelJson = json_encode($chartLabel);
$chartTitleJson = json_encode($chartTitle);

// Pie chart specific data
$pieDataJson = json_encode([$compliantCount, $nonCompliantCount]);

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>DICT Monday Flag Raising Attendance and Compliance Checker</title>
    <link rel="icon" type="image/png" href="img/logo/DICT.png">

    <link href="css/bootstrap.min.css" rel="stylesheet" />
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet" />

    <link href="css/plugins/morris/morris-0.4.3.min.css" rel="stylesheet" />

    <link href="css/animate.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />
  
    <!-- Custom UI to match Index Page -->
    <style>
        /* Animated Gradient Background for the main content area */
        body.gray-bg, #page-wrapper, .wrapper.wrapper-content {
            background: linear-gradient(135deg, #4e73df, #1cc88a) !important;
            background-size: 200% 200% !important;
            animation: gradientBG 10s ease infinite !important;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Card (ibox) Styling to match login card */
        .ibox {
            border-radius: 15px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
            background: rgba(255, 255, 255, 0.95) !important;
            border: none !important;
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
            padding: 15px 20px !important;
        }

        .ibox-content {
            background: transparent !important;
            border-radius: 0 0 15px 15px !important;
            border: none !important;
        }

        /* Update text colors to match the blue/green theme */
        .text-success { color: #1cc88a !important; }
        .text-primary { color: #4e73df !important; }
        .text-info { color: #36b9cc !important; }
        .text-danger { color: #e74a3b !important; }
        
        /* Update badge backgrounds */
        .bg-success { background-color: #1cc88a !important; }
        .bg-primary { background-color: #4e73df !important; }
        .bg-info { background-color: #36b9cc !important; }
        .bg-danger { background-color: #e74a3b !important; }
        
        /* Ensure topbar is visible against the new background */
        .navbar-static-top {
            background: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05) !important;
            border-bottom: none !important;
        }

        .ibox.clickable {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .ibox.clickable:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
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

  <body>
    <div id="wrapper">
      <nav class="navbar-default navbar-static-side" role="navigation">
        <div class="sidebar-collapse">
          <ul class="nav metismenu" id="side-menu">
            <?php include 'sidebar.php'; ?>
          </ul>
        </div>
      </nav>

      <!-- topbar.php -->
       <?php include 'topbar.php'; ?>
       
        <div class="wrapper wrapper-content">

          <div class="page-callout">
              <div>
                  <p class="callout-title">Dashboard Overview</p>
                  <p class="callout-subtitle">Monitor compliance, attendee counts, and date-specific summaries in one place.</p>
              </div>
              <span class="callout-icon"><i class="fa fa-line-chart"></i></span>
          </div>
        
          <?php if(isset($dbError)): ?>
          <div class="alert alert-danger shadow-sm border-0 rounded-3" role="alert">
              <i class="fa fa-exclamation-triangle"></i> Database Connection Error: <?php echo htmlspecialchars($dbError); ?>
          </div>
          <?php endif; ?>

          <!-- ================= NEW AREA FILTER ROW ================= -->
          <div class="row mb-3">
              <div class="col-lg-12 d-flex justify-content-end">
                  <form method="GET" action="dashboard.php" class="d-flex align-items-center bg-white px-3 py-2 rounded shadow-sm" style="border-radius: 20px !important;">
                      <i class="fa fa-filter text-muted mr-2 me-2"></i>
                      <label for="areaFilter" class="mr-2 me-2 mb-0 font-weight-bold text-dark" style="white-space: nowrap;">Filter by Area:</label>
                      <select name="area" id="areaFilter" class="form-control border-0" style="outline: none; box-shadow: none; cursor: pointer; min-width: 180px; background-color: #f8f9fc; border-radius: 10px;" onchange="this.form.submit()">
                          <option value="">All Areas</option>
                          <?php foreach($allAreasList as $areaOption): ?>
                              <option value="<?php echo htmlspecialchars($areaOption); ?>" <?php echo $selectedArea === $areaOption ? 'selected' : ''; ?>>
                                  <?php echo htmlspecialchars($areaOption); ?>
                              </option>
                          <?php endforeach; ?>
                      </select>
                  </form>
              </div>
          </div>
          <!-- ================= END FILTER ================= -->

          <div class="row">
            <div class="col-lg-4">
                <div class="ibox clickable" onclick="window.location.href='unlock_dates.php'" title="Manage Unlock Dates">
                    <div class="ibox-title">
                        <span class="badge bg-success float-end float-right">Event</span>
                        <h5>Flag Raising Ceremony</h5>
                    </div>
                    <div class="ibox-content">
                        <h1 class="no-margins" id="latestDateHeading"><?php echo date('M d, Y', strtotime($targetDate)); ?></h1>
                        <div class="stat-percent font-bold text-success"><i class="fa fa-flag"></i></div>
                        <small>Latest Flag Ceremony</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="ibox clickable" id="compliantCard" data-status="1">
                    <div class="ibox-title">
                        <span class="badge bg-primary float-end float-right">Compliant</span>
                        <h5>Total</h5>
                    </div>
                    <div class="ibox-content">
                        <h1 class="no-margins"><?php echo $compliantCount; ?></h1>
                        <div class="stat-percent font-bold text-primary"><i class="fa fa-check-circle"></i></div>
                        <small>Compliant Attendees</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="ibox clickable" id="nonCompliantCard" data-status="0">
                    <div class="ibox-title">
                        <span class="badge bg-danger float-end float-right">Non-Compliant</span>
                        <h5>Total</h5>
                    </div>
                    <div class="ibox-content">
                        <h1 class="no-margins"><?php echo $nonCompliantCount; ?></h1>
                        <div class="stat-percent font-bold text-danger"><i class="fa fa-times-circle"></i></div>
                        <small>Non-Compliant Attendees</small>
                    </div>
                </div>
            </div>
          </div>

          <div class="row">
            <!-- Compliant Employees by Area Chart -->
            <div class="col-lg-6">
                <div class="ibox ">
                    <div class="ibox-title">
                        <h5><?php echo $chartTitle; ?></h5>
                    </div>
                    <div class="ibox-content">
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="departmentBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employees by Status Pie Chart -->
            <div class="col-lg-6">
                <div class="ibox ">
                    <div class="ibox-title">
                        <h5><?php echo $statusTitle; ?></h5>
                    </div>
                    <div class="ibox-content">
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="statusPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
          </div>

      <div id="right-sidebar">
        </div>
    </div>

    <div class="modal fade" id="complianceModal" tabindex="-1" role="dialog" aria-labelledby="complianceModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="complianceModalLabel"></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
              <table class="table table-bordered table-striped mb-0">
                <thead>
                  <tr>
                    <th>Full Name</th>
                    <th>Designation</th>
                    <th>Division</th>
                    <th>Unit</th>
                    <th>Area of Assignment</th>
                    <th>Most Recent Time</th>
                  </tr>
                </thead>
                <tbody id="compliance_list_body">
                  <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                      <i class="fa fa-info-circle fa-2x mb-2 d-block"></i>
                      <em>Click the Compliant or Non-Compliant card to see the employee list.</em>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <script src="js/jquery-3.1.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.js"></script>
    <script src="js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="js/plugins/slimscroll/jquery.slimscroll.min.js"></script>

    <script src="js/inspinia.js"></script>
    <script src="js/plugins/pace/pace.min.js"></script>

    <script src="js/plugins/chartJs/Chart.min.js"></script>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
      $(document).ready(function () {
          
        // 1. Horizontal Bar Chart Configuration
        var chartLabels = <?php echo $chartLabelsJson; ?>;
        var chartData = <?php echo $chartDataJson; ?>;
        var chartLabel = <?php echo $chartLabelJson; ?>;
        
        var barData = {
            labels: chartLabels,
            datasets: [{
                label: chartLabel,
                backgroundColor: "rgba(28, 200, 138, 0.6)", // Match #1cc88a
                borderColor: "#1cc88a",
                borderWidth: 2,
                maxBarThickness: 80,
                data: chartData
            }]
        };

        var barOptions = {
            responsive: true,
            maintainAspectRatio: false,
            legend: { display: false },
            scales: {
                xAxes: [{ 
                    ticks: { 
                        beginAtZero: true,
                        stepSize: 1 // Forces the axis to use whole numbers
                    } 
                }],
                yAxes: [{}]
            }
        };

        var ctxBar = document.getElementById("departmentBarChart").getContext("2d");
        window.departmentBarChart = new Chart(ctxBar, { type: "horizontalBar", data: barData, options: barOptions });

        // 2. Pie Chart Configuration (Employees by Status)
        var statusLabels = <?php echo $statusLabels; ?>;
        var statusData = <?php echo $statusCounts; ?>;
        
        var pieData = {
            labels: statusLabels,
            datasets: [{
                data: statusData,
                backgroundColor: ["#1cc88a", "#e74a3b", "#36b9cc", "#f6c23e"],
                hoverBackgroundColor: ["#17a673", "#e02d1b", "#2c9faf", "#f4b619"]
            }]
        };

        var pieOptions = {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'bottom'
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        var dataset = data.datasets[tooltipItem.datasetIndex];
                        var currentValue = dataset.data[tooltipItem.index];
                        // Calculate total for percentage
                        var total = 0;
                        for (var i = 0; i < dataset.data.length; i++) {
                            total += parseFloat(dataset.data[i]);
                        }
                        var percentage = total > 0 ? ((currentValue / total) * 100).toFixed(2) + '%' : '0.00%';
                        return data.labels[tooltipItem.index] + ': ' + currentValue + ' (' + percentage + ')';
                    }
                }
            }
        };

        var ctxPie = document.getElementById("statusPieChart").getContext("2d");
        new Chart(ctxPie, { type: "pie", data: pieData, options: pieOptions });
        
        // 3. Clickable Compliance Cards (Modal Fetch)
        $('.ibox.clickable[data-status]').on('click', function () {
            var status = $(this).data('status');
            var selectedArea = $('#areaFilter').val() || ''; 
            var currentDateStr = $('#latestDateHeading').text(); // Grab updated date string
            
            var isCompliant = status === 1 || status === '1';
            var modalTitle = isCompliant ? ('Compliant Attendees - ' + currentDateStr) : ('Non-Compliant Attendees - ' + currentDateStr);
            
            // Append Area to title if filtered
            if(selectedArea !== '') {
                modalTitle += ' (' + selectedArea + ')';
            }

            var loadingRow = '<tr><td colspan="6" class="text-center text-muted py-4"><i class="fa fa-spinner fa-spin fa-2x mb-2 d-block"></i><em>Loading list...</em></td></tr>';

            $('#complianceModalLabel').text(modalTitle);
            $('#compliance_list_body').html(loadingRow);
            $('#complianceModal').modal('show');

            $.ajax({
                url: 'dashboard.php',
                method: 'POST',
                data: {
                    action: 'fetch_compliance_list',
                    status: status,
                    area: selectedArea 
                },
                success: function(response) {
                    $('#compliance_list_body').html(response);
                },
                error: function() {
                    $('#compliance_list_body').html('<tr><td colspan="6" class="text-center text-danger py-4"><i class="fa fa-exclamation-triangle fa-2x mb-2 d-block"></i><em>Unable to load employees. Please refresh and try again.</em></td></tr>');
                }
            });
        });

        // 4. SweetAlert Logout Confirmation
        $('#logout-btn').on('click', function(e) {
            e.preventDefault(); 
            Swal.fire({
                title: 'Are you sure?',
                text: "You will be logged out of your current session.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#1cc88a',
                cancelButtonColor: '#e74a3b',
                confirmButtonText: 'Yes, log out!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php'; 
                }
            });
        });

        // 5. Auto-refresh dashboard dynamically when new attendance is submitted
        var totalAttendanceToday = <?php echo isset($totalAttendanceToday) ? $totalAttendanceToday : 0; ?>;
        var currentTargetDate = "<?php echo isset($targetDate) ? $targetDate : ''; ?>";
        
        setInterval(function() {
            var selectedArea = $('#areaFilter').val() || '';
            $.ajax({
                url: 'dashboard.php',
                method: 'POST',
                data: { 
                    action: 'check_new_attendance',
                    current_count: totalAttendanceToday,
                    current_date: currentTargetDate,
                    area: selectedArea
                },
                dataType: 'json',
                success: function(response) {
                    if (response.changed) {
                        // Update tracking variables
                        totalAttendanceToday = response.count;
                        currentTargetDate = response.target_date;
                        
                        // Update Date Heading and UI Components dynamically
                        $('#latestDateHeading').text(response.latestDateFormatted);
                        $('#compliantCard h1').text(response.compliantCount);
                        $('#nonCompliantCard h1').text(response.nonCompliantCount);

                        // Update Bar Chart seamlessly without reloading the page
                        window.departmentBarChart.data.labels = response.chartLabels;
                        window.departmentBarChart.data.datasets[0].data = response.chartData;
                        window.departmentBarChart.update();
                        
                        // Display sync toast
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Dashboard synced with new attendance!'
                        });
                    }
                }
            });
        }, 3000); // Check every 3 seconds for seamless updates

      });
    </script>
  </body>
</html>