<?php
// Ensure session is started to access $_SESSION variables
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and the session variables from index.php exist
if (isset($_SESSION['fullname']) && !empty($_SESSION['fullname'])) {
    $userFullName = $_SESSION['fullname'];
    $userRole = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : "User";
} elseif (isset($_SESSION['username'])) {
    $userFullName = $_SESSION['username'];
    $userRole = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : "User";
} else {
    $userFullName = "Guest";
    $userRole = "Unknown";
}
?>

<style>
  .locked-sidebar {
    position: fixed !important;
    top: 0;      /* Anchors the sidebar to the top of the viewport */
    left: 0;     /* Anchors the sidebar to the left of the viewport */
    height: 100vh;
    overflow-y: auto; /* Allows scrolling INSIDE the sidebar if the menu gets too long */
    z-index: 2000;
  }

  /* Adds a visual separator and spacing for the about us item */
  .nav-about {
    border-top: 1px solid #2f4050;
    margin-top: 15px;
  }

  /* Profile image styling */
  .profile-logo {
      width: 60px;
      height: 60px;
      object-fit: cover;
      margin-bottom: 10px;
      border: 2px solid #1ab394;
  }
  .mini-logo {
      width: 40px;
      height: 40px;
      object-fit: cover;
  }
</style>

<nav class="navbar-default navbar-static-side locked-sidebar" role="navigation">
  <div class="sidebar-collapse">
    <ul class="nav metismenu" id="side-menu">
      <li class="nav-header" style="text-align: center;">
        <div class="dropdown profile-element">
          <img alt="image" class="rounded-circle profile-logo" src="img/logo/DICT.png" onerror="this.src='img/logo/DICT.png';" />
          <div class="m-t-sm">
            <span class="block font-weight-bold" style="font-size: 14px; color: #dfe4ed;">
              <?php echo htmlspecialchars($userFullName); ?>
            </span>
            <span class="text-xs block" style="color: #8095a8; margin-top: 2px;">
              <?php echo htmlspecialchars($userRole); ?>
            </span>
          </div>
        </div>
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

      <li class="nav-about">
        <a href="#" data-toggle="modal" data-target="#aboutUsModal">
          <i class="fa fa-info-circle"></i> <span class="nav-label">About Us</span>
        </a>
      </li>
    </ul>
  </div>
</nav>

<?php include 'about_us.php'; ?>