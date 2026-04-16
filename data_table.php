<?php 
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

include 'connect.php'; 

// Fetch unlocked dates for the calendar filter
$unlocked_dates_array = [];
try {
    $stmt_unlocked = $pdo->query("SELECT target_date FROM unlocked_dates");
    if ($stmt_unlocked) {
        while ($row = $stmt_unlocked->fetch(PDO::FETCH_ASSOC)) {
            $unlocked_dates_array[] = $row['target_date'];
        }
    }
} catch (\PDOException $e) {
    // If the table doesn't exist yet or there's an error, the array remains empty
}
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>DICT Monday Flag Raising | Flag Ceremonies</title>

    <link href="css/bootstrap.min.css" rel="stylesheet" />
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <link href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.2.7/css/responsive.bootstrap4.min.css" rel="stylesheet">
    
    <!-- Flatpickr for Calendar Filter -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <link href="css/animate.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />
    
    <style>
        .btn-info { background-color: #17a2b8; border-color: #17a2b8; }
        .ibox-title {
            background-color: #ffffff; border-color: #e7eaec;
            border-style: solid solid none; border-width: 2px 0 0;
            margin-bottom: 0; padding: 15px 15px 7px; min-height: 48px;
        }
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
        .ibox {
            border-radius: 15px !important; box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
            background: rgba(255, 255, 255, 0.95) !important; border: none !important; margin-bottom: 25px;
        }
        .ibox-title { border-radius: 15px 15px 0 0 !important; background: transparent !important; border-bottom: 1px solid rgba(0,0,0,0.05) !important; }
        .ibox-content { background: transparent !important; border-radius: 0 0 15px 15px !important; }
        .navbar-static-top { background: rgba(255, 255, 255, 0.95) !important; box-shadow: 0 2px 10px rgba(0,0,0,0.05) !important; border-bottom: none !important; }
        div.dataTables_wrapper div.dataTables_filter input, div.dataTables_wrapper div.dataTables_length select { border-radius: 8px; border: 1px solid #ddd; padding: 4px 8px; }
        .modal-content { border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .modal-header { border-radius: 15px 15px 0 0; }
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

      <?php include 'topbar.php'; ?>

        <div class="wrapper wrapper-content animated fadeInRight">
            <div class="row">
                <div class="col-lg-12">
                <div class="ibox ">
                    <div class="ibox-title">
                        <h5>Flag Ceremony Attendance Dates</h5>
                    </div>
                    <div class="ibox-content">

                        <!-- Custom Filter Template (Hidden initially, moved by JS) -->
                        <div id="custom-date-filter" style="display: none;">
                            <div class="d-flex align-items-center justify-content-md-end">
                                <label for="mondaySearch" class="font-weight-bold mr-2 mb-0" style="white-space: nowrap;">Filter (Mondays & Unlocked):</label>
                                <div class="input-group input-group-sm mr-2" style="max-width: 200px;">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                                    </div>
                                    <input type="text" id="mondaySearch" class="form-control" placeholder="Select Date..." style="background-color: white;">
                                </div>
                                <button id="clearDate" class="btn btn-warning btn-sm" title="Clear Filter"><i class="fa fa-times"></i></button>
                            </div>
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table table-striped table-bordered table-hover dataTables-dates" >
                            <thead>
                            <tr>
                                <th>Date of Flag Ceremony</th>
                                <th>Total Attendees</th>
                                <th class="text-center">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            if (isset($pdo)) {
                                try {
                                    // Use COUNT(DISTINCT emp_id) because one employee might have multiple logs (e.g. In/Out, with/without ID)
                                    $stmt = $pdo->query("
                                        SELECT DATE(time_recorded) as ceremony_date, COUNT(DISTINCT emp_id) as attendee_count 
                                        FROM attendance_record 
                                        GROUP BY DATE(time_recorded) 
                                        ORDER BY ceremony_date DESC
                                    ");
                                    
                                    while ($row = $stmt->fetch()) {
                                        $dateObj = new DateTime($row['ceremony_date']);
                                        $formattedDate = $dateObj->format('F d, Y');

                                        echo "<tr class='gradeX'>";
                                        // Invisible span added for precise Datatable Searching based on raw date string
                                        echo "<td><span style='display:none;'>".$row['ceremony_date']."</span>" . $formattedDate . "</td>";
                                        echo "<td><span class='badge badge-primary'>" . htmlspecialchars($row['attendee_count']) . " Employees</span></td>";
                                        echo "<td class='text-center'>
                                                <button class='btn btn-success btn-sm btn-view-date' title='View Attendees'
                                                    data-toggle='modal' 
                                                    data-target='#attendanceModal'
                                                    data-date='" . htmlspecialchars($row['ceremony_date']) . "'
                                                    data-formatted='" . htmlspecialchars($formattedDate) . "'>
                                                    <i class='fa fa-users'></i> View Attendees
                                                </button>
                                              </td>";
                                        echo "</tr>";
                                    }
                                } catch (\PDOException $e) {
                                    echo "<tr><td colspan='4' class='text-danger'>Error fetching data: " . $e->getMessage() . "</td></tr>";
                                }
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

    <?php include 'view_attendance.php'; ?>
    
    <script src="js/jquery-3.1.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.js"></script>
    <script src="js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="js/plugins/slimscroll/jquery.slimscroll.min.js"></script>

    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/responsive.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Flatpickr Script -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script src="js/inspinia.js"></script>
    <script src="js/plugins/pace/pace.min.js"></script>

    <script>
        // Pass the unlocked dates from PHP to JavaScript
        var unlockedDates = <?php echo json_encode($unlocked_dates_array); ?>;

        $(document).ready(function(){
            var table = $('.dataTables-dates').DataTable({
                pageLength: 25,
                responsive: true,
                order: [[0, 'desc']],
                // Custom DOM structure to completely remove the default search ('f') and create a placeholder for our filter
                dom: "<'row mb-2'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 custom-filter-wrapper'>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row mt-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            });

            // Inject the custom calendar filter into the DataTables top-right section
            $('#custom-date-filter').css('display', 'block').appendTo('.custom-filter-wrapper');

            // Initialize Flatpickr for Monday search + Unlocked dates
            var calendarSearch = flatpickr("#mondaySearch", {
                disable: [
                    function(date) {
                        // Format the current JS date to YYYY-MM-DD
                        var d = new Date(date);
                        var month = '' + (d.getMonth() + 1);
                        var day = '' + d.getDate();
                        var year = d.getFullYear();

                        if (month.length < 2) month = '0' + month;
                        if (day.length < 2) day = '0' + day;

                        var dateString = [year, month, day].join('-');

                        // Return true to disable the date. 
                        // Disable if it is NOT a Monday AND NOT in the unlockedDates array
                        return (date.getDay() !== 1 && unlockedDates.indexOf(dateString) === -1);
                    }
                ],
                onChange: function(selectedDates, dateStr, instance) {
                    // When a valid date is selected, filter the DataTable on the 1st column 
                    // which contains the invisible raw date string inside a span
                    table.column(0).search(dateStr).draw();
                }
            });

            // Clear Date Filter
            $('#clearDate').click(function(){
                calendarSearch.clear();
                table.column(0).search('').draw();
            });

            // View Attendees functionality
            $('.dataTables-dates tbody').on('click', '.btn-view-date', function () {
                var btn = $(this);
                var rawDate = btn.data('date');
                var formattedDate = btn.data('formatted');

                $('#ceremony_date_display').text(formattedDate);
                $('#attendance_records_body').html('<tr><td colspan="10" class="text-center text-muted py-4"><i class="fa fa-spinner fa-spin fa-2x mb-2 d-block"></i><em>Loading attendees...</em></td></tr>');

                $.ajax({
                    url: 'view_attendance.php',
                    type: 'POST',
                    data: { action: 'fetch_date_attendance', ceremony_date: rawDate },
                    success: function(response) { $('#attendance_records_body').html(response); },
                    error: function() { $('#attendance_records_body').html('<tr><td colspan="10" class="text-center text-danger py-4"><i class="fa fa-exclamation-triangle fa-2x mb-2 d-block"></i><em>Failed to retrieve data. Check your connection.</em></td></tr>'); }
                });
            });

            $('#logout-btn').on('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Are you sure?', text: "You will be logged out of your current session.", icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#1cc88a', cancelButtonColor: '#e74a3b', confirmButtonText: 'Yes, log out!'
                }).then((result) => {
                    if (result.isConfirmed) { window.location.href = 'logout.php'; }
                });
            });
        });
    </script>
  </body>
</html>