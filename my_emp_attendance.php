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

$selectedEmpId = isset($_GET['emp_id']) ? (int) $_GET['emp_id'] : 0;

$employees = [];
$selectedEmployee = null;
$attendanceRecords = [];
$compliantCount = 0;
$nonCompliantCount = 0;

try {
	$employeeStmt = $pdo->query(
		"SELECT emp_id, full, designation, department, area_of_assignment
		 FROM employees
		 WHERE full IS NOT NULL AND TRIM(full) != ''
		 ORDER BY full ASC"
	);
	$employees = $employeeStmt->fetchAll(PDO::FETCH_ASSOC);

	if ($selectedEmpId > 0) {
		$selectedEmployeeStmt = $pdo->prepare(
			"SELECT emp_id, full, designation, department, area_of_assignment
			 FROM employees
			 WHERE emp_id = :emp_id
			 LIMIT 1"
		);
		$selectedEmployeeStmt->execute(['emp_id' => $selectedEmpId]);
		$selectedEmployee = $selectedEmployeeStmt->fetch(PDO::FETCH_ASSOC);

		if ($selectedEmployee) {
			$attendanceStmt = $pdo->prepare(
				"SELECT time_recorded, with_id, is_asean, is_compliant, photo_path
				 FROM attendance_record
				 WHERE emp_id = :emp_id
				 ORDER BY time_recorded DESC"
			);
			$attendanceStmt->execute(['emp_id' => $selectedEmpId]);
			$attendanceRecords = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);

			foreach ($attendanceRecords as $record) {
				if ((int) $record['is_compliant'] === 1) {
					$compliantCount++;
				} else {
					$nonCompliantCount++;
				}
			}
		}
	}
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
	<link href="css/animate.css" rel="stylesheet" />
	<link href="css/style.css" rel="stylesheet" />

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

		.stats-badge {
			font-size: 14px;
			padding: 8px 12px;
			border-radius: 999px;
			margin-right: 8px;
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

		<?php include 'topbar.php'; ?>

		<div class="wrapper wrapper-content animated fadeInRight">
			<div class="page-callout">
				<div>
					<p class="callout-title">Employee Attendance Lookup</p>
					<p class="callout-subtitle">Select an employee to review attendance history, compliance, and submitted proof photos.</p>
				</div>
				<span class="callout-icon"><i class="fa fa-search"></i></span>
			</div>

			<div class="row">
				<div class="col-lg-12">
					<div class="ibox">
						<div class="ibox-title d-flex justify-content-between align-items-center">
							<h5>Employee List</h5>
							<small>Select an employee and click View Attendance.</small>
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
														<a href="my_emp_attendance.php?emp_id=<?php echo (int) $emp['emp_id']; ?>" class="btn btn-primary btn-sm">
															<i class="fa fa-eye"></i> View Attendance
														</a>
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
						</div>
					</div>
				</div>
			</div>

			<?php if ($selectedEmpId > 0): ?>
				<div class="row">
					<div class="col-lg-12">
						<div class="ibox">
							<div class="ibox-title d-flex justify-content-between align-items-center">
								<h5>
									Attendance Records
									<?php if ($selectedEmployee): ?>
										- <?php echo htmlspecialchars($selectedEmployee['full'] ?? ''); ?>
									<?php endif; ?>
								</h5>
								<?php if ($selectedEmployee): ?>
									<div>
										<span class="badge badge-success stats-badge">Compliant: <?php echo (int) $compliantCount; ?></span>
										<span class="badge badge-danger stats-badge">Non-Compliant: <?php echo (int) $nonCompliantCount; ?></span>
									</div>
								<?php endif; ?>
							</div>
							<div class="ibox-content">
								<?php if (!$selectedEmployee): ?>
									<div class="alert alert-warning mb-0">Employee not found.</div>
								<?php else: ?>
									<div class="table-responsive">
										<table class="table table-striped table-bordered table-hover">
											<thead>
												<tr>
													<th>Date & Time</th>
													<th>With ID</th>
													<th>ASEAN Attire</th>
													<th>Compliance</th>
													<th>Photo</th>
												</tr>
											</thead>
											<tbody>
												<?php if (!empty($attendanceRecords)): ?>
													<?php foreach ($attendanceRecords as $record): ?>
														<tr>
															<td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($record['time_recorded']))); ?></td>
															<td><?php echo htmlspecialchars($record['with_id'] ?? ''); ?></td>
															<td><?php echo htmlspecialchars($record['is_asean'] ?? ''); ?></td>
															<td>
																<?php if ((int) $record['is_compliant'] === 1): ?>
																	<span class="badge badge-success">Compliant</span>
																<?php else: ?>
																	<span class="badge badge-danger">Non-Compliant</span>
																<?php endif; ?>
															</td>
															<td>
																<?php if (!empty($record['photo_path'])): ?>
																	<a href="<?php echo htmlspecialchars($record['photo_path']); ?>" target="_blank" class="btn btn-info btn-xs">
																		<i class="fa fa-image"></i> View
																	</a>
																<?php else: ?>
																	<span class="text-muted">No photo</span>
																<?php endif; ?>
															</td>
														</tr>
													<?php endforeach; ?>
												<?php else: ?>
													<tr>
														<td colspan="5" class="text-center">No attendance records found for this employee.</td>
													</tr>
												<?php endif; ?>
											</tbody>
										</table>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div> <!-- closes page-wrapper -->
	</div> <!-- closes wrapper -->

	<script src="js/jquery-3.1.1.min.js"></script>
	<script src="js/popper.min.js"></script>
	<script src="js/bootstrap.js"></script>
	<script src="js/plugins/metisMenu/jquery.metisMenu.js"></script>
	<script src="js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
	<script src="js/inspinia.js"></script>
	<script src="js/plugins/pace/pace.min.js"></script>
</body>
</html>