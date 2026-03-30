<?php 
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

include 'connect.php'; 
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>DICT Monday Flag Raising | Data Table</title>

    <link href="css/bootstrap.min.css" rel="stylesheet" />
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <link href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.2.7/css/responsive.bootstrap4.min.css" rel="stylesheet">

    <link href="css/animate.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />
    
    <style>
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }
        .ibox-title {
            background-color: #ffffff;
            border-color: #e7eaec;
            border-image: none;
            border-style: solid solid none;
            border-width: 2px 0 0;
            color: inherit;
            margin-bottom: 0;
            padding: 15px 15px 7px;
            min-height: 48px;
        }
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
        
        /* Custom Filter Styling */
        .filter-container {
            background: rgba(255,255,255,0.5);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #e7eaec;
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

      <!-- topbar.php - this file opens <div id="page-wrapper" class="gray-bg"> -->
      <?php include 'topbar.php'; ?>

        <div class="wrapper wrapper-content animated fadeInRight">

            <div class="row">
                <div class="col-lg-12">
                <div class="ibox ">
                    <div class="ibox-title">
                        <h5>Registered Employees Database</h5>
                    </div>
                    <div class="ibox-content">

                        <!-- DYNAMIC DATABASE FILTERS SECTION -->
                        <div class="filter-container">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="font-weight-bold text-primary"><i class="fa fa-building"></i> Department</label>
                                    <select id="filter_dept" class="form-control">
                                        <option value="">All Departments</option>
                                        <?php
                                        if (isset($pdo)) {
                                            $dept_query = $pdo->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department");
                                            while ($row = $dept_query->fetchColumn()) {
                                                echo "<option value='" . htmlspecialchars($row) . "'>" . htmlspecialchars($row) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="font-weight-bold text-primary"><i class="fa fa-map-marker"></i> Area of Assignment</label>
                                    <select id="filter_area" class="form-control">
                                        <option value="">All Areas</option>
                                        <option value="Regional Office">Regional Office</option>
                                        <option value="Zamboanga City">Zamboanga City</option>
                                        <option value="Zamboanga Del Sur">Zamboanga Del Sur</option>
                                        <option value="Zamboanga Del Norte">Zamboanga Del Norte</option>
                                        <option value="Basilan">Basilan</option>
                                        <option value="Tawi-Tawi">Tawi-Tawi</option>
                                        <option value="Sulu">Sulu</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="font-weight-bold text-primary"><i class="fa fa-id-badge"></i> Status</label>
                                    <select id="filter_status" class="form-control">
                                        <option value="">All Statuses</option>
                                        <?php
                                        if (isset($pdo)) {
                                            $status_query = $pdo->query("SELECT DISTINCT status FROM employees WHERE status IS NOT NULL AND status != '' ORDER BY status");
                                            while ($row = $status_query->fetchColumn()) {
                                                echo "<option value='" . htmlspecialchars($row) . "'>" . htmlspecialchars($row) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <!-- END FILTERS SECTION -->

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover dataTables-employees" >
                            <thead>
                            <tr>
                                <th>Emp ID</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Unit</th>
                                <th>Area of Assignment</th>
                                <th>Designation</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            if (isset($pdo)) {
                                try {
                                    $stmt = $pdo->query("SELECT emp_id, full, emp_email, department, unit, area_of_assignment, designation, age, gender, status FROM employees ORDER BY emp_id DESC");
                                    while ($row = $stmt->fetch()) {
                                        // Updated Badge Colors for Plantilla & Job Order
                                        $statusClass = 'badge-secondary'; // Default for old/missing data
                                        $statusText = strtolower($row['status'] ?? '');
                                        
                                        if ($statusText === 'plantilla') {
                                            $statusClass = 'badge-success'; 
                                        } elseif ($statusText === 'job order') {
                                            $statusClass = 'badge-warning';
                                        }

                                        echo "<tr class='gradeX'>";
                                        echo "<td>" . htmlspecialchars($row['emp_id']) . "</td>";
                                        echo "<td>" . ucwords(htmlspecialchars($row['full'] ?? '')) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['department'] ?? '') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['unit'] ?? '') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['area_of_assignment'] ?? '') . "</td>";
                                        echo "<td>" . htmlspecialchars($row['designation'] ?? '') . "</td>";
                                        echo "<td class='text-center'><span class='badge {$statusClass}'>" . htmlspecialchars($row['status'] ?? 'N/A') . "</span></td>";
                                        
                                        echo "<td class='text-center'>
                                                <div class='btn-group'>
                                                    
                                                    <!-- VIEW ATTENDANCE BUTTON -->
                                                    <button class='btn btn-success btn-sm btn-attendance' title='View Attendance'
                                                        data-toggle='modal' 
                                                        data-target='#attendanceModal'
                                                        data-id='" . htmlspecialchars($row['emp_id']) . "'
                                                        data-full='" . htmlspecialchars($row['full'] ?? '') . "'>
                                                        <i class='fa fa-calendar'></i>
                                                    </button>

                                                    <button class='btn btn-info btn-sm btn-view' title='View Profile'
                                                        data-toggle='modal' 
                                                        data-target='#viewModal'
                                                        data-id='" . htmlspecialchars($row['emp_id']) . "'
                                                        data-full='" . htmlspecialchars($row['full'] ?? '') . "'
                                                        data-email='" . htmlspecialchars($row['emp_email'] ?? '') . "'
                                                        data-dept='" . htmlspecialchars($row['department'] ?? '') . "'
                                                        data-unit='" . htmlspecialchars($row['unit'] ?? '') . "'
                                                        data-area='" . htmlspecialchars($row['area_of_assignment'] ?? '') . "'
                                                        data-age='" . htmlspecialchars($row['age'] ?? '') . "'
                                                        data-gender='" . htmlspecialchars($row['gender'] ?? '') . "'
                                                        data-designation='" . htmlspecialchars($row['designation'] ?? '') . "'
                                                        data-status='" . htmlspecialchars($row['status'] ?? 'Plantilla') . "'>
                                                        <i class='fa fa-eye'></i>
                                                    </button>
                                                </div>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } catch (\PDOException $e) {
                                    echo "<tr><td colspan='8' class='text-danger'>Error fetching data: " . $e->getMessage() . "</td></tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='text-danger text-center'><strong>Database connection failed:</strong> " . ($db_error ?? 'Check credentials') . "</td></tr>";
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

        
      </div>
    </div>

    <!-- INCLUDED MODALS -->
    <?php include 'view_attendance.php'; ?>
    <?php include 'view_employee.php'; ?>

    
    <script src="js/jquery-3.1.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.js"></script>
    <script src="js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="js/plugins/slimscroll/jquery.slimscroll.min.js"></script>

    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/responsive.bootstrap4.min.js"></script>

    <!-- SweetAlert2 CDN added for Logout Confirmation -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="js/inspinia.js"></script>
    <script src="js/plugins/pace/pace.min.js"></script>

    <script>
        $(document).ready(function(){
            // Initialize DataTable
            var table = $('.dataTables-employees').DataTable({
                pageLength: 25,
                responsive: true,
                dom: '<"html5buttons"B>lTfgitp', 
                buttons: [], 
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search employees..."
                }
            });

            // TRIGGER DATATABLES FILTERING ON DROPDOWN CHANGE
            // Using exact matching so "Area 1" doesn't match "Area 10"
            $('#filter_dept').on('change', function () {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                table.column(2).search(val ? '^' + val + '$' : '', true, false).draw();
            });
            
            $('#filter_area').on('change', function () {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                table.column(4).search(val ? '^' + val + '$' : '', true, false).draw();
            });
            
            $('#filter_status').on('change', function () {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                table.column(6).search(val ? '^' + val + '$' : '', true, false).draw();
            });

            // POPULATE ATTENDANCE MODAL DATA & TRIGGER AJAX
            $('.dataTables-employees tbody').on('click', '.btn-attendance', function () {
                var btn = $(this);
                var fullName = btn.data('full');
                var empId = btn.data('id');
                var formattedName = fullName.replace(/\b\w/g, function(l){ return l.toUpperCase() });

                $('#attendance_full_name').text(formattedName);
                
              
                // Show loading spinner in the table body (Updated colspan to 7)
                $('#attendance_records_body').html('<tr><td colspan="7" class="text-center text-muted py-4"><i class="fa fa-spinner fa-spin fa-2x mb-2 d-block"></i><em>Loading records...</em></td></tr>');

                // AJAX Call routing to our new extracted view_attendance.php file
                $.ajax({
                    url: 'view_attendance.php',
                    type: 'POST',
                    data: { 
                        action: 'fetch_attendance',
                        emp_id: empId 
                    },
                    success: function(response) {
                        $('#attendance_records_body').html(response);
                    },
                    error: function() {
                        // Error message (Updated colspan to 7)
                        $('#attendance_records_body').html('<tr><td colspan="7" class="text-center text-danger py-4"><i class="fa fa-exclamation-triangle fa-2x mb-2 d-block"></i><em>Failed to retrieve data. Check your connection.</em></td></tr>');
                    }
                });
            });
            
            // POPULATE VIEW MODAL DATA
            $('.dataTables-employees tbody').on('click', '.btn-view', function () {
                var btn = $(this);
                var fullName = btn.data('full');
                var formattedName = fullName.replace(/\b\w/g, function(l){ return l.toUpperCase() });

                $('#view_full_name').text(formattedName);
                $('#view_designation_dept').text(btn.data('designation') + '  |  ' + btn.data('dept'));
                
                $('#view_emp_id').text(btn.data('id'));
                $('#view_full').text(formattedName);
                $('#view_email').text(btn.data('email') ? btn.data('email') : 'N/A');
                
                $('#view_age').text(btn.data('age') ? btn.data('age') : 'N/A');
                $('#view_gender').text(btn.data('gender') ? btn.data('gender') : 'N/A');
                
                $('#view_unit').text(btn.data('unit') ? btn.data('unit') : 'N/A');
                $('#view_area').text(btn.data('area') ? btn.data('area') : 'N/A');
                
                // Updated Badge Color Logic for JS View rendering
                var status = btn.data('status');
                var statusText = status.toLowerCase();
                var statusClass = 'badge-secondary';
                
                if (statusText === 'plantilla') {
                    statusClass = 'badge-success';
                } else if (statusText === 'job order') {
                    statusClass = 'badge-warning';
                } 
                
                $('#view_status_badge').removeClass().addClass('badge ' + statusClass).text(status);
            });

            // $('.navbar-minimalize').on('click', function (event) {
            //     event.preventDefault();
            //     $("body").toggleClass("mini-navbar");
            //     SmoothlyMenu();
            // });

            // SweetAlert Logout Confirmation matching dashboard.php
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
                        window.location.href = 'logout.php'; // Proceed to log out route
                    }
                });
            });
        });

    </script>
  </body>
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
            
            /* Ensure topbar is visible against the new background */
            .navbar-static-top {
                background: rgba(255, 255, 255, 0.95) !important;
                box-shadow: 0 2px 10px rgba(0,0,0,0.05) !important;
                border-bottom: none !important;
            }
            
            /* DataTables specific transparent matching */
            div.dataTables_wrapper div.dataTables_filter input,
            div.dataTables_wrapper div.dataTables_length select {
                border-radius: 8px;
                border: 1px solid #ddd;
                padding: 4px 8px;
            }
            
            /* Modal Styling */
            .modal-content {
                border-radius: 15px;
                border: none;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            }
            .modal-header {
                border-radius: 15px 15px 0 0;
            }
            
            /* Match Primary/Success button themes */
            .btn-primary { background-color: #4e73df !important; border-color: #4e73df !important; }
            .btn-success { background-color: #1cc88a !important; border-color: #1cc88a !important; }
            .btn-info { background-color: #36b9cc !important; border-color: #36b9cc !important; }
            .text-success { color: #1cc88a !important; }
            .text-primary { color: #4e73df !important; }
            .text-info { color: #36b9cc !important; }
            .bg-success { background-color: #1cc88a !important; }
            .bg-primary { background-color: #4e73df !important; }
            .bg-info { background-color: #36b9cc !important; }
        </style>
</html>