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
// ... rest of your code remains exactly the same
    die('Database connection failed: ' . ($db_error ?? 'Unknown error'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_compliance_list') {
    $status = isset($_POST['status']) && $_POST['status'] === '1' ? 1 : 0;
    $mondayDate = date('Y-m-d', strtotime(date('l') === 'Monday' ? 'today' : 'last Monday'));
    
    if (!$pdo) {
        echo "<tr><td colspan='5' class='text-center text-danger'>Database connection failed.</td></tr>";
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT 
                e.emp_id, 
                e.full, 
                MAX(a.time_recorded) AS time_recorded,
                SUBSTRING_INDEX(GROUP_CONCAT(a.status ORDER BY a.status DESC SEPARATOR ','), ',', 1) AS status
            FROM attendance_record a
            JOIN employees e ON a.emp_id = e.emp_id
            WHERE DATE(a.time_recorded) = :mondayDate
              AND a.is_compliant = :status
            GROUP BY e.emp_id, e.full
            ORDER BY time_recorded DESC");
        $stmt->execute(['status' => $status, 'mondayDate' => $mondayDate]);
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

try {
    // 1. Get Total Employees
    $stmt = $pdo->query("SELECT COUNT(emp_id) FROM employees");
    $totalEmployees = $stmt->fetchColumn();

    // 2. Get Employees per Department
    $stmt = $pdo->query("SELECT department, COUNT(emp_id) as count FROM employees GROUP BY department");
    $departmentsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get Employees by Gender
    $stmt = $pdo->query("SELECT gender, COUNT(emp_id) as count FROM employees GROUP BY gender");
    $sexData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Get Employees by Status
    $stmt = $pdo->query("SELECT status, COUNT(emp_id) as count FROM employees GROUP BY status");
    $statusData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Get Employees by Area of Assignment
    $stmt = $pdo->query("SELECT area_of_assignment, COUNT(emp_id) as count FROM employees GROUP BY area_of_assignment");
    $areaData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Get Compliant & Non-Compliant for This Week (Monday Only)
    // Find the date of the most recent Monday (or today if today is Monday)
    $mondayDate = date('Y-m-d', strtotime(date('l') === 'Monday' ? 'today' : 'last Monday'));

    // Compliant Employees (Monday only)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT emp_id) FROM attendance_record WHERE is_compliant = 1 AND DATE(time_recorded) = ?");
    $stmt->execute([$mondayDate]);
    $compliantCount = $stmt->fetchColumn();

    // Non-Compliant Employees (Monday only)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT emp_id) FROM attendance_record WHERE is_compliant = 0 AND DATE(time_recorded) = ?");
    $stmt->execute([$mondayDate]);
    $nonCompliantCount = $stmt->fetchColumn();

} catch (PDOException $e) {
    // If DB fails to connect, fallback to empty data to prevent page breaking
    $totalEmployees = 0;
    $departmentsData = [];
    $sexData = [];
    $statusData = [];
    $areaData = [];
    $compliantCount = 0;
    $nonCompliantCount = 0;
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

          <div class="row">
            <div class="col-lg-3">
                <div class="ibox ">
                    <div class="ibox-title">
                        <span class="badge bg-success float-end">Current</span>
                        <h5>Total Employees</h5>
                    </div>
                    <div class="ibox-content">
                        <h1 class="no-margins"><?php echo $totalEmployees; ?></h1>
                        <div class="stat-percent font-bold text-success">100% <i class="fa fa-users"></i></div>
                        <small>Total Active Profiles</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3">
                <div class="ibox ">
                    <div class="ibox-title">
                        <span class="badge bg-info float-end">Active</span>
                        <h5>Departments</h5>
                    </div>
                    <div class="ibox-content">
                        <h1 class="no-margins"><?php echo count($departmentsData); ?></h1>
                        <div class="stat-percent font-bold text-info"><i class="fa fa-building"></i></div>
                        <small>Different Departments</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3">
                <div class="ibox clickable" id="compliantCard" data-status="1">
                    <div class="ibox-title">
                        <span class="badge bg-primary float-end">This Monday</span>
                        <h5>Compliant</h5>
                    </div>
                    <div class="ibox-content">
                        <h1 class="no-margins"><?php echo $compliantCount; ?></h1>
                        <div class="stat-percent font-bold text-primary"><i class="fa fa-check-circle"></i></div>
                        <small>Compliant Attendees</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="ibox clickable" id="nonCompliantCard" data-status="0">
                    <div class="ibox-title">
                        <span class="badge bg-danger float-end">This Monday</span>
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
            <div class="col-lg-8">
                <div class="ibox ">
                    <div class="ibox-title">
                        <h5>Field Offices</h5>
                    </div>
                    <div class="ibox-content">
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="departmentBarChart"></canvas>
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
          
        // 1. Department Bar Chart Configuration (Match index green)
        var deptLabels = ["ZDN", "ZDS", "ZSP", "ZC", "BAS", "SUL", "TW"];
        var deptCounts = <?php echo $deptCounts ?: '[]'; ?>;
        
        var barData = {
            labels: deptLabels,
            datasets: [{
                label: "Number of Employees",
                backgroundColor: "rgba(28, 200, 138, 0.6)", // Match #1cc88a
                borderColor: "#1cc88a",
                borderWidth: 2,
                maxBarThickness: 80,
                data: deptCounts.length > 0 ? deptCounts : [0,0,0,0,0,0,0]
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


       
        
        // 5. Clickable Compliance Cards
        $('.ibox.clickable').on('click', function () {
            var status = $(this).data('status');
            var isCompliant = status === 1 || status === '1';
            var modalTitle = isCompliant ? 'Compliant Attendees This Monday' : 'Non-Compliant Attendees This Monday';
            var loadingRow = '<tr><td colspan="5" class="text-center text-muted py-4"><i class="fa fa-spinner fa-spin fa-2x mb-2 d-block"></i><em>Loading list...</em></td></tr>';

            $('#complianceModalLabel').text(modalTitle);
            $('#compliance_list_body').html(loadingRow);
            $('#complianceModal').modal('show');

            $.ajax({
                url: 'dashboard.php',
                method: 'POST',
                data: {
                    action: 'fetch_compliance_list',
                    status: status
                },
                success: function(response) {
                    $('#compliance_list_body').html(response);
                },
                error: function() {
                    $('#compliance_list_body').html('<tr><td colspan="5" class="text-center text-danger py-4"><i class="fa fa-exclamation-triangle fa-2x mb-2 d-block"></i><em>Unable to load employees. Please refresh and try again.</em></td></tr>');
                }
            });
        });

        // 6. SweetAlert Logout Confirmation
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