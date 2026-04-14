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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_compliance_list') {
    $status = isset($_POST['status']) && $_POST['status'] === '1' ? 1 : 0;
    $area = isset($_POST['area']) ? $_POST['area'] : ''; // Fetch area filter from AJAX
    $mondayDate = date('Y-m-d', strtotime(date('l') === 'Monday' ? 'today' : 'last Monday'));
    
    if (!$pdo) {
        echo "<tr><td colspan='5' class='text-center text-danger'>Database connection failed.</td></tr>";
        exit;
    }
    
    try {
        $query = "SELECT 
                e.emp_id, 
                e.full, 
                MAX(a.time_recorded) AS time_recorded,
                SUBSTRING_INDEX(GROUP_CONCAT(a.status ORDER BY a.status DESC SEPARATOR ','), ',', 1) AS status
            FROM attendance_record a
            JOIN employees e ON a.emp_id = e.emp_id
            WHERE DATE(a.time_recorded) = :mondayDate
              AND a.is_compliant = :status";
              
        $params = ['status' => $status, 'mondayDate' => $mondayDate];
        
        // Apply area filter if selected
        if ($area !== '') {
            $query .= " AND e.area_of_assignment = :area";
            $params['area'] = $area;
        }

        $query .= " GROUP BY e.emp_id, e.full ORDER BY time_recorded DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($rows) {
            foreach ($rows as $row) {
                $fullName = htmlspecialchars(ucwords($row['full'] ?? 'N/A'));
                $statusText = htmlspecialchars($row['status'] ?? 'N/A');
                $formattedTime = !empty($row['time_recorded']) ? htmlspecialchars(date('M d, Y - h:i A', strtotime($row['time_recorded']))) : 'N/A';

                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['emp_id']) . "</td>";
                echo "<td>" . $fullName . "</td>";
                echo "<td>" . $formattedTime . "</td>";
                echo "<td class='text-center'>" . $statusText . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4' class='text-center text-muted py-4'><i class='fa fa-folder-open-o fa-2x mb-2 d-block'></i><em>No employees found for this category this Monday.</em></td></tr>";
        }
    } catch (PDOException $e) {
        echo "<tr><td colspan='5' class='text-center text-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
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
    $statusQuery = "SELECT status, COUNT(emp_id) as count FROM employees GROUP BY status";
    $stmt = $pdo->prepare($statusQuery);
    $stmt->execute();
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

    // 6. Get Compliant & Non-Compliant for This Week (Monday Only)
    // Find the date of the most recent Monday (or today if today is Monday)
    $mondayDate = date('Y-m-d', strtotime(date('l') === 'Monday' ? 'today' : 'last Monday'));

    // Compliant Employees (Monday only, Filtered by Area if applicable)
    $compliantQuery = "SELECT COUNT(DISTINCT a.emp_id) FROM attendance_record a JOIN employees e ON a.emp_id = e.emp_id WHERE a.is_compliant = 1 AND DATE(a.time_recorded) = :date";
    $compParams = ['date' => $mondayDate];
    if ($selectedArea) {
        $compliantQuery .= " AND e.area_of_assignment = :area";
        $compParams['area'] = $selectedArea;
    }
    $stmt = $pdo->prepare($compliantQuery);
    $stmt->execute($compParams);
    $compliantCount = $stmt->fetchColumn();

    // Non-Compliant Employees (Monday only, Filtered by Area if applicable)
    $nonCompliantQuery = "SELECT COUNT(DISTINCT a.emp_id) FROM attendance_record a JOIN employees e ON a.emp_id = e.emp_id WHERE a.is_compliant = 0 AND DATE(a.time_recorded) = :date";
    $nonCompParams = ['date' => $mondayDate];
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
        $stmt->execute([$mondayDate, $area['area_of_assignment']]);
        $chartData[] = $stmt->fetchColumn();
    }
    $chartLabel = 'Compliant Employees';
    $chartTitle = 'Compliant Employees by Area' . ($selectedArea ? ' (' . htmlspecialchars($selectedArea) . ')' : '');

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
        
          <?php if(isset($dbError)): ?>
          <div class="alert alert-danger shadow-sm border-0 rounded-3" role="alert">
              <i class="fa fa-exclamation-triangle"></i> Database Connection Error: <?php echo htmlspecialchars($dbError); ?>
          </div>
          <?php endif; ?>

          <!-- ================= NEW AREA FILTER ROW (Yellow Line Area) ================= -->
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
                <div class="ibox clickable" onclick="window.location.href='data_table.php'" title="View Data Table">
                    <div class="ibox-title">
                        <span class="badge bg-success float-end float-right">Event</span>
                        <h5>Flag Raising Ceremony</h5>
                    </div>
                    <div class="ibox-content">
                        <h1 class="no-margins"><?php echo date('M d, Y', strtotime($mondayDate)); ?></h1>
                        <div class="stat-percent font-bold text-success"><i class="fa fa-flag"></i></div>
                        <small>Recent/Upcoming Monday</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="ibox clickable" id="compliantCard" data-status="1">
                    <div class="ibox-title">
                        <span class="badge bg-primary float-end float-right">This Monday</span>
                        <h5>Compliant</h5>
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
                        <span class="badge bg-danger float-end float-right">This Monday</span>
                        <h5>Non-Compliant</h5>
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

            <!-- Compliant vs Non-Compliant Pie Chart -->
            <div class="col-lg-6">
                <div class="ibox ">
                    <div class="ibox-title">
                        <h5>Overall Compliance Breakdown</h5>
                    </div>
                    <div class="ibox-content">
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="compliancePieChart"></canvas>
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
                    <th>Employee ID</th>
                    <th>Full Name</th>
                    <th>Most Recent Time</th>
                    <th class="text-center">Status</th>
                  </tr>
                </thead>
                <tbody id="compliance_list_body">
                  <tr>
                    <td colspan="4" class="text-center text-muted py-4">
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
                xAxes: [{ ticks: { beginAtZero: true } }],
                yAxes: [{}]
            }
        };

        var ctxBar = document.getElementById("departmentBarChart").getContext("2d");
        new Chart(ctxBar, { type: "horizontalBar", data: barData, options: barOptions });

        // 2. Pie Chart Configuration (Compliant vs Non-Compliant)
        var pieData = {
            labels: ["Compliant", "Non-Compliant"],
            datasets: [{
                data: <?php echo $pieDataJson; ?>,
                backgroundColor: ["#1cc88a", "#e74a3b"],
                hoverBackgroundColor: ["#17a673", "#e02d1b"]
            }]
        };

        var pieOptions = {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'bottom'
            }
        };

        var ctxPie = document.getElementById("compliancePieChart").getContext("2d");
        new Chart(ctxPie, { type: "pie", data: pieData, options: pieOptions });
        
        // 3. Clickable Compliance Cards (Modal Fetch)
        $('.ibox.clickable[data-status]').on('click', function () {
            var status = $(this).data('status');
            var selectedArea = $('#areaFilter').val() || ''; // Grab selected area for the modal query
            
            var isCompliant = status === 1 || status === '1';
            var modalTitle = isCompliant ? 'Compliant Attendees This Monday' : 'Non-Compliant Attendees This Monday';
            
            // Append Area to title if filtered
            if(selectedArea !== '') {
                modalTitle += ' (' + selectedArea + ')';
            }

            var loadingRow = '<tr><td colspan="5" class="text-center text-muted py-4"><i class="fa fa-spinner fa-spin fa-2x mb-2 d-block"></i><em>Loading list...</em></td></tr>';

            $('#complianceModalLabel').text(modalTitle);
            $('#compliance_list_body').html(loadingRow);
            $('#complianceModal').modal('show');

            $.ajax({
                url: 'dashboard.php',
                method: 'POST',
                data: {
                    action: 'fetch_compliance_list',
                    status: status,
                    area: selectedArea // Send the selected area to filter the modal list
                },
                success: function(response) {
                    $('#compliance_list_body').html(response);
                },
                error: function() {
                    $('#compliance_list_body').html('<tr><td colspan="5" class="text-center text-danger py-4"><i class="fa fa-exclamation-triangle fa-2x mb-2 d-block"></i><em>Unable to load employees. Please refresh and try again.</em></td></tr>');
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

      });
    </script>
  </body>
</html>