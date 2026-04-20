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

// Search query
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// Pagination settings
$limit = 20; // entries per page
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$totalEmployees = 0;
$totalPages = 1;
$start = 0;
$end = 0;

try {
	// Count total employees for pagination (with optional search)
	if ($search !== '') {
		$countStmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE full IS NOT NULL AND TRIM(full) != '' AND full LIKE :search");
		$countStmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
		$countStmt->execute();
	} else {
		$countStmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE full IS NOT NULL AND TRIM(full) != ''");
		$countStmt->execute();
	}
	$totalEmployees = (int) $countStmt->fetchColumn();

	$totalPages = ($totalEmployees > 0) ? (int) ceil($totalEmployees / $limit) : 1;
	if ($page > $totalPages) {
		$page = $totalPages;
		$offset = ($page - 1) * $limit;
	}

	// Fetch paginated employees (with optional search)
	$sql = "SELECT emp_id, full, designation, department, area_of_assignment
			FROM employees
			WHERE full IS NOT NULL AND TRIM(full) != ''";
	if ($search !== '') {
		$sql .= " AND full LIKE :search";
	}
	$sql .= "\n         ORDER BY full ASC\n         LIMIT :limit OFFSET :offset";

	$employeeStmt = $pdo->prepare($sql);
	if ($search !== '') {
		$employeeStmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
	}
	$employeeStmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
	$employeeStmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
	$employeeStmt->execute();
	$employees = $employeeStmt->fetchAll(PDO::FETCH_ASSOC);

	$start = $totalEmployees > 0 ? $offset + 1 : 0;
	$end = min($offset + $limit, $totalEmployees);

} catch (PDOException $e) {
	die('Database Error: ' . $e->getMessage());
}

// Build base query string for pagination links (preserve search)
$baseQuery = [];
if ($search !== '') {
	$baseQuery['q'] = $search;
}
$queryBase = '?';
if (!empty($baseQuery)) {
	$queryBase = '?' . http_build_query($baseQuery) . '&';
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
								<form class="form-inline mb-0" method="get" action="my_emp_attendance.php">
									<div class="input-group input-group-sm">
										<input type="text" name="q" class="form-control" placeholder="Search by name..." value="<?php echo htmlspecialchars($search ?? '', ENT_QUOTES); ?>">
										<div class="input-group-append">
											<button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
										</div>
									</div>
								</form>
								<small class="text-muted d-none d-sm-block mb-0">Select an employee and click View Attendance.</small>
							</div>
						</div>
						<div class="ibox-content">
							<div class="table-responsive">
								<table class="table table-striped table-bordered table-hover">
									<thead>
										<tr>
											<th>Employee Name</th>
											<th>Designation</th>
											<th>Division</th>
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
													<td><?php echo htmlspecialchars($emp['area_of_assignment'] ?? ''); ?></td>
													<td>
														<button type="button" class="btn btn-primary btn-sm view-attendance-btn" data-emp-id="<?php echo (int) $emp['emp_id']; ?>" data-emp-name="<?php echo htmlspecialchars($emp['full'] ?? '', ENT_QUOTES); ?>">
															<i class="fa fa-eye"></i> View Attendance
														</button>
													</td>
												</tr>
											<?php endforeach; ?>
										<?php else: ?>
											<tr>
												<td colspan="5" class="text-center">No employees found.</td>
											</tr>
										<?php endif; ?>
									</tbody>
									</table>
								</div>

								<!-- Pagination and summary -->
								<?php if ($totalEmployees > 0): ?>
									<div class="d-flex justify-content-between align-items-center mt-3">
										<div class="text-muted">
											Showing <?php echo $start; ?> to <?php echo $end; ?> of <?php echo $totalEmployees; ?> entries
										</div>
										<nav aria-label="Employee list pagination">
											<ul class="pagination mb-0">
												<?php if ($page > 1): ?>
													<li class="page-item"><a class="page-link" href="<?php echo $queryBase; ?>page=<?php echo $page - 1; ?>">&laquo; Prev</a></li>
												<?php else: ?>
													<li class="page-item disabled"><span class="page-link">&laquo; Prev</span></li>
												<?php endif; ?>

												<?php
													$adjacents = 2;
													$startPage = max(1, $page - $adjacents);
													$endPage = min($totalPages, $page + $adjacents);

													if ($startPage > 1) {
														?>
														<li class="page-item"><a class="page-link" href="<?php echo $queryBase; ?>page=1">1</a></li>
														<?php if ($startPage > 2) { ?>
															<li class="page-item disabled"><span class="page-link">...</span></li>
														<?php }
													}

													for ($p = $startPage; $p <= $endPage; $p++) {
														if ($p == $page) {
															echo '<li class="page-item active" aria-current="page"><span class="page-link">'.$p.'</span></li>';
														} else {
														?>
														<li class="page-item"><a class="page-link" href="<?php echo $queryBase; ?>page=<?php echo $p; ?>"><?php echo $p; ?></a></li>
														<?php
														}
													}

													if ($endPage < $totalPages) {
														if ($endPage < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
														?>
														<li class="page-item"><a class="page-link" href="<?php echo $queryBase; ?>page=<?php echo $totalPages; ?>"><?php echo $totalPages; ?></a></li>
														<?php
													}
												?>

												<?php if ($page < $totalPages): ?>
													<li class="page-item"><a class="page-link" href="<?php echo $queryBase; ?>page=<?php echo $page + 1; ?>">Next &raquo;</a></li>
												<?php else: ?>
													<li class="page-item disabled"><span class="page-link">Next &raquo;</span></li>
												<?php endif; ?>
											</ul>
										</nav>
									</div>
								<?php endif; ?>

							</div>
						</div>
				</div>
			</div>

		</div>
	</div> <!-- closes page-wrapper -->

	<script src="js/jquery-3.1.1.min.js"></script>
	<script src="js/popper.min.js"></script>
	<script src="js/bootstrap.js"></script>
	<script src="js/plugins/metisMenu/jquery.metisMenu.js"></script>
	<script src="js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
	<script src="js/inspinia.js"></script>
	<script src="js/plugins/pace/pace.min.js"></script>
	<!-- SweetAlert2 -->
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

	<script>
		// Dynamic tabbed attendance viewer
		(function($){
			function createTab(empId, empName) {
				var tabId = 'emp-tab-' + empId;
				
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