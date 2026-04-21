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
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $id;
                    $_SESSION['username'] = $db_username;
                    $_SESSION['fullname'] = $fullname;
                    $_SESSION['role'] = $role; 
                    
                    // Redirect based on user role from the database
                    if ($role === 'admin') {
                        header('Location: dashboard.php');
                    } else {
                        header('Location: my_attendance.php');
                    }
                    exit;
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
    <link rel="icon" type="image/png" href="img/logo/DICT.png"> 

    <style>
        /* Base page styles identical to index.php */
        body {
            background-color: #f3f3f4;
            background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('img/bg/philippine_bg.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #676a6c;
            font-family: 'Open Sans', helvetica, arial, sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* --- LANDSCAPE KIOSK STYLES --- */
        .landscape-kiosk {
            background-color: rgba(255, 255, 255, 0.98);
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.4);
            width: 100%;
            max-width: 900px;
            overflow: hidden;
        }

        /* Left Side (Branding & Clock) */
        .brand-side {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 50px 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border-right: 1px solid #e7eaec;
            text-align: center;
        }
        .brand-side h3 { font-weight: 700; margin-top: 15px; font-size: 24px; color: #2f4050; }
        .brand-side p { color: #888; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; font-size: 13px; }

        /* Live Clock styling */
        .clock-panel { margin-top: 30px; padding-top: 30px; border-top: 1px dashed #d1dade; width: 100%; }
        #live-clock { font-size: 2.2rem; font-weight: 700; color: #1ab394; line-height: 1.2; letter-spacing: -1px; }
        #live-date { font-size: 0.9rem; font-weight: 600; color: #a7b1c2; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Right Side (Form) */
        .form-side {
            padding: 50px 40px;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Form Controls */
        .form-control {
            border: 1px solid #e5e6e7;
            border-radius: 4px;
            padding: 10px 14px;
            font-size: 14px;
            box-shadow: none;
        }
        .form-control:focus { border-color: #1ab394; box-shadow: none; }

        /* Buttons */
        .btn { border-radius: 4px; font-size: 14px; font-weight: 600; padding: 10px 15px; }
        .btn-primary { background-color: #1ab394; border-color: #1ab394; color: #FFFFFF; }
        .btn-primary:hover, .btn-primary:focus, .btn-primary:active { background-color: #18a689 !important; border-color: #18a689 !important; color: #FFFFFF !important; }
        .btn-white { color: inherit; background: white; border: 1px solid #e7eaec; }
        .btn-white:hover, .btn-white:focus { color: inherit; border: 1px solid #d2d2d2; background: #f8f9fa; }

        /* Password Toggle */
        .toggle-password {
            cursor: pointer;
            color: #999c9e;
            z-index: 10;
        }
        .toggle-password:hover {
            color: #1ab394;
        }

        /* Mobile Adjustments */
        @media (max-width: 767.98px) {
            .landscape-kiosk { flex-direction: column; max-width: 450px; }
            .brand-side { border-right: none; border-bottom: 1px solid #e7eaec; padding: 30px 20px; }
            .form-side { padding: 30px 20px; }
            #live-clock { font-size: 1.8rem; }
        }
    </style>
</head>
<body>

<div class="landscape-kiosk mx-auto row g-0">
    
    <!-- LEFT SIDE: Branding & Info -->
    <div class="col-md-5 brand-side">
        <div class="logo-container">
            <img src="img/logo/DICT.png" alt="DICT Logo" class="img-fluid" style="max-height: 140px; object-fit: contain;">
        </div>
        
        <h3>DICT Monday Flag Raising</h3>
        <p>Login Page</p>

        <!-- Live Clock -->
        <div class="clock-panel">
            <div id="live-clock">00:00:00 AM</div>
            <div id="live-date">Loading Date...</div>
        </div>
    </div>

    <!-- RIGHT SIDE: Form & Actions -->
    <div class="col-md-7 form-side">
        
        <!-- Display Errors & Success Messages -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center py-2 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success text-center py-2 mb-4" role="alert">
                <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-4">
                <label class="form-label text-muted fw-bold small mb-2 text-uppercase letter-spacing-1">Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
            </div>

            <div class="mb-4">
                <label class="form-label text-muted fw-bold small mb-2 text-uppercase letter-spacing-1">Password</label>
                <div class="position-relative">
                    <input type="password" class="form-control pe-5" id="password" name="password" placeholder="Enter your password" required>
                    <span class="position-absolute top-50 end-0 translate-middle-y me-3 toggle-password" onclick="togglePassword('password', this)">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 d-block py-2 mb-4">
                <i class="bi bi-box-arrow-in-right me-1"></i> Login
            </button>
        </form>

        <hr class="my-2 text-muted" style="opacity: 0.15;">

        <!-- Switch Button -->
        <div class="mt-3">
            <a href="index.php" class="btn btn-white w-100 py-2 d-flex align-items-center justify-content-center">
                <i class="bi bi-arrow-left-circle me-2"></i> Return to Attendance Checker
            </a>
        </div>
        
    </div>
</div>

<!-- Bootstrap JS -->
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

// Live Clock Implementation (Matching index.php visually)
function updateClock() {
    const now = new Date();
    let hours = now.getHours();
    let minutes = now.getMinutes();
    let seconds = now.getSeconds();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    
    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'
    minutes = minutes < 10 ? '0' + minutes : minutes;
    seconds = seconds < 10 ? '0' + seconds : seconds;
    
    const timeString = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
    document.getElementById('live-clock').textContent = timeString;
    
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('live-date').textContent = now.toLocaleDateString('en-US', options);
}

// Initialize clock immediately, then update every second
updateClock();
setInterval(updateClock, 1000);
</script>

</body>
</html>