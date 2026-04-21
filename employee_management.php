<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

include 'connect.php';

// Fetch unique values for the filters from the database
$dept_query   = $conn->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department");
$status_query = $conn->query("SELECT DISTINCT status FROM employees WHERE status IS NOT NULL AND status != '' ORDER BY status");

// Fetch distinct roles from the user_account table for the role filter
$role_query = $conn->query("SELECT DISTINCT role FROM user_account WHERE role IS NOT NULL AND role != '' ORDER BY role");
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>DICT Monday Flag Raising | Employee Management</title>
    <link rel="icon" type="image/png" href="img/logo/DICT.png">

    <link href="css/bootstrap.min.css" rel="stylesheet" />
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet" />

    <!-- DataTables CSS (Loaded via CDN) -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

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
        .btn-info    { background-color: #36b9cc !important; border-color: #36b9cc !important; }
        .text-success { color: #1cc88a !important; }
        .text-primary { color: #4e73df !important; }
        .text-info    { color: #36b9cc !important; }
        .bg-success   { background-color: #1cc88a !important; }
        .bg-primary   { background-color: #4e73df !important; }
        .bg-info      { background-color: #36b9cc !important; }

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
                    <!-- Sidebar -->
                    <?php include 'sidebar.php'; ?>
                </ul>
            </div>
        </nav>

        <!-- Topbar replaces the opening of page-wrapper since it contains it -->
        <?php include 'topbar.php'; ?>

            <!-- Main Content -->
            <div class="wrapper wrapper-content animated fadeInRight">
                <div class="page-callout">
                    <div>
                        <p class="callout-title">Employee Directory</p>
                        <p class="callout-subtitle">Manage employee records, account status, and role assignments from this panel.</p>
                    </div>
                    <span class="callout-icon"><i class="fa fa-users"></i></span>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="ibox ">
                            <div class="ibox-title">
                                <h5>Employee Management List</h5>
                                <div class="ibox-tools">
                                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addEmployeeModal">
                                        <i class="fa fa-plus"></i> Add New Employee
                                    </button>
                                </div>
                            </div>
                            <div class="ibox-content">

                                <!-- Search Filters -->
                                <div class="row mb-4">
                                    <!-- Changed col-md-4 to col-md-3 to fit 4 filters -->
                                    <div class="col-md-3 mb-3">
                                        <label for="filter-department" class="form-label font-weight-bold">Department</label>
                                        <select id="filter-department" class="form-control form-select">
                                            <option value="">All Departments</option>
                                            <?php 
                                            if ($dept_query) {
                                                while ($row = $dept_query->fetch_assoc()) { 
                                                    echo '<option value="'.htmlspecialchars($row['department']).'">'.htmlspecialchars($row['department']).'</option>'; 
                                                }
                                            } 
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="filter-area" class="form-label font-weight-bold">Area of Assignment</label>
                                        <select id="filter-area" class="form-control form-select">
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
                                    <div class="col-md-3 mb-3">
                                        <label for="filter-status" class="form-label font-weight-bold">Status</label>
                                        <select id="filter-status" class="form-control form-select">
                                            <option value="">All Statuses</option>
                                            <?php 
                                            if ($status_query) {
                                                while ($row = $status_query->fetch_assoc()) { 
                                                    echo '<option value="'.htmlspecialchars($row['status']).'">'.htmlspecialchars($row['status']).'</option>'; 
                                                }
                                            } 
                                            ?>
                                        </select>
                                    </div>
                                    <!-- Role Filter -->
                                    <div class="col-md-3 mb-3">
                                        <label for="filter-role" class="form-label font-weight-bold">Role</label>
                                        <select id="filter-role" class="form-control form-select">
                                            <option value="">All Roles</option>
                                            <?php 
                                            if ($role_query) {
                                                while ($row = $role_query->fetch_assoc()) { 
                                                    // Display uppercase first letter for aesthetics
                                                    echo '<option value="'.htmlspecialchars(ucfirst($row['role'])).'">'.htmlspecialchars(ucfirst($row['role'])).'</option>'; 
                                                }
                                            } 
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <hr>

                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover dataTables-example">
                                        <thead>
                                            <tr>
                                                <th>Full Name</th>
                                                <th>Email</th>
                                                <th>Division</th>
                                                <th>Area of Assignment</th>
                                                <th>Designation</th>
                                                <th>Status</th>
                                                <th>Role</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Fetch employees and their user roles using a LEFT JOIN
                                            $sql = "SELECT e.*, u.role, u.username 
                                                    FROM employees e 
                                                    LEFT JOIN user_account u ON e.emp_id = u.emp_id 
                                                    ORDER BY e.emp_id ASC";
                                            $result = $conn->query($sql);

                                            if ($result && $result->num_rows > 0) {
                                                while ($row = $result->fetch_assoc()) {
                                                    // Determine Status Badge Color
                                                    $statusBadge = "badge-secondary";
                                                    if ($row['status'] == 'Plantilla')  $statusBadge = "badge-success";
                                                    elseif ($row['status'] == 'Job Order') $statusBadge = "badge-primary";

                                                    // Determine Role Badge Color
                                                    $roleBadge = "badge-secondary";
                                                    $roleText  = "None";
                                                    
                                                    if (isset($row['role'])) {
                                                        $roleBadge = (strtolower($row['role']) == 'admin') ? "badge-danger" : "badge-info";
                                                        $roleText  = ucfirst($row['role']);
                                                    }

                                                    echo '<tr>';
                                                    echo '<td>' . htmlspecialchars($row['full']               ?? '') . '</td>';
                                                    echo '<td>' . htmlspecialchars($row['emp_email']          ?? '') . '</td>';
                                                    echo '<td>' . htmlspecialchars($row['department']         ?? '') . '</td>';
                                                    echo '<td>' . htmlspecialchars($row['area_of_assignment'] ?? '') . '</td>';
                                                    echo '<td>' . htmlspecialchars($row['designation']        ?? '') . '</td>';
                                                    echo '<td><span class="badge ' . $statusBadge . '">' . htmlspecialchars($row['status'] ?? '') . '</span></td>';
                                                    echo '<td><span class="badge ' . $roleBadge   . '">' . htmlspecialchars($roleText)         . '</span></td>';
                                                    
                                                    // Determine whether the employee already has a user account
                                                    $hasAccount       = !empty($row['username']);
                                                    $statusBtnClass   = $hasAccount ? 'btn-warning'   : 'btn-success';
                                                    $statusBtnLabel   = $hasAccount ? 'Deactivate'     : 'Activate';
                                                    $statusBtnIcon    = $hasAccount ? 'fa-user-times'  : 'fa-user-plus';
                                                    $accountStatusVal = $hasAccount ? 'active'         : 'inactive';

                                                    // Action Buttons with ALL Data Attributes for the Edit Modal
                                                    echo '<td>
                                                            <button class="btn btn-info btn-sm edit-btn" title="Edit" 
                                                                data-id="'     . $row['emp_id']                                              . '"
                                                                data-full="'   . htmlspecialchars($row['full']               ?? '')           . '"
                                                                data-email="'  . htmlspecialchars($row['emp_email']          ?? '')           . '"
                                                                data-age="'    . htmlspecialchars($row['age']                ?? '')           . '"
                                                                data-gender="' . htmlspecialchars($row['gender']             ?? '')           . '"
                                                                data-dept="'   . htmlspecialchars($row['department']         ?? '')           . '"
                                                                data-area="'   . htmlspecialchars($row['area_of_assignment'] ?? '')           . '"
                                                                data-desig="'  . htmlspecialchars($row['designation']        ?? '')           . '"
                                                                data-unit="'   . htmlspecialchars($row['unit']               ?? '')           . '"
                                                                data-status="' . htmlspecialchars($row['status']             ?? '')           . '"
                                                                data-role="'   . (isset($row['role'])     ? htmlspecialchars($row['role'])     : '') . '"
                                                                data-username="'. (isset($row['username']) ? htmlspecialchars($row['username']) : '') . '"
                                                                data-toggle="modal" data-target="#editEmployeeModal">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button class="btn ' . $statusBtnClass . ' btn-sm status-btn"
                                                                data-id="' . $row['emp_id'] . '"
                                                                data-account-status="' . $accountStatusVal . '"
                                                                title="' . $statusBtnLabel . ' Account">
                                                                <i class="fa ' . $statusBtnIcon . '"></i> ' . $statusBtnLabel . '
                                                            </button>
                                                          </td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="8" class="text-center">No employees found.</td></tr>';
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

        </div> <!-- Closes page-wrapper -->
    </div> <!-- Closes wrapper -->

    <!-- ADD EMPLOYEE MODAL (Extracted) -->
    <?php include 'add_employee_modal.php'; ?>
    
    <!-- Assuming this handles something else as per original code -->
    <?php include 'add_new_employee.php'; ?>

    <script src="js/jquery-3.1.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.js"></script>
    <?php include 'edit_employee.php'; ?>
    
    <script src="js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="js/plugins/slimscroll/jquery.slimscroll.min.js"></script>

    <!-- Custom and plugin javascript -->
    <script src="js/inspinia.js"></script>
    <script src="js/plugins/pace/pace.min.js"></script>

    <!-- DataTables JS (Loaded via CDN) -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

    <!-- SweetAlert2 Library (Added via CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Connect Custom SweetAlert Functions -->
    <script src="js/function.js"></script>

    <!-- Employee Management JS -->
    <script src="js/employee_management.js"></script>

    <!-- Real-time Username Uniqueness Check -->
    <script src="js/username_check.js"></script>
</body>
</html>