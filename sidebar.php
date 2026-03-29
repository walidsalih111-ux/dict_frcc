<?php
// Ensure session is started to access $_SESSION variables

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and the session variables from index.php exist
if (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
    // Grab the name and role directly from the session
    $userFullName = $_SESSION['fullname'];
    $userRole = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : "User";
} 
// Fallback just in case the full name session is missing but the username exists
elseif (isset($_SESSION['username'])) {
    $userFullName = $_SESSION['username'];
    $userRole = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : "User";
} 
// Final fallback if no one is properly logged in
else {
    $userFullName = "Guest";
    $userRole = "Unknown";
}
?>

<style>
  .locked-sidebar {
    position: fixed !important;
    height: 100vh;
    overflow-y: auto;
    z-index: 2000;
  }
  
  /* Adds a visual separator and spacing for the about us item */
  .nav-about {
    border-top: 1px solid #2f4050;
    margin-top: 15px;
  }

  /* Profile image styling to ensure non-square logos fit well inside the circle */
  .profile-logo {
    width: 50px;
    height: 50px;
    object-fit: contain;
    background-color: #ffffff; /* White background in case the logo is transparent */
    padding: 2px;
  }
  
  /* Mini logo styling for when the sidebar is minimized */
  .mini-logo {
    width: 35px;
    height: 35px;
    object-fit: contain;
    background-color: #ffffff;
    padding: 2px;
    margin: 0 auto;
  }
  
  /* Adjust the container when replacing text with an image */
  .logo-element {
    background-color: transparent !important;
    padding: 15px 0 !important;
  }
</style>

<nav class="navbar-default navbar-static-side locked-sidebar" role="navigation">
  <div class="sidebar-collapse">
    <ul class="nav metismenu" id="side-menu">
      <li class="nav-header">
        <div class="profile-element text-center">
          <img alt="image" class="rounded-circle profile-logo" src="img/logo/DICT.png" />
          <div class="m-t-sm">
            <span class="block font-weight-bold" style="font-size: 14px; color: #dfe4ed;">
              <?php echo htmlspecialchars($userFullName); ?>
            </span>
            <span class="text-xs block" style="color: #8095a8; margin-top: 2px;">
              <?php echo htmlspecialchars($userRole); ?>
            </span>
          </div>
        </div>
        <!-- Replaced "OC" text with the image element below -->
        <div class="logo-element">
          <img alt="image" class="rounded-circle mini-logo" src="img/logo/DICT.png" />
        </div>
      </li>

      <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
        <a href="dashboard.php"><i class="fa fa-th-large"></i> <span class="nav-label">Dashboard</span></a>
      </li>

      <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'data_table.php') ? 'active' : ''; ?>">
        <a href="data_table.php"><i class="fa fa-table"></i> <span class="nav-label">Data Table</span></a>
      </li>

      <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'employee_management.php') ? 'active' : ''; ?>">
        <a href="employee_management.php"><i class="fa fa-users"></i> <span class="nav-label">Employee Management</span></a>
      </li>

      <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'about_us.php') ? 'active' : ''; ?> nav-about">
        <a href="about_us.php">
          <i class="fa fa-info-circle"></i> <span class="nav-label">About Us</span>
        </a>
      </li>
    </ul>
  </div>
</nav>