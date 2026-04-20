<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
	header('Location: index.php');
	exit;
}

include 'connect.php';

if (!$pdo) {
	die('Database connection failed: ' . ($db_error ?? 'Unknown error'));
}

$employees = [];

try {
	// Fetch all valid employees for DataTables
	$sql = "SELECT emp_id, full, designation, department, unit, area_of_assignment
			FROM employees
			WHERE full IS NOT NULL AND TRIM(full) != ''
			ORDER BY full ASC";

	$employeeStmt = $pdo->prepare($sql);
	$employeeStmt->execute();
	$employees = $employeeStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
	die('Database Error: ' . $e->getMessage());
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>My Employee Attendance</title>

	<link href="css/bootstrap.min.css" rel="stylesheet" />
	<link href="font-awesome/css/font-awesome.css" rel="stylesheet" />
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

	<link href="css/animate.css" rel="stylesheet" />
	<link href="css/style.css" rel="stylesheet" />
	<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet" />

	<style>
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
			border-radius: 15px !important;
			box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
			background: rgba(255, 255, 255, 0.95) !important;
			border: none !important;
			margin-bottom: 25px;
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

		.table td,
		.table th {
			vertical-align: middle !important;
		}

        /* DataTables specific transparent matching */
        div.dataTables_wrapper div.dataTables_filter input,
        div.dataTables_wrapper div.dataTables_length select {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 4px 8px;
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

        /* Dynamic tabs area for viewing employee attendance */
        #attendanceTabsWrapper { margin-bottom: 18px; }
        #attendanceTabs { 
            margin-bottom: 0; 
            border-bottom: none;
        }
        #attendanceTabs li a {
            border-radius: 8px 8px 0 0;
            background: rgba(255,255,255,0.7);
            color: #555;
            font-weight: 600;
            border: none;
            margin-right: 5px;
            display: flex;
            align-items: center;
            padding: 10px 15px;
        }
        #attendanceTabs li.active a {
            background: #fff;
            color: #4e73df;
            box-shadow: 0 -3px 10px rgba(0,0,0,0.05);
        }
        #attendanceTabs .close-tab { 
            margin-left: 10px; 
            color: #999; 
            border: none; 
            background: transparent; 
            font-size: 18px; 
            line-height: 1;
            padding: 0;
            outline: none;
        }
        #attendanceTabs .close-tab:hover { color: #e74a3b; cursor: pointer; }
        #attendanceTabContent { 
            border: none; 
            padding: 0; 
            background: transparent; 
            border-radius: 0 0 8px 8px; 
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

		<?php include 'topbar.php'; ?>

		<div class="wrapper wrapper-content animated fadeInRight">
			<div class="page-callout">
				<div>
					<p class="callout-title">Employee Attendance Lookup</p>
					<p class="callout-subtitle">Select an employee to review attendance history, compliance, and submitted proof photos.</p>
				</div>
				<span class="callout-icon"><i class="fa fa-search"></i></span>
			</div>

			<!-- Dynamic Attendance Tabs -->
			<div id="attendanceTabsWrapper">
				<ul class="nav nav-tabs" id="attendanceTabs" role="tablist"></ul>
				<div class="tab-content" id="attendanceTabContent" role="tablist"></div>
			</div>

			<div class="row" id="employeeListSection">
				<div class="col-lg-12">
					<div class="ibox">
						<div class="ibox-title d-flex justify-content-between align-items-center">
							<h5>Employee List</h5>
							<div class="d-flex align-items-center" style="gap:10px;">
								<small class="text-muted d-none d-sm-block mb-0">Select an employee and click View Attendance.</small>
							</div>
						</div>
						<div class="ibox-content">
							<div class="table-responsive">
								<table class="table table-striped table-bordered table-hover dataTables-employees">
									<thead>
										<tr>
											<th>Employee Name</th>
											<th>Designation</th>
											<th>Division</th>
                                            <th>Unit</th>
											<th>Area of Assignment</th>
											<th width="160">Action</th>
										</tr>
									</thead>
									<tbody>
										<?php if (!empty($employees)): ?>
											<?php foreach ($employees as $emp): ?>
												<tr>
													<td><?php echo htmlspecialchars($emp['full'] ?? ''); ?></td>
													<td><?php echo htmlspecialchars($emp['designation'] ?? ''); ?></td>
													<td><?php echo htmlspecialchars($emp['department'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($emp['unit'] ?? ''); ?></td>
													<td><?php echo htmlspecialchars($emp['area_of_assignment'] ?? ''); ?></td>
													<td>
														<button type="button" class="btn btn-primary btn-sm view-attendance-btn" data-emp-id="<?php echo (int) $emp['emp_id']; ?>" data-emp-name="<?php echo htmlspecialchars($emp['full'] ?? '', ENT_QUOTES); ?>">
															<i class="fa fa-eye"></i> View Attendance
														</button>
													</td>
												</tr>
											<?php endforeach; ?>
										<?php endif; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div> <!-- closes page-wrapper -->

    <!-- Photo Viewer Modal (Matches view_attendance.php style) -->
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
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
	
    <!-- SweetAlert2 -->
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

    <script>
        // Global function to be called by child elements rendered in get_employee_attendance.php
        function viewAttendancePhoto(path, time) {
            $('#attendanceImagePreview').attr('src', path);
            $('#attendancePhotoTime').text(time);
            $('#photoViewerModal').modal('show');
        }

        $(document).ready(function() {
            // Initialize DataTables
            $('.dataTables-employees').DataTable({
                pageLength: 20,
                responsive: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search employee..."
                }
            });
        });

		// Dynamic tabbed attendance viewer
		(function($){
			function createTab(empId, empName) {
				var tabId = 'emp-tab-' + empId;
				
				// Hide the main employee list table
				$('#employeeListSection').slideUp(300);

				// If exists, show it and scroll to it
				if ($('#attendanceTabs a[href="#' + tabId + '"]').length) {
					$('#attendanceTabs a[href="#' + tabId + '"]').tab('show');
                    $('html, body').animate({ scrollTop: $("#attendanceTabsWrapper").offset().top - 20 }, 500);
					return;
				}

				// Build tab element with close button
				var $li = $('<li/>');
				var $a = $('<a/>', { href: '#' + tabId, 'data-toggle': 'tab' }).text(empName);
				var $close = $('<button/>', { type: 'button', 'class': 'close-tab', title: 'Close tab' }).html('&times;');
				
                $a.append($close);
				$li.append($a);
				$('#attendanceTabs').append($li);

				// Create content placeholder
				var $content = $('<div/>', { class: 'tab-pane', id: tabId }).html('<div class="text-center p-4 bg-white rounded shadow-sm"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i> <p class="mt-2 text-muted">Loading attendance records...</p></div>');
				$('#attendanceTabContent').append($content);

				// Activate new tab
				$('#attendanceTabs a[href="#' + tabId + '"]').tab('show');
                $('html, body').animate({ scrollTop: $("#attendanceTabsWrapper").offset().top - 20 }, 500);

				// Load attendance via AJAX from get_employee_attendance.php
				$.get('get_employee_attendance.php', { emp_id: empId })
				.done(function(html){
					$content.html(html);
				})
				.fail(function(){
					$content.html('<div class="alert alert-danger bg-white shadow-sm">Failed to load attendance. Please check your connection or database.</div>');
				});
			}

			$(document).on('click', '.view-attendance-btn', function(e){
				e.preventDefault();
				var empId = $(this).data('emp-id');
				var empName = $(this).data('emp-name') || 'Employee';
				createTab(empId, empName);
			});

			// Close tab handler
			$(document).on('click', '#attendanceTabs .close-tab', function(e){
				e.preventDefault();
				e.stopPropagation(); // Prevents the tab from being activated when clicking close
				var $btn = $(this);
				var $li = $btn.closest('li');
				var $a = $li.find('a');
				var href = $a.attr('href');
				var $content = $(href);
				var wasActive = $li.hasClass('active');
				
                $li.remove();
				$content.remove();
				
                // If the closed tab was active, switch to the last open tab
                if (wasActive) {
					var $last = $('#attendanceTabs li:last a');
					if ($last.length) { 
                        $last.tab('show'); 
                    }
				}

                // If no tabs remain open, slide the employee list back down
                if ($('#attendanceTabs li').length === 0) {
                    $('#employeeListSection').slideDown(300);
                }
			});
		})(jQuery);
	</script>

	<!-- Logout handler: SweetAlert confirmation matching other pages -->
	<script>
		$(function(){
			$(document).on('click', '#logout-btn', function(e){
				e.preventDefault();
				if (typeof Swal !== 'undefined') {
					Swal.fire({
						title: 'Are you sure?',
						text: 'You will be logged out of your session.',
						icon: 'warning',
						showCancelButton: true,
						confirmButtonColor: '#1ab394',
						cancelButtonColor: '#ed5565',
						confirmButtonText: 'Yes, log out',
						cancelButtonText: 'Cancel'
					}).then(function(result){
						if (result && result.isConfirmed) {
							window.location.href = 'logout.php';
						}
					});
				} else {
					if (confirm('Are you sure you want to log out?')) {
						window.location.href = 'logout.php';
					}
				}
			});
		});
	</script>
</body>
</html>