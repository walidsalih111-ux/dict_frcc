<?php
session_start();

// If the user is already logged in, redirect them to their respective pages automatically
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: dashboard.php');
        exit;
    } else {
        header('Location: my_attendance.php');
        exit;
    }
}

include 'connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ==========================================
    // LOGIN LOGIC
    // ==========================================
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $attempted_role = isset($_POST['login_role']) ? $_POST['login_role'] : '';

    if ($username === '' || $password === '') {
        $error = 'Please enter username and password.';
    } else {
        // Used a JOIN to get the 'full' name from the employees table and alias it as 'fullname'
        $sql = "SELECT u.id, u.username, u.password, e.full as fullname, u.role 
                FROM user_account u 
                LEFT JOIN employees e ON u.emp_id = e.emp_id 
                WHERE u.username = ? LIMIT 1";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            
            // Bind the variables to the result
            mysqli_stmt_bind_result($stmt, $id, $db_username, $db_password, $fullname, $role);
            
            if (mysqli_stmt_fetch($stmt)) {
                $verified = false;
                
                // Check password securely or with plain text fallback
                if (is_string($db_password) && password_verify($password, $db_password)) {
                    $verified = true;
                } elseif ($password === $db_password) {
                    $verified = true;
                }

                if ($verified) {
                    // Make sure the user is logging in through the right portal tab
                    if ($role !== $attempted_role) {
                        $error = 'Access denied: Please use the correct login portal for your role.';
                    } else {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $id;
                        $_SESSION['username'] = $db_username;
                        $_SESSION['fullname'] = $fullname;
                        $_SESSION['role'] = $role; 
                        
                        // Redirect based on user role
                        if ($role === 'admin') {
                            header('Location: dashboard.php');
                        } else {
                            header('Location: my_attendance.php');
                        }
                        exit;
                    }
                } else {
                    $error = 'Invalid username or password.';
                }
            } else {
                $error = 'Invalid username or password.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = 'Database error: ' . mysqli_error($conn); 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DICT Monday Flag Raising - Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts: Open Sans for Inspinia Typography -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        /* Inspinia Theme Core Variables & Styles */
        body {
            background-color: #f3f3f4; /* Fallback color */
            /* Added a linear-gradient overlay with 60% opacity black to darken the background image */
            background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('img/bg/philippine_bg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #676a6c;
            font-family: 'Open Sans', helvetica, arial, sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .loginscreen.middle-box {
            width: 360px;
            max-width: 90%;
            z-index: 100;
            text-align: center;
            /* Added a subtle background and padding to ensure text is readable over the image */
            background-color: rgba(255, 255, 255, 0.85);
            padding: 30px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        /* Adjusted for the new image logo */
        .logo-container {
            margin-bottom: 15px;
        }

        h3 {
            font-weight: 600;
            margin-top: 10px;
            font-size: 24px;
            color: #333; /* Darker text for better contrast */
        }

        .ibox-content {
            background-color: #ffffff;
            color: inherit;
            padding: 25px 20px 20px 20px;
            border-color: #e7eaec;
            border-image: none;
            border-style: solid solid none;
            border-width: 1px 0;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            text-align: left;
        }

        /* Form Controls */
        .form-control {
            border: 1px solid #e5e6e7;
            border-radius: 2px;
            padding: 8px 12px;
            font-size: 14px;
            box-shadow: none;
            transition: border-color 0.15s ease-in-out 0s;
        }
        .form-control:focus {
            border-color: #1ab394;
            box-shadow: none;
        }

        /* Buttons */
        .btn {
            border-radius: 3px;
            font-size: 14px;
            font-weight: 600;
            padding: 8px 12px;
        }
        
        .btn-primary { /* Inspinia Teal */
            background-color: #1ab394;
            border-color: #1ab394;
            color: #FFFFFF;
        }
        .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
            background-color: #18a689 !important;
            border-color: #18a689 !important;
            color: #FFFFFF !important;
        }

        .btn-info { /* Inspinia Blue for Admin */
            background-color: #1c84c6;
            border-color: #1c84c6;
            color: #FFFFFF;
        }
        .btn-info:hover, .btn-info:focus, .btn-info:active {
            background-color: #1a7bb9 !important;
            border-color: #1a7bb9 !important;
            color: #FFFFFF !important;
        }

        .btn-white {
            color: inherit;
            background: white;
            border: 1px solid #e7eaec;
        }
        .btn-white:hover, .btn-white:focus {
            color: inherit;
            border: 1px solid #d2d2d2;
            background: #f8f9fa;
        }

        /* Custom Tabs */
        .nav-tabs {
            border-bottom: 1px solid #e7eaec;
            margin-bottom: 20px;
        }
        .nav-tabs .nav-link {
            color: #a7b1c2;
            border: none;
            font-weight: 600;
            padding: 8px 15px;
            font-size: 15px;
        }
        .nav-tabs .nav-link:hover {
            color: #676a6c;
            border-color: transparent;
        }
        .nav-tabs .nav-link.active {
            color: #676a6c;
            background-color: transparent;
            border-bottom: 2px solid #1ab394;
        }

        /* Utilities */
        .toggle-password {
            cursor: pointer;
            color: #999c9e;
            z-index: 10;
        }
        .toggle-password:hover {
            color: #1ab394;
        }
        .alert {
            border-radius: 3px;
            font-size: 13px;
            padding: 10px;
        }
    </style>
</head>
<body>

<div class="middle-box loginscreen">
    
    <!-- Updated Logo Section -->
    <div class="logo-container">
        <img src="img/logo/DICT.png" alt="DICT Logo" class="img-fluid" style="max-height: 130px; object-fit: contain;">
    </div>
    
    <h3>DICT Monday Flag Raising</h3>
    <p class="text-muted">Attendance Checker</p>

    <!-- Display Errors & Success Messages -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success text-center" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="ibox-content mb-3">
        
        <!-- Role Selection Tabs -->
        <ul class="nav nav-tabs nav-justified" id="loginTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="user-tab" data-bs-toggle="tab" data-bs-target="#user-form" type="button" role="tab">User</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="admin-tab" data-bs-toggle="tab" data-bs-target="#admin-form" type="button" role="tab">Admin</button>
            </li>
        </ul>

        <div class="tab-content" id="loginTabsContent">
            
            <!-- ================= USER LOGIN FORM ================= -->
            <div class="tab-pane fade show active" id="user-form" role="tabpanel">
                <form method="POST" action="">
                    <input type="hidden" name="login_role" value="user">

                    <div class="mb-3">
                        <input type="text" class="form-control" id="user_username" name="username" placeholder="Username" required>
                    </div>

                    <div class="mb-4 position-relative">
                        <input type="password" class="form-control pe-5" id="user_password" name="password" placeholder="Password" required>
                        <span class="position-absolute top-50 end-0 translate-middle-y me-3 toggle-password" onclick="togglePassword('user_password', this)">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 d-block">
                        Login
                    </button>
                </form>
            </div>

            <!-- ================= ADMIN LOGIN FORM ================= -->
            <div class="tab-pane fade" id="admin-form" role="tabpanel">
                <form method="POST" action="">
                    <input type="hidden" name="login_role" value="admin">

                    <div class="mb-3">
                        <input type="text" class="form-control" id="admin_username" name="username" placeholder="Admin Username" required>
                    </div>

                    <div class="mb-4 position-relative">
                        <input type="password" class="form-control pe-5" id="admin_password" name="password" placeholder="Admin Password" required>
                        <span class="position-absolute top-50 end-0 translate-middle-y me-3 toggle-password" onclick="togglePassword('admin_password', this)">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div>

                    <button type="submit" class="btn btn-info w-100 d-block">
                        Admin Login
                    </button>
                </form>
            </div>

        </div>
    </div>

    <!-- Switch Button -->
    <a href="attendance_checker.php" class="btn btn-white w-100">
        <i class="bi bi-arrow-repeat me-1"></i> Switch to Weekly Attendance
    </a>

</div>

<!-- Bootstrap JS needed for Tabs -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Toggle Password Visibility
function togglePassword(inputId, iconElement) {
    const pass = document.getElementById(inputId);
    const icon = iconElement.querySelector("i");

    if (pass.type === "password") {
        pass.type = "text";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
    } else {
        pass.type = "password";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    }
}
</script>

</body>
</html>