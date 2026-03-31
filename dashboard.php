
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

try {
    // 1. Get Total Employees
    $stmt = $pdo->query("SELECT COUNT(emp_id) FROM employees");
    $totalEmployees = $stmt->fetchColumn();

    // 2. Get Employees per Department
    $stmt = $pdo->query("SELECT department, COUNT(emp_id) as count FROM employees GROUP BY department");
    $departmentsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get Employees by Gender
    $stmt = $pdo->query("SELECT gender, COUNT(emp_id) as count FROM employees GROUP BY gender");
    $genderData = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    $genderData = [];
    $statusData = [];
    $areaData = [];
    $compliantCount = 0;
    $nonCompliantCount = 0;
    $dbError = $e->getMessage();
}

// Convert arrays to JSON so we can use them in JavaScript for Chart.js
$deptLabels = json_encode(array_column($departmentsData, 'department'));
$deptCounts = json_encode(array_column($departmentsData, 'count'));

$genderLabels = json_encode(array_column($genderData, 'gender'));
$genderCounts = json_encode(array_column($genderData, 'count'));

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
                <div class="ibox ">
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
                <div class="ibox ">
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
                        <h5>Employees by Department</h5>
                    </div>
                    <div class="ibox-content">
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="departmentBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="ibox ">
                    <div class="ibox-title">
                        <h5>Gender Distribution</h5>
                    </div>
                    <div class="ibox-content">
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="genderDoughnutChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
          </div>

          <div class="row">
            <!-- Area of Assignment Chart -->
            <div class="col-lg-6">
                <div class="ibox ">
                    <div class="ibox-title">
                        <h5>Area of Assignment</h5>
                    </div>
                    <div class="ibox-content">
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="areaBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
          
            <!-- Employment Status Pie Chart -->
            <div class="col-lg-6">
                <div class="ibox ">
                    <div class="ibox-title">
                        <h5>Employment Status</h5>
                    </div>
                    <div class="ibox-content">
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="statusPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
          </div>

        </div>
        
      </div>
      
      <div id="right-sidebar">
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
        var deptLabels = <?php echo $deptLabels ?: '[]'; ?>;
        var deptCounts = <?php echo $deptCounts ?: '[]'; ?>;
        
        var barData = {
            labels: deptLabels.length > 0 ? deptLabels : ["No Data"],
            datasets: [{
                label: "Number of Employees",
                backgroundColor: "rgba(28, 200, 138, 0.6)", // Match #1cc88a
                borderColor: "#1cc88a",
                borderWidth: 2,
                maxBarThickness: 80,
                data: deptCounts.length > 0 ? deptCounts : [0]
            }]
        };

        var barOptions = {
            responsive: true,
            maintainAspectRatio: false,
            legend: { display: false },
            scales: {
                yAxes: [{ ticks: { beginAtZero: true } }]
            }
        };

        var ctxBar = document.getElementById("departmentBarChart").getContext("2d");
        new Chart(ctxBar, { type: "bar", data: barData, options: barOptions });

        // 2. Gender Doughnut Chart Configuration (Mixed Theme Colors)
        var genderLabels = <?php echo $genderLabels ?: '[]'; ?>;
        var genderCounts = <?php echo $genderCounts ?: '[]'; ?>;
        
        var doughnutData = {
            labels: genderLabels.length > 0 ? genderLabels : ["No Data"],
            datasets: [{
                data: genderCounts.length > 0 ? genderCounts : [0],
                backgroundColor: ["#1cc88a", "#4e73df", "#36b9cc", "#e74a3b"],
                borderWidth: 0
            }]
        };
        var doughnutOptions = { 
            responsive: true,
            maintainAspectRatio: false,
            cutoutPercentage: 70
        };
        var ctxDoughnut = document.getElementById("genderDoughnutChart").getContext("2d");
        new Chart(ctxDoughnut, { type: "doughnut", data: doughnutData, options: doughnutOptions });

        // 3. Area of Assignment Bar Chart
        var areaLabels = <?php echo $areaLabels ?: '[]'; ?>;
        var areaCounts = <?php echo $areaCounts ?: '[]'; ?>;
        
        var areaBarData = {
            labels: areaLabels.length > 0 ? areaLabels : ["No Data"],
            datasets: [{
                label: "Number of Employees",
                backgroundColor: "rgba(78, 115, 223, 0.6)", // Match #4e73df (blue theme)
                borderColor: "#4e73df",
                borderWidth: 2,
                maxBarThickness: 60,
                data: areaCounts.length > 0 ? areaCounts : [0]
            }]
        };

        var areaBarOptions = {
            responsive: true,
            maintainAspectRatio: false,
            legend: { display: false },
            scales: {
                yAxes: [{ ticks: { beginAtZero: true } }]
            }
        };

        var ctxArea = document.getElementById("areaBarChart").getContext("2d");
        new Chart(ctxArea, { type: "bar", data: areaBarData, options: areaBarOptions });

        // 4. Status Pie Chart Configuration (Mixed Theme Colors)
        var statusLabels = <?php echo $statusLabels ?: '[]'; ?>;
        var statusCounts = <?php echo $statusCounts ?: '[]'; ?>;

        var pieData = {
            labels: statusLabels.length > 0 ? statusLabels : ["No Data"],
            datasets: [{
                data: statusCounts.length > 0 ? statusCounts : [0],
                backgroundColor: ["#f6c23e", "#4e73df", "#1cc88a", "#e74a3b"],
                borderWidth: 0
            }]
        };
        var pieOptions = { 
            responsive: true,
            maintainAspectRatio: false 
        };
        var ctxPie = document.getElementById("statusPieChart").getContext("2d");
        new Chart(ctxPie, { type: "pie", data: pieData, options: pieOptions });
        
        // 5. SweetAlert Logout Confirmation
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