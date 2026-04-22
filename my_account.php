<?php
// Start the session to access login variables
session_start();

// 1. Check if the user is actually logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// 2. Check if the logged-in user has the correct role ('user')
if ($_SESSION['role'] !== 'user') {
    if ($_SESSION['role'] === 'admin') {
        header('Location: dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

// Safely get the user's full name
$fullname = htmlspecialchars($_SESSION['fullname']);

// 3. Database Connection
include 'connect.php';
if (!$pdo) {
    die('ERROR: Could not connect to the database. ' . ($db_error ?? 'Unknown error'));
}

// 4. Handle Form Submission (Update Profile)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    try {
        // Update username in user_account table
        if (!empty($username)) {
            $updateSql = "UPDATE user_account u 
                          JOIN employees e ON u.emp_id = e.emp_id
                          SET u.username = :username 
                          WHERE e.full = :fullname";
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute([
                ':username' => $username,
                ':fullname' => $_SESSION['fullname']
            ]);
            $_SESSION['username'] = $username; // Update session variable
        }

        // Handle Password Update if requested
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                header("Location: my_account.php?account_update=error&reason=" . urlencode("New passwords do not match."));
                exit;
            }
            
            // Note: If your system uses MD5 instead of password_hash, change this line to: $hashed_password = md5($new_password);
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $passSql = "UPDATE user_account u 
                        JOIN employees e ON u.emp_id = e.emp_id
                        SET u.password = :password 
                        WHERE e.full = :fullname";
            $passStmt = $pdo->prepare($passSql);
            $passStmt->execute([
                ':password' => $hashed_password,
                ':fullname' => $_SESSION['fullname']
            ]);
        }

        header("Location: my_account.php?account_update=success");
        exit;
    } catch (PDOException $e) {
        header("Location: my_account.php?account_update=error&reason=" . urlencode("Database error: " . $e->getMessage()));
        exit;
    }
}

// 5. Fetch the User's Profile Details from both employees and user_account
$profileSql = "SELECT e.*, u.username 
               FROM employees e
               LEFT JOIN user_account u ON e.emp_id = u.emp_id 
               WHERE e.full = :fullname LIMIT 1";
$profileStmt = $pdo->prepare($profileSql);
$profileStmt->bindParam(':fullname', $_SESSION['fullname'], PDO::PARAM_STR);
$profileStmt->execute();
$userProfile = $profileStmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Account  Employee Portal</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Fix layout overlap caused by the fixed locked-sidebar */
        @media (min-width: 769px) {
            #page-wrapper {
                margin-left: 220px !important;
                /* Optional: Adds smooth sliding animation when the sidebar minimizes */
                transition: margin-left 0.4s; 
            }
            body.mini-navbar #page-wrapper {
                margin-left: 70px !important;
            }
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <!-- SIDEBAR NAVIGATION -->
        <?php include 'sidebar_user.php'; ?>

        <!-- PAGE WRAPPER -->
        <div id="page-wrapper" class="gray-bg">
            <!-- TOP NAVIGATION -->
            <div class="row border-bottom">
                <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0">
                    <div class="navbar-header">
                        <a class="navbar-minimalize minimalize-styl-2 btn btn-primary " href="#"><i class="fa fa-bars"></i> </a>
                    </div>
                    <ul class="nav navbar-top-links navbar-right">
                        <li>
                            <span class="m-r-sm text-muted welcome-message">Welcome to Employee Portal.</span>
                        </li>
                        <li>
                            <a href="#" id="logoutBtn">
                                <i class="fa fa-sign-out"></i> Log out
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>

           

            <!-- MAIN CONTENT -->
            <div class="wrapper wrapper-content animated fadeInRight">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="ibox ">
                            <div class="ibox-title">
                                <h5>My Account Settings</h5>
                                <div class="ibox-tools">
                                    <a class="collapse-link">
                                        <i class="fa fa-chevron-up"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="ibox-content">
                                <form method="POST" action="my_account.php">
                                    
                                    <!-- Employee Information Section (Read-Only) -->
                                    <h3 class="m-t-none m-b-md">Employee Information</h3>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Full Name</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($userProfile['full'] ?? $_SESSION['fullname'] ?? ''); ?>" readonly disabled>
                                                <small class="form-text text-muted">Please contact HR to change your official details.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Designation</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($userProfile['designation'] ?? 'N/A'); ?>" readonly disabled>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Department</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($userProfile['department'] ?? 'N/A'); ?>" readonly disabled>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Unit</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($userProfile['unit'] ?? 'N/A'); ?>" readonly disabled>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Area of Assignment</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($userProfile['area_of_assignment'] ?? 'N/A'); ?>" readonly disabled>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="hr-line-dashed"></div>

                                    <!-- Account Credentials Section -->
                                    <h3 class="m-t-none m-b-md">Account Credentials</h3>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Username</label>
                                                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($userProfile['username'] ?? ''); ?>" required>
                                                <small class="form-text text-muted">This is the username you use to log in.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="hr-line-dashed"></div>

                                    <!-- Security Section -->
                                    <h3 class="m-t-none m-b-md">Security <small class="text-muted" style="font-size: 13px;">(Leave blank to keep current password)</small></h3>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>New Password</label>
                                                <input type="password" name="new_password" class="form-control" minlength="6">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Confirm New Password</label>
                                                <input type="password" name="confirm_password" class="form-control" minlength="6">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="hr-line-dashed"></div>

                                    <!-- Submit Action -->
                                    <div class="row">
                                        <div class="col-md-12 text-right">
                                            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Account Settings</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

           

    <!-- Mainly scripts -->
    <script src="js/jquery-3.1.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.js"></script>
    <script src="js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="js/plugins/slimscroll/jquery.slimscroll.min.js"></script>

    <!-- Custom and plugin javascript -->
    <script src="js/inspinia.js"></script>
    <script src="js/plugins/pace/pace.min.js"></script>

    <!-- Page-Level Scripts -->
    <script>
        // Logout Confirmation via SweetAlert2
        document.getElementById('logoutBtn').addEventListener('click', function(e) {
            e.preventDefault(); 
            Swal.fire({
                title: 'Are you sure?',
                text: "You will be logged out of your session.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#1ab394', // Match Theme Primary Color (Inspinia default)
                cancelButtonColor: '#e74a3b', 
                confirmButtonText: 'Yes, log out',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
                }
            });
        });

        // Show server-side result messages (account update)
        <?php if (isset($_GET['account_update']) && $_GET['account_update'] === 'success'): ?>
            Swal.fire({
                icon: 'success', 
                title: 'Profile Updated', 
                text: 'Your account settings have been successfully updated.', 
                confirmButtonColor: '#1ab394'
            });
        <?php elseif (isset($_GET['account_update']) && $_GET['account_update'] === 'error'): ?>
            Swal.fire({
                icon: 'error', 
                title: 'Update Failed', 
                text: <?php echo json_encode($_GET['reason'] ?? 'An error occurred'); ?>, 
                confirmButtonColor: '#1ab394'
            });
        <?php endif; ?>
    </script>
</body>
</html>