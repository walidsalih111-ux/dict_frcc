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

    <title>My Account | DICT Monday Flag Raising Ceremony</title>
    <!-- Added Website Tab Icon -->
    <link rel="icon" type="image/png" href="img/logo/DICT.png">

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        /* Base typography and body */
        body {
            font-family: "Open Sans", "Helvetica Neue", Helvetica, Arial, sans-serif;
            margin: 0;
            padding-bottom: 40px;
        }

        /* Fix layout overlap caused by the fixed locked-sidebar */
        #page-wrapper {
            margin-left: 220px !important;
            transition: all 0.3s ease;
            min-height: 100vh;
        }
        body.mini-navbar #page-wrapper {
            margin-left: 70px !important;
        }
        @media (max-width: 768px) {
            #page-wrapper { margin-left: 0 !important; }
            body.mini-navbar #page-wrapper { margin-left: 0 !important; }
        }

        /* Solid Blue Background matching reference image */
        body.gray-bg, #page-wrapper, .wrapper.wrapper-content {
            background-color: #4e73df !important;
        }

        /* Ibox Card Styling matching modern dashboard */
        .ibox {
            border-radius: 15px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
            background: rgba(255, 255, 255, 0.98) !important;
            border: none !important;
            margin-top: 10px;
            margin-bottom: 25px;
            overflow: hidden;
        }

        .ibox-title {
            background: transparent !important;
            border-bottom: 1px solid rgba(0,0,0,0.05) !important;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px 25px !important;
        }

        .ibox-content {
            background: transparent !important;
            border-radius: 0 0 15px 15px !important;
            border: none !important;
            padding: 30px !important;
        }

        /* Callout Styles */
        .page-callout {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(78, 115, 223, 0.15);
            border-left: 5px solid #4e73df;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 18px 22px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .page-callout .callout-title { margin: 0; font-weight: 800; color: #2f4050; font-size: 16px; }
        .page-callout .callout-subtitle { margin: 4px 0 0; color: #5a5c69; font-size: 13px; }
        .page-callout .callout-icon { width: 42px; height: 42px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: rgba(78, 115, 223, 0.1); color: #4e73df; flex-shrink: 0; font-size: 18px; }

        @media (max-width: 767px) {
            .page-callout { flex-direction: column; align-items: flex-start; }
        }

        /* Form Controls */
        label { font-weight: 700; color: #5a5c69; font-size: 13px; margin-bottom: 6px; }
        .form-control {
            border-radius: 8px;
            border: 1px solid #e3e6f0;
            padding: 10px 15px;
            font-size: 14px;
            color: #3a3b45;
            transition: all 0.2s ease-in-out;
        }
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
            outline: 0;
        }
        .form-control[readonly], .form-control[disabled] {
            background-color: #f8f9fc;
            opacity: 1;
            color: #6e707e;
            font-weight: 600;
        }
        
        .input-group-text {
            background-color: #f8f9fc;
            border: 1px solid #e3e6f0;
            color: #4e73df;
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
            border-right: none;
        }
        .form-control.with-icon {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            border-left: none;
        }
        .form-control.with-icon:focus {
            border-left: 1px solid #4e73df;
        }

        .input-group .btn-outline-secondary {
            border: 1px solid #e3e6f0;
            border-left: none;
            color: #858796;
            background: #fff;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }
        .input-group .btn-outline-secondary:hover { background: #f8f9fc; color: #4e73df; }

        /* Primary Button */
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
            border-radius: 8px;
            padding: 10px 24px;
            font-weight: 600;
            letter-spacing: 0.05em;
            transition: all 0.2s ease;
        }
        .btn-primary:hover, .btn-primary:focus {
            background-color: #2e59d9;
            border-color: #2653d4;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(78, 115, 223, 0.3);
        }
        
        .hr-line-dashed {
            border-top: 1px dashed #eaecf4;
            color: #ffffff;
            background-color: #ffffff;
            height: 1px;
            margin: 20px 0;
        }
    </style>
</head>

<body class="gray-bg">
    <div id="wrapper">
        <!-- SIDEBAR NAVIGATION -->
        <?php include 'sidebar_user.php'; ?>

        <!-- TOP NAVIGATION (Opens #page-wrapper) -->
        <?php include 'topbar_user.php'; ?>

        <!-- MAIN CONTENT -->
        <div class="wrapper wrapper-content animated fadeInRight mt-3">
            <div class="row justify-content-center">
                <div class="col-lg-12 px-xl-4">
                    
                    <!-- Page Intro Callout -->
                    <div class="page-callout">
                        <div>
                            <p class="callout-title">My Account Settings</p>
                            <p class="callout-subtitle">Manage your personal information, login credentials, and security preferences securely.</p>
                        </div>
                        <span class="callout-icon"><i class="fa fa-user-shield"></i></span>
                    </div>

                    <div class="row">
                        <!-- LEFT COLUMN: Employee Profile Read-Only -->
                        <div class="col-lg-5 col-md-12 mb-4">
                            <div class="ibox h-100 mb-0">
                                <div class="ibox-title">
                                    <h5 class="text-primary fw-bold mb-0"><i class="fa fa-id-card-o me-2"></i> Employee Profile</h5>
                                </div>
                                <div class="ibox-content">
                                    
                                    <div class="form-group mb-3">
                                        <label>Full Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-user"></i></span>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($userProfile['full'] ?? $_SESSION['fullname'] ?? ''); ?>" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label>Designation</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-briefcase"></i></span>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($userProfile['designation'] ?? 'N/A'); ?>" readonly>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label>Department</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-building-o"></i></span>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($userProfile['department'] ?? 'N/A'); ?>" readonly>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label>Unit</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-sitemap"></i></span>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($userProfile['unit'] ?? 'N/A'); ?>" readonly>
                                        </div>
                                    </div>

                                    <div class="form-group mb-4">
                                        <label>Area of Assignment</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-map-marker"></i></span>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($userProfile['area_of_assignment'] ?? 'N/A'); ?>" readonly>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info pb-2 pt-3 px-3 rounded text-center" style="background-color: rgba(54, 185, 204, 0.1); border: 1px solid rgba(54, 185, 204, 0.2); color: #2a96a5;">
                                        <i class="fa fa-info-circle mb-2 fa-2x"></i>
                                        <p class="small fw-semibold mb-0" style="font-size: 12px;">Contact the Admin to request changes to your official employee records.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT COLUMN: Editable Form -->
                        <div class="col-lg-7 col-md-12 mb-4">
                            <div class="ibox h-100 mb-0">
                                <div class="ibox-title">
                                    <h5 class="text-primary fw-bold mb-0"><i class="fa fa-lock me-2"></i> Account Security</h5>
                                </div>
                                <div class="ibox-content">
                                    <form method="POST" action="my_account.php" id="accountForm">
                                        
                                        <h4 class="mb-3 fw-bold text-dark border-bottom pb-2" style="font-size: 15px;">Login Credentials</h4>
                                        
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <div class="form-group">
                                                    <label>Username</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fa fa-at"></i></span>
                                                        <input type="text" id="acc_username" name="username" class="form-control with-icon" value="<?php echo htmlspecialchars($userProfile['username'] ?? ''); ?>" required placeholder="Enter a unique username">
                                                        <input type="hidden" id="acc_emp_id" value="<?php echo htmlspecialchars($userProfile['emp_id'] ?? ''); ?>">
                                                    </div>
                                                    <div id="usernameFeedback"></div>
                                                    <small class="form-text text-muted mt-1">This is the username you use to log in to the portal.</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="hr-line-dashed mt-4 mb-4"></div>

                                        <h4 class="mb-3 fw-bold text-dark border-bottom pb-2" style="font-size: 15px;">
                                            Update Password <span class="badge bg-light text-muted fw-normal ms-2 border" style="font-size: 11px;">Leave blank to keep current</span>
                                        </h4>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <div class="form-group">
                                                    <label>New Password</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fa fa-key"></i></span>
                                                        <input type="password" id="new_password" name="new_password" class="form-control with-icon" minlength="8" placeholder="Min. 8 characters">
                                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password', this)"><i class="fa fa-eye"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <div class="form-group">
                                                    <label>Confirm New Password</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fa fa-lock"></i></span>
                                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control with-icon" minlength="8" placeholder="Repeat password">
                                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password', this)"><i class="fa fa-eye"></i></button>
                                                    </div>
                                                    <div id="pwd_match_feedback" class="mt-1"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-4 pt-3">
                                            <div class="col-md-12 text-end">
                                                <button type="submit" class="btn btn-primary shadow-sm" id="saveBtn">
                                                    <i class="fa fa-save me-1"></i> Save Changes
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div> <!-- Close page-wrapper opened inside topbar_user.php -->
    </div> <!-- Close wrapper -->

    <!-- Bootstrap 5 JS - Replaced legacy jQuery plugins to fix Sidebar Expand/Minimize Conflicts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

    <!-- Page-Level Scripts -->
    <script>
        // Real-time Username Availability Validation
        var isUsernameValid = true;
        var isPasswordValid = true;
        const usernameInput = document.getElementById('acc_username');
        const usernameFeedback = document.getElementById('usernameFeedback');
        const saveBtn = document.getElementById('saveBtn');
        const empIdInput = document.getElementById('acc_emp_id');
        let usernameTimeout = null;

        if (usernameInput) {
            const originalUsername = usernameInput.value.trim();

            usernameInput.addEventListener('input', function() {
                clearTimeout(usernameTimeout);
                const currentVal = this.value.trim();
                const empId = empIdInput ? empIdInput.value : '';

                // Reset visual state
                usernameFeedback.textContent = '';
                usernameInput.classList.remove('is-invalid', 'is-valid');
                usernameInput.style.borderColor = '#e3e6f0';
                
                // Allow empty to trigger standard HTML5 'required' validation later
                if (currentVal === '') {
                    isUsernameValid = false;
                    checkAllValidations();
                    return;
                }

                // If it is identical to their current username, no check needed
                if (currentVal === originalUsername) {
                    isUsernameValid = true;
                    checkAllValidations();
                    return;
                }

                // Debounce API call
                usernameTimeout = setTimeout(() => {
                    const formData = new FormData();
                    formData.append('username', currentVal);
                    formData.append('emp_id', empId);

                    fetch('check_username.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.available) {
                            usernameInput.classList.add('is-invalid');
                            usernameInput.style.borderColor = '#e74a3b';
                            usernameFeedback.textContent = 'Username is already taken.';
                            usernameFeedback.className = 'text-danger mt-1 d-block small fw-bold';
                            isUsernameValid = false;
                        } else {
                            usernameInput.classList.add('is-valid');
                            usernameInput.style.borderColor = '#1cc88a';
                            usernameFeedback.textContent = 'Username is available.';
                            usernameFeedback.className = 'text-success mt-1 d-block small fw-bold';
                            isUsernameValid = true;
                        }
                        checkAllValidations();
                    })
                    .catch(err => console.error('Error checking username:', err));
                }, 500); 
            });
        }

        // Real-time password matching feedback
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
        document.getElementById('new_password').addEventListener('input', checkPasswordMatch);

        function checkPasswordMatch() {
            var pwd = document.getElementById('new_password').value;
            var confirm_pwd = document.getElementById('confirm_password').value;
            var feedback = document.getElementById('pwd_match_feedback');
            var confirmInput = document.getElementById('confirm_password');
            
            if (confirm_pwd === '') {
                confirmInput.style.borderColor = '#e3e6f0';
                feedback.innerHTML = '';
                isPasswordValid = true;
                checkAllValidations();
                return;
            }
            
            if (pwd === confirm_pwd) {
                confirmInput.style.borderColor = '#1cc88a';
                feedback.innerHTML = '<span class="text-success small fw-bold"><i class="fa fa-check-circle"></i> Passwords match</span>';
                isPasswordValid = true;
            } else {
                confirmInput.style.borderColor = '#e74a3b';
                feedback.innerHTML = '<span class="text-danger small fw-bold"><i class="fa fa-times-circle"></i> Passwords do not match</span>';
                isPasswordValid = false;
            }
            checkAllValidations();
        }

        // Helper function to lock/unlock button based on all states
        function checkAllValidations() {
            saveBtn.disabled = !(isUsernameValid && isPasswordValid);
        }

        // Final Intercept for Form submission validation
        var accountForm = document.getElementById('accountForm');
        if (accountForm) {
            accountForm.addEventListener('submit', function(e) {
                if (!isUsernameValid) {
                    e.preventDefault();
                    Swal.fire({icon:'error', title:'Invalid Username', text: 'Please choose an available username.', confirmButtonColor: '#4e73df'});
                    usernameInput.focus();
                    return false;
                }

                var newPass = document.getElementById('new_password').value.trim();
                var confPass = document.getElementById('confirm_password').value.trim();

                if (newPass !== '' || confPass !== '') {
                    if (newPass.length < 8) {
                        e.preventDefault();
                        Swal.fire({icon:'warning', title:'Weak password', text: 'New password must be at least 8 characters.', confirmButtonColor: '#4e73df'});
                        return false;
                    }
                    if (newPass !== confPass) {
                        e.preventDefault();
                        Swal.fire({icon:'warning', title:'Password mismatch', text: 'New password and confirmation do not match.', confirmButtonColor: '#4e73df'});
                        return false;
                    }
                }
            });
        }

        // Toggle password visibility helper
        function togglePassword(inputId, btnElement) {
            var input = document.getElementById(inputId);
            var icon = btnElement.querySelector("i");
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        // Logout Confirmation Binding
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You will be logged out of your session.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#4e73df',
                    cancelButtonColor: '#e74a3b',
                    confirmButtonText: 'Yes, log out',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'logout.php';
                    }
                });
            });
        }

        // Show server-side result messages (account update) via SweetAlert2
        <?php if (isset($_GET['account_update']) && $_GET['account_update'] === 'success'): ?>
            Swal.fire({
                icon: 'success', 
                title: 'Settings Saved', 
                text: 'Your account credentials have been successfully updated.', 
                confirmButtonColor: '#4e73df',
                customClass: { confirmButton: 'btn btn-primary px-4 py-2' },
                buttonsStyling: false
            }).then(() => {
                // Clear the URL parameter so it doesn't show again on refresh
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        <?php elseif (isset($_GET['account_update']) && $_GET['account_update'] === 'error'): ?>
            Swal.fire({
                icon: 'error', 
                title: 'Update Failed', 
                text: <?php echo json_encode($_GET['reason'] ?? 'An error occurred while updating.'); ?>, 
                confirmButtonColor: '#e74a3b',
                customClass: { confirmButton: 'btn btn-danger px-4 py-2' },
                buttonsStyling: false
            }).then(() => {
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        <?php endif; ?>
    </script>
</body>
</html>