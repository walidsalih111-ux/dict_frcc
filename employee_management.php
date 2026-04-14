<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

include 'connect.php';

// Fetch unique values for the filters from the database
$dept_query = $conn->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department");
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
        .btn-info { background-color: #36b9cc !important; border-color: #36b9cc !important; }
        .text-success { color: #1cc88a !important; }
        .text-primary { color: #4e73df !important; }
        .text-info { color: #36b9cc !important; }
        .bg-success { background-color: #1cc88a !important; }
        .bg-primary { background-color: #4e73df !important; }
        .bg-info { background-color: #36b9cc !important; }
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
                                            if($dept_query) {
                                                while($row = $dept_query->fetch_assoc()) { 
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
                                            if($status_query) {
                                                while($row = $status_query->fetch_assoc()) { 
                                                    echo '<option value="'.htmlspecialchars($row['status']).'">'.htmlspecialchars($row['status']).'</option>'; 
                                                }
                                            } 
                                            ?>
                                        </select>
                                    </div>
                                    <!-- New Role Filter -->
                                    <div class="col-md-3 mb-3">
                                        <label for="filter-role" class="form-label font-weight-bold">Role</label>
                                        <select id="filter-role" class="form-control form-select">
                                            <option value="">All Roles</option>
                                            <?php 
                                            if($role_query) {
                                                while($row = $role_query->fetch_assoc()) { 
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
                                                <th>ID</th>
                                                <th>Full Name</th>
                                                <th>Email</th>
                                                <th>Department</th>
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
                                                while($row = $result->fetch_assoc()) {
                                                    // Determine Status Badge Color
                                                    $statusBadge = "badge-secondary";
                                                    if($row['status'] == 'Plantilla') $statusBadge = "badge-success";
                                                    else if($row['status'] == 'Job Order') $statusBadge = "badge-primary";

                                                    // Determine Role Badge Color
                                                    $roleBadge = "badge-secondary"; // default if no account
                                                    $roleText = "None"; // <-- FIX: Initialize variable with a default value
                                                    
                                                    if(isset($row['role'])) {
                                                        if(strtolower($row['role']) == 'admin') {
                                                            $roleBadge = "badge-danger";
                                                        } else {
                                                            $roleBadge = "badge-info";
                                                        }
                                                        $roleText = ucfirst($row['role']);
                                                    }

                                                    echo '<tr>';
                                                    echo '<td>' . htmlspecialchars($row['emp_id'] ?? '') . '</td>';
                                                    echo '<td>' . htmlspecialchars($row['full'] ?? '') . '</td>';
                                                    echo '<td>' . htmlspecialchars($row['emp_email'] ?? '') . '</td>';
                                                    echo '<td>' . htmlspecialchars($row['department'] ?? '') . '</td>';
                                                    echo '<td>' . htmlspecialchars($row['area_of_assignment'] ?? '') . '</td>';
                                                    echo '<td>' . htmlspecialchars($row['designation'] ?? '') . '</td>';
                                                    echo '<td><span class="badge ' . $statusBadge . '">' . htmlspecialchars($row['status'] ?? '') . '</span></td>';
                                                    echo '<td><span class="badge ' . $roleBadge . '">' . htmlspecialchars($roleText) . '</span></td>';
                                                    
                                                    // Action Buttons with ALL Data Attributes for the Edit Modal
                                                    echo '<td>
                                                            <button class="btn btn-info btn-sm edit-btn" title="Edit" 
                                                                data-id="'.$row['emp_id'].'"
                                                                data-full="'.htmlspecialchars($row['full'] ?? '').'"
                                                                data-email="'.htmlspecialchars($row['emp_email'] ?? '').'"
                                                                data-age="'.htmlspecialchars($row['age'] ?? '').'"
                                                                data-gender="'.htmlspecialchars($row['gender'] ?? '').'"
                                                                data-dept="'.htmlspecialchars($row['department'] ?? '').'"
                                                                data-area="'.htmlspecialchars($row['area_of_assignment'] ?? '').'"
                                                                data-desig="'.htmlspecialchars($row['designation'] ?? '').'"
                                                                data-unit="'.htmlspecialchars($row['unit'] ?? '').'"
                                                                data-status="'.htmlspecialchars($row['status'] ?? '').'"
                                                                data-role="'.(isset($row['role']) ? htmlspecialchars($row['role']) : '').'"
                                                                data-username="'.(isset($row['username']) ? htmlspecialchars($row['username']) : '').'"
                                                                data-toggle="modal" data-target="#editEmployeeModal">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-danger btn-sm delete-btn" data-id="'.$row['emp_id'].'" title="Delete"><i class="fa fa-trash"></i></button>
                                                          </td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="9" class="text-center">No employees found.</td></tr>';
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

    <!-- Script to Initialize DataTables -->
    <script>
        $(document).ready(function(){
            // Initialize DataTables for Search and Pagination (Show Entries)
            var table = $('.dataTables-example').DataTable({
                pageLength: 10,
                responsive: true,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]]
            });

            // Bind filters to corresponding columns
            
            // Filter by Department (Column index 3)
            $('#filter-department').on('change', function() {
                table.column(3).search(this.value).draw();
            });

            // Filter by Area of Assignment (Column index 4)
            $('#filter-area').on('change', function() {
                table.column(4).search(this.value).draw();
            });

            // Filter by Status (Column index 6)
            $('#filter-status').on('change', function() {
                table.column(6).search(this.value).draw();
            });

            // Filter by Role (Column index 7)
            $('#filter-role').on('change', function() {
                table.column(7).search(this.value).draw();
            });

            // Delete Employee Button Handler
            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                const empId = $(this).data('id');
                const empName = $(this).closest('tr').find('td:eq(1)').text(); // Get employee name from row

                Swal.fire({
                    title: 'Delete Employee?',
                    text: `Are you sure you want to delete ${empName}? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e74a3b',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Delete!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading state and immediately start the AJAX call
                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait while we delete this employee.',
                            icon: 'info',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Send delete request via AJAX with timeout
                        $.ajax({
                            url: 'process_delete.php',
                            type: 'POST',
                            dataType: 'json',
                            data: { id: empId },
                            timeout: 10000, // 10 second timeout
                            success: function(result) {
                                if (result && result.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: result.message,
                                        confirmButtonColor: '#1cc88a',
                                        timer: 2000,
                                        showConfirmButton: false
                                    }).then(() => {
                                        // Reload the page to refresh the table
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: (result && result.message) ? result.message : 'Invalid response from server.',
                                        confirmButtonColor: '#e74a3b'
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                let errorMessage = 'Failed to delete employee. Please try again.';
                                if (status === 'timeout') {
                                    errorMessage = 'Request timed out. Please check your connection and try again.';
                                } else if (xhr.status === 0) {
                                    errorMessage = 'Unable to connect to server. Please check your internet connection.';
                                } else if (xhr.status >= 500) {
                                    errorMessage = 'Server error occurred. Please try again later.';
                                } else if (xhr.responseText) {
                                    console.error('Server response:', xhr.responseText);
                                }

                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: errorMessage,
                                    confirmButtonColor: '#e74a3b'
                                });
                                console.error('Delete error:', error);
                            }
                        });
                    }
                });
            });

            // SweetAlert Logout Confirmation
            $('#logout-btn').on('click', function(e) {
                e.preventDefault(); // Prevent instant redirect
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
</html>