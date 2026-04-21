<?php
// Start the session (Assuming this page is protected for admins)


// Include your database connection
include 'connect.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $full = trim($_POST['full'] ?? '');
    $email = trim($_POST['emp_email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $area = trim($_POST['area_of_assignment'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $age = isset($_POST['age']) && $_POST['age'] !== '' ? intval($_POST['age']) : null;
    $gender = trim($_POST['gender'] ?? '');
    $status = trim($_POST['status'] ?? '');

    // Basic Validation: Ensure full name is provided
    if (empty($full)) {
        $error_msg = 'Employee Full Name is required.';
    } else {
        // Prepare the SQL INSERT query
        $sql = "INSERT INTO employees (emp_email, full, department, area_of_assignment, designation, unit, age, gender, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Bind parameters to the statement
            // 's' = string, 'i' = integer
            mysqli_stmt_bind_param($stmt, 'ssssssiss', $email, $full, $department, $area, $designation, $unit, $age, $gender, $status);
            
            // Execute the query
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = 'New employee successfully added!';
                // Optional: Clear POST data to prevent duplicate submissions on refresh
                $_POST = array(); 
            } else {
                $error_msg = 'Failed to add employee. Database error: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_msg = 'Failed to prepare statement. Database error: ' . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Oplan CeremonIX - Add Employee</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="img/logo/DICT.png">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #4e73df, #1cc88a);
            min-height: 100vh;
            background-size: 200% 200%;
            animation: gradientBG 10s ease infinite;
            padding: 2rem 0;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .form-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            background: rgba(255, 255, 255, 0.95);
        }

        .header-title {
            color: #4e73df;
            font-weight: bold;
        }

        .btn-submit { background: #1cc88a; border: none; color: white; font-weight: 500;}
        .btn-submit:hover { background: #17a673; color: white;}

        .btn-cancel { background: #858796; border: none; color: white; font-weight: 500;}
        .btn-cancel:hover { background: #717384; color: white;}
    </style>
</head>
<body>

<div class="container d-flex align-items-center justify-content-center">
    <div class="card form-card p-4 col-md-10 col-lg-8">

        <div class="text-center mb-4">
            <h3 class="header-title mt-2"><i class="bi bi-person-plus-fill"></i> Add New Employee</h3>
            <small class="text-muted">Enter the details of the new employee below.</small>
        </div>

        <!-- Display Messages -->
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger p-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success p-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>

        <!-- Add Employee Form -->
        <form method="POST" action="">
            <div class="row g-3">
                <!-- Full Name -->
                <div class="col-md-6">
                    <label for="full" class="form-label text-muted fw-bold small">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="full" name="full" placeholder="e.g., Juan Dela Cruz" value="<?php echo isset($_POST['full']) ? htmlspecialchars($_POST['full']) : ''; ?>" required>
                </div>

                <!-- Email Address -->
                <div class="col-md-6">
                    <label for="emp_email" class="form-label text-muted fw-bold small">Email Address</label>
                    <input type="email" class="form-control" id="emp_email" name="emp_email" placeholder="e.g., juan@example.com" value="<?php echo isset($_POST['emp_email']) ? htmlspecialchars($_POST['emp_email']) : ''; ?>">
                </div>

                <!-- Department -->
                <div class="col-md-6">
                    <label for="department" class="form-label text-muted fw-bold small">Department</label>
                    <input type="text" class="form-control" id="department" name="department" placeholder="e.g., AFD, TOD" value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : ''; ?>">
                </div>

                <!-- Area of Assignment Dropdown -->
                <div class="col-md-6">
                    <label for="area_of_assignment" class="form-label text-muted fw-bold small">Area of Assignment</label>
                    <select class="form-select" id="area_of_assignment" name="area_of_assignment">
                        <option value="" disabled selected>Select Area of Assignment</option>
                        <option value="Regional Office" <?php echo (isset($_POST['area_of_assignment']) && $_POST['area_of_assignment'] === 'Regional Office') ? 'selected' : ''; ?>>Regional Office</option>
                        <option value="Zamboanga City" <?php echo (isset($_POST['area_of_assignment']) && $_POST['area_of_assignment'] === 'Zamboanga City') ? 'selected' : ''; ?>>Zamboanga City</option>
                        <option value="Zamboanga Del Sur" <?php echo (isset($_POST['area_of_assignment']) && $_POST['area_of_assignment'] === 'Zamboanga Del Sur') ? 'selected' : ''; ?>>Zamboanga Del Sur</option>
                        <option value="Zamboanga Del Norte" <?php echo (isset($_POST['area_of_assignment']) && $_POST['area_of_assignment'] === 'Zamboanga Del Norte') ? 'selected' : ''; ?>>Zamboanga Del Norte</option>
                        <option value="Basilan" <?php echo (isset($_POST['area_of_assignment']) && $_POST['area_of_assignment'] === 'Basilan') ? 'selected' : ''; ?>>Basilan</option>
                        <option value="Tawi-Tawi" <?php echo (isset($_POST['area_of_assignment']) && $_POST['area_of_assignment'] === 'Tawi-Tawi') ? 'selected' : ''; ?>>Tawi-Tawi</option>
                        <option value="Sulu" <?php echo (isset($_POST['area_of_assignment']) && $_POST['area_of_assignment'] === 'Sulu') ? 'selected' : ''; ?>>Sulu</option>
                    </select>
                </div>

                <!-- Designation -->
                <div class="col-md-6">
                    <label for="designation" class="form-label text-muted fw-bold small">Designation</label>
                    <input type="text" class="form-control" id="designation" name="designation" placeholder="e.g., Planning Assistant" value="<?php echo isset($_POST['designation']) ? htmlspecialchars($_POST['designation']) : ''; ?>">
                </div>

                <!-- Unit -->
                <div class="col-md-6">
                    <label for="unit" class="form-label text-muted fw-bold small">Unit</label>
                    <input type="text" class="form-control" id="unit" name="unit" placeholder="e.g., CSB/PNPKI" value="<?php echo isset($_POST['unit']) ? htmlspecialchars($_POST['unit']) : ''; ?>">
                </div>

                <!-- Age -->
                <div class="col-md-4">
                    <label for="age" class="form-label text-muted fw-bold small">Age</label>
                    <input type="number" class="form-control" id="age" name="age" min="18" max="100" placeholder="e.g., 25" value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>">
                </div>

                <!-- Gender -->
                <div class="col-md-4">
                    <label for="gender" class="form-label text-muted fw-bold small">Gender</label>
                    <select class="form-select" id="gender" name="gender">
                        <option value="" disabled selected>Select Gender</option>
                        <option value="MALE" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'MALE') ? 'selected' : ''; ?>>Male</option>
                        <option value="FEMALE" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'FEMALE') ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>

                <!-- Employment Status -->
                <div class="col-md-4">
                    <label for="status" class="form-label text-muted fw-bold small">Employment Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="" disabled selected>Select Status</option>
                        <option value="Job Order" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Job Order') ? 'selected' : ''; ?>>Job Order</option>
                        <option value="Plantilla" <?php echo (isset($_POST['status']) && $_POST['status'] === 'Plantilla') ? 'selected' : ''; ?>>Plantilla</option>
                       
                    </select>
                </div>
            </div>

            <hr class="mt-4 mb-4">

            <!-- Form Actions -->
            <div class="d-flex justify-content-end gap-2">
                <a href="index.php" class="btn btn-cancel py-2 px-4">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
                <button type="submit" class="btn btn-submit py-2 px-4">
                    <i class="bi bi-save"></i> Save Employee
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>