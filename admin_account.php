<?php
session_start();

// Only allow admin users here
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

include 'connect.php';
if (!$pdo) {
    die('ERROR: Could not connect to the database. ' . ($db_error ?? 'Unknown error'));
}

// Fetch the admin's profile by user id
$profileSql = "SELECT e.*, u.username, u.id AS user_account_id, u.emp_id
               FROM user_account u
               LEFT JOIN employees e ON u.emp_id = e.emp_id
               WHERE u.id = :user_id LIMIT 1";
$profileStmt = $pdo->prepare($profileSql);
$profileStmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$profileStmt->execute();
$userProfile = $profileStmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>DICT Monday Flag Raising | Account Settings</title>
    <link rel="icon" type="image/png" href="img/logo/DICT.png">

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet">
    <!-- Keep animate.css for other potential elements, but removed from main wrapper -->
    <link href="css/animate.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
        /* Keep the same visual styles as my_account.php for parity */
        body { font-family: "Open Sans", "Helvetica Neue", Helvetica, Arial, sans-serif; margin: 0; padding-bottom: 40px; }
        #page-wrapper { transition: all 0.3s ease; min-height: 100vh; }
        @media (min-width: 769px) {
            body:not(.mini-navbar) #page-wrapper { margin-left: 220px !important; }
            body.mini-navbar #page-wrapper { margin-left: 70px !important; }
        }
        @media (max-width: 768px) {
            #page-wrapper { margin-left: 0 !important; }
        }
        body.gray-bg, #page-wrapper, .wrapper.wrapper-content { background-color: #4e73df !important; }
        .ibox { border-radius: 15px !important; box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important; background: rgba(255,255,255,0.98) !important; border: none !important; margin-top: 10px; margin-bottom: 25px; overflow: hidden; }
        .ibox-title { background: transparent !important; border-bottom: 1px solid rgba(0,0,0,0.05) !important; border-radius: 15px 15px 0 0 !important; padding: 20px 25px !important; }
        .ibox-content { background: transparent !important; border-radius: 0 0 15px 15px !important; border: none !important; padding: 30px !important; }
        .page-callout { background: rgba(255,255,255,0.95); border: 1px solid rgba(78,115,223,0.15); border-left: 5px solid #4e73df; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 18px 22px; margin-bottom: 22px; display:flex; align-items:center; justify-content:space-between; gap:15px; }
        .page-callout .callout-title { margin: 0; font-weight: 800; color: #2f4050; font-size: 16px; }
        label { font-weight: 700; color: #5a5c69; font-size: 13px; margin-bottom: 6px; }
        .form-control { border-radius: 8px; border: 1px solid #e3e6f0; padding: 10px 15px; font-size: 14px; color: #3a3b45; }
        .hr-line-dashed { border-top: 1px dashed #eaecf4; color: #ffffff; background-color: #ffffff; height: 1px; margin: 20px 0; }
        .input-group-text { background-color: #f8f9fc; border: 1px solid #e3e6f0; color: #4e73df; border-top-left-radius: 8px; border-bottom-left-radius: 8px; border-right: none; }
        .form-control.with-icon { border-top-left-radius: 0; border-bottom-left-radius: 0; border-left: none; }
        .btn-primary { background-color: #4e73df; border-color: #4e73df; border-radius: 8px; padding: 10px 24px; font-weight: 600; }
    </style>
</head>

<body class="gray-bg">
    <div id="wrapper">
        <?php include 'sidebar.php'; ?>
        <?php include 'topbar.php'; ?>

        <div class="wrapper wrapper-content mt-3">
            <div class="row justify-content-center">
                <div class="col-lg-12 px-xl-4">

                    <div class="page-callout">
                        <div>
                            <p class="callout-title">Admin Account Settings</p>
                            <p class="callout-subtitle">Manage your administrator profile and login credentials.</p>
                        </div>
                        <span class="callout-icon"><i class="fa fa-user"></i></span>
                    </div>

                    <form method="POST" action="process_account_settings.php" id="adminAccountForm">
                    <div class="row">
                        <div class="col-lg-5 col-md-12 mb-4">
                            <div class="ibox h-100 mb-0">
                                <div class="ibox-title">
                                    <h5 class="text-primary fw-bold mb-0"><i class="fa fa-id-card-o me-2"></i> Profile</h5>
                                </div>
                                <div class="ibox-content">
                                    <div class="form-group mb-3">
                                        <label>Full Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-user"></i></span>
                                            <input type="text" name="full" id="admin_full" class="form-control" value="<?php echo htmlspecialchars($userProfile['full'] ?? ($_SESSION['fullname'] ?? '')); ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label>Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                                            <input type="email" name="emp_email" id="admin_email" class="form-control" value="<?php echo htmlspecialchars($userProfile['emp_email'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7 col-md-12 mb-4">
                            <div class="ibox h-100 mb-0">
                                <div class="ibox-title">
                                    <h5 class="text-primary fw-bold mb-0"><i class="fa fa-lock me-2"></i> Account Security</h5>
                                </div>
                                <div class="ibox-content">
                                        <h4 class="mb-3 fw-bold text-dark border-bottom pb-2" style="font-size: 15px;">Login Credentials</h4>

                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <div class="form-group">
                                                    <label>Username</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fa fa-at"></i></span>
                                                        <input type="text" id="acc_username" name="username" class="form-control with-icon" value="<?php echo htmlspecialchars($userProfile['username'] ?? ''); ?>" required placeholder="Enter a unique username">
                                                        <input type="hidden" id="acc_emp_id" name="emp_id" value="<?php echo htmlspecialchars($userProfile['emp_id'] ?? ''); ?>">
                                                    </div>
                                                    <div id="usernameFeedback"></div>
                                                    <small class="form-text text-muted mt-1">This is the username you use to log in to the portal.</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="hr-line-dashed mt-4 mb-4"></div>

                                        <h4 class="mb-3 fw-bold text-dark border-bottom pb-2" style="font-size: 15px;">Change Password <span class="badge bg-light text-muted fw-normal ms-2 border" style="font-size: 11px;">Require current password</span></h4>

                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <div class="form-group">
                                                    <label>Current Password</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fa fa-unlock"></i></span>
                                                        <input type="password" id="current_password" name="current_password" class="form-control with-icon" placeholder="Enter current password to change">
                                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password', this)"><i class="fa fa-eye"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

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
                                </div>
                            </div>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>

        </div> <!-- Closes #page-wrapper from topbar.php -->
    </div> <!-- Closes #wrapper -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Sidebar toggle logic (Replaces missing inspinia.js toggle functionality on this page)
            const toggleBtn = document.querySelector('.navbar-minimalize');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.body.classList.toggle('mini-navbar');
                });
            }

            // 2. About Us Modal Trigger Logic (Bridges Bootstrap 4 data-toggle to Bootstrap 5 script)
            const aboutLink = document.querySelector('a[data-target="#aboutUsModal"]');
            if (aboutLink) {
                aboutLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    var aboutModalEl = document.getElementById('aboutUsModal');
                    if (aboutModalEl) {
                        var aboutModal = new bootstrap.Modal(aboutModalEl);
                        aboutModal.show();
                    }
                });
            }
        });

        // Validation Variables and Listeners
        var isUsernameValid = true;
        var isPasswordValid = true;
        const usernameInput = document.getElementById('acc_username');
        const usernameFeedback = document.getElementById('usernameFeedback');
        const saveBtn = document.getElementById('saveBtn');
        const empIdInput = document.getElementById('acc_emp_id');

        if (usernameInput) {
            const originalUsername = usernameInput.value.trim();
            let usernameTimeout = null;
            usernameInput.addEventListener('input', function() {
                clearTimeout(usernameTimeout);
                const currentVal = this.value.trim();
                const empId = empIdInput ? empIdInput.value : '';
                usernameFeedback.textContent = '';
                usernameInput.classList.remove('is-invalid', 'is-valid');
                usernameInput.style.borderColor = '#e3e6f0';
                if (currentVal === '') { isUsernameValid = false; checkAllValidations(); return; }
                if (currentVal === originalUsername) { isUsernameValid = true; checkAllValidations(); return; }
                usernameTimeout = setTimeout(() => {
                    const formData = new FormData();
                    formData.append('username', currentVal);
                    formData.append('emp_id', empId);
                    fetch('check_username.php', { method: 'POST', body: formData })
                    .then(r => r.json())
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
                    }).catch(err => console.error('Error checking username:', err));
                }, 500);
            });
        }

        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
        document.getElementById('new_password').addEventListener('input', checkPasswordMatch);

        function checkPasswordMatch() {
            var pwd = document.getElementById('new_password').value;
            var confirm_pwd = document.getElementById('confirm_password').value;
            var feedback = document.getElementById('pwd_match_feedback');
            var confirmInput = document.getElementById('confirm_password');
            if (confirm_pwd === '') { confirmInput.style.borderColor = '#e3e6f0'; feedback.innerHTML = ''; isPasswordValid = true; checkAllValidations(); return; }
            if (pwd === confirm_pwd) { confirmInput.style.borderColor = '#1cc88a'; feedback.innerHTML = '<span class="text-success small fw-bold"><i class="fa fa-check-circle"></i> Passwords match</span>'; isPasswordValid = true; }
            else { confirmInput.style.borderColor = '#e74a3b'; feedback.innerHTML = '<span class="text-danger small fw-bold"><i class="fa fa-times-circle"></i> Passwords do not match</span>'; isPasswordValid = false; }
            checkAllValidations();
        }

        function checkAllValidations() {
            // If changing password, require current password present
            var newPass = document.getElementById('new_password').value.trim();
            var confPass = document.getElementById('confirm_password').value.trim();
            var current = document.getElementById('current_password').value.trim();
            var needsCurrent = (newPass !== '' || confPass !== '');
            if (needsCurrent && current === '') {
                saveBtn.disabled = true;
                return;
            }
            saveBtn.disabled = !(isUsernameValid && isPasswordValid);
        }

        var accountForm = document.getElementById('adminAccountForm');
        if (accountForm) {
            accountForm.addEventListener('submit', function(e) {
                if (!isUsernameValid) { e.preventDefault(); Swal.fire({icon:'error', title:'Invalid Username', text: 'Please choose an available username.', confirmButtonColor: '#4e73df'}); usernameInput.focus(); return false; }

                var newPass = document.getElementById('new_password').value.trim();
                var confPass = document.getElementById('confirm_password').value.trim();
                var currentPass = document.getElementById('current_password').value.trim();

                if (newPass !== '' || confPass !== '') {
                    if (currentPass === '') { e.preventDefault(); Swal.fire({icon:'warning', title:'Current password required', text: 'Enter your current password to change it.', confirmButtonColor: '#4e73df'}); return false; }
                    if (newPass.length < 8) { e.preventDefault(); Swal.fire({icon:'warning', title:'Weak password', text: 'New password must be at least 8 characters.', confirmButtonColor: '#4e73df'}); return false; }
                    if (newPass !== confPass) { e.preventDefault(); Swal.fire({icon:'warning', title:'Password mismatch', text: 'New password and confirmation do not match.', confirmButtonColor: '#4e73df'}); return false; }
                }
            });
        }

        function togglePassword(inputId, btnElement) {
            var input = document.getElementById(inputId);
            var icon = btnElement.querySelector("i");
            if (input.type === "password") { input.type = "text"; icon.classList.remove("fa-eye"); icon.classList.add("fa-eye-slash"); }
            else { input.type = "password"; icon.classList.remove("fa-eye-slash"); icon.classList.add("fa-eye"); }
        }

        <?php if (isset($_GET['account_update']) && $_GET['account_update'] === 'success'): ?>
            Swal.fire({ icon: 'success', title: 'Settings Saved', text: 'Your account credentials have been successfully updated.', confirmButtonColor: '#4e73df' }).then(()=>{ window.history.replaceState({}, document.title, window.location.pathname); });
        <?php elseif (isset($_GET['account_update']) && $_GET['account_update'] === 'error'): ?>
            Swal.fire({ icon: 'error', title: 'Update Failed', text: <?php echo json_encode($_GET['reason'] ?? 'An error occurred while updating.'); ?>, confirmButtonColor: '#e74a3b' }).then(()=>{ window.history.replaceState({}, document.title, window.location.pathname); });
        <?php endif; ?>
    </script>
</body>
</html>