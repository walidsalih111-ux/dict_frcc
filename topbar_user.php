<div id="page-wrapper" class="gray-bg">
  <!-- Added 'sticky-top', 'bg-white', and a 'z-index' to keep the top bar locked when scrolling -->
  <div class="row border-bottom sticky-top bg-white" style="z-index: 1030;">
    <nav class="navbar white-bg px-3 d-flex align-items-center w-100" role="navigation" style="margin-bottom: 0; min-height: 60px;">
      
      <!-- Sidebar Toggle Button -->
      <div class="navbar-header d-flex align-items-center">
          <a class="navbar-minimalize minimalize-styl-2 btn text-white shadow-sm me-3" href="#" style="background-color: #4e73df; border: none; border-radius: 5px; padding: 6px 12px;">
              <i class="fa fa-bars"></i>
          </a>
      </div>

      <!-- Right Aligned Links -->
      <ul class="nav navbar-top-links ms-auto d-flex align-items-center mb-0 list-unstyled">
          <li class="nav-item pe-4 d-none d-lg-block">
              <span class="text-muted welcome-message fw-semibold" style="font-size: 14px;">
                  <i class="fa fa-clipboard-check text-success me-1"></i> DICT Monday Flag Raising Attendance
              </span>
          </li>
          
          <!-- Quick Log Out button for the top bar -->
          <li class="nav-item">
              <a class="nav-link text-danger fw-bold d-flex align-items-center profile-nav-link" href="#" id="logoutBtn" style="padding: 20px 15px;">
                  <i class="fa fa-sign-out me-1 fs-5"></i> Log out
              </a>
          </li>
      </ul>

    </nav>
  </div>
  
  <script>
    // Keep track of sidebar toggle state in local storage (Vanilla JS support)
    document.addEventListener("DOMContentLoaded", function() {
        const toggleBtn = document.querySelector('.navbar-minimalize');
        if(toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Toggle the class on the body explicitly for standard UI responsiveness 
                document.body.classList.toggle('mini-navbar');
                
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