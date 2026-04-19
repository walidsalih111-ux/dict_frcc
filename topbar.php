<div id="page-wrapper" class="gray-bg">
  <!-- Added 'sticky-top', 'bg-white', and a 'z-index' to keep the top bar locked when scrolling -->
  <div class="row border-bottom sticky-top bg-white" style="z-index: 1030;">
    <nav class="navbar white-bg px-3 d-flex align-items-center w-100" role="navigation" style="margin-bottom: 0">
      
      <!-- Sidebar Toggle Button -->
      <div class="navbar-header d-flex align-items-center">
          <a class="navbar-minimalize minimalize-styl-2 btn text-white shadow-sm me-3" href="#" style="background-color: #1cc88a; border: none; border-radius: 5px;">
              <i class="fa fa-bars"></i>
          </a>
      </div>

      <!-- Right Aligned Links (BS5 uses ms-auto instead of navbar-right) -->
      <ul class="nav navbar-top-links ms-auto d-flex align-items-center mb-0">
          <!-- Added d-none d-lg-block to keep the navbar responsive on mobile devices -->
          <li class="nav-item pe-4 d-none d-lg-block">
              <span class="text-muted welcome-message fw-semibold">
                  <i class="fa fa-clipboard-check text-success me-1"></i> DICT Monday Flag Raising Attendance and Compliance Checker
              </span>
          </li>
          
          <!-- Quick Log Out button for the top bar -->
          <li class="nav-item">
              <a class="nav-link text-secondary fw-medium profile-nav-link" href="#" id="logout-btn">
                  <i class="fa fa-sign-out-alt"></i> Log out
              </a>
          </li>
      </ul>

    </nav>
  </div>
  
  <script>
    // Keep track of sidebar toggle state in local storage
    document.addEventListener("DOMContentLoaded", function() {
        const toggleBtn = document.querySelector('.navbar-minimalize');
        if(toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                // Wait a tiny bit (50ms) for the body class to be toggled by the template's main JS (inspinia.js)
                setTimeout(function() {
                    if (document.body.classList.contains('mini-navbar')) {
                        localStorage.setItem('sidebar_state', 'minimized');
                    } else {
                        localStorage.setItem('sidebar_state', 'expanded');
                    }
                }, 50);
            });
        }
    });
  </script>