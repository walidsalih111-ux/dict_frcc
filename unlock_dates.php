<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

include 'connect.php';

// Ensure the unlocked_dates table exists so the page always works
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS unlocked_dates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        target_date DATE UNIQUE
    )");
} catch (PDOException $e) {
    // ignore table initialization errors
}
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>DICT Monday Flag Raising | Unlock Dates</title>

    <link href="css/bootstrap.min.css" rel="stylesheet" />
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="css/animate.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />

    <style>
        body.gray-bg, #page-wrapper, .wrapper.wrapper-content {
            background: linear-gradient(135deg, #4e73df, #1cc88a) !important;
            background-size: 200% 200% !important;
            animation: gradientBG 10s ease infinite !important;
        }
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .ibox {
            border-radius: 15px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
            background: rgba(255, 255, 255, 0.95) !important;
            border: none !important;
            margin-bottom: 25px;
        }
        .ibox-title { background: transparent !important; border-bottom: 1px solid rgba(0,0,0,0.05) !important; border-radius: 15px 15px 0 0 !important; padding: 15px 20px; }
        .ibox-content { border-radius: 0 0 15px 15px !important; background: transparent !important; }
        .navbar-static-top { background: rgba(255,255,255,0.95) !important; box-shadow: 0 2px 10px rgba(0,0,0,0.05) !important; border-bottom: none !important; }
        .table tbody tr:hover { background: rgba(26, 179, 148, 0.08); }
        .form-control:focus { box-shadow: none; border-color: #1ab394; }
    </style>
  </head>

  <body>
    <div id="wrapper">
      <nav class="navbar-default navbar-static-side" role="navigation">
        <div class="sidebar-collapse">
          <ul class="nav metismenu" id="side-menu">
            <?php include 'sidebar.php'; ?>
          </ul>
        </div>
      </nav>

      <?php include 'topbar.php'; ?>

      <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row">
          <div class="col-lg-10 offset-lg-1">
            <div class="ibox ">
              <div class="ibox-title">
                <h5>Unlock Attendance Dates</h5>
                <div class="ibox-tools">
                  <small class="text-muted">Grant employees access on special dates</small>
                </div>
              </div>
              <div class="ibox-content">
                <p class="mb-4">Use this panel to unlock specific dates when attendance is normally closed. Employees can sign in on an unlocked date even if it is not a Monday.</p>

                <form id="unlock-form" class="row g-3 align-items-end">
                  <div class="col-md-4">
                    <label for="unlock_date" class="form-label">Date to Unlock</label>
                    <input type="date" id="unlock_date" name="unlock_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                  </div>
                  <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">Unlock Date</button>
                  </div>
                  <div class="col-md-6 align-self-start">
                    <div class="alert alert-info mb-0" role="alert" style="font-size: 0.95rem;">
                      <i class="fa fa-info-circle"></i> Only admin users can add or remove unlocked dates.
                    </div>
                  </div>
                </form>

                <div class="mt-4">
                  <h6>Currently unlocked dates</h6>
                  <div class="table-responsive mt-3">
                    <table class="table table-bordered table-hover">
                      <thead>
                        <tr>
                          <th style="width: 70%;">Unlocked Date</th>
                          <th class="text-center" style="width: 30%;">Action</th>
                        </tr>
                      </thead>
                      <tbody id="unlocked-dates-body">
                        <tr>
                          <td colspan="2" class="text-center text-muted py-4">Loading unlocked dates...</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="js/jquery-3.1.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.js"></script>

    <!-- Added missing Inspinia scripts for the sidebar toggle to work -->
    <script src="js/plugins/metisMenu/jquery.metisMenu.js"></script>
    <script src="js/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="js/inspinia.js"></script>
    <script src="js/plugins/pace/pace.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/jquery-3.1.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.js"></script>
    
    <script>
      function renderUnlockedDates(dates) {
        var tbody = $('#unlocked-dates-body');
        if (!dates || dates.length === 0) {
          tbody.html('<tr><td colspan="2" class="text-center text-muted py-4">No unlocked dates yet.</td></tr>');
          return;
        }

        var rows = dates.map(function(date) {
          return '<tr>' +
                 '<td>' + date + '</td>' +
                 '<td class="text-center">' +
                 '<button class="btn btn-danger btn-sm btn-lock-date" data-date="' + date + '">' +
                 '<i class="fa fa-lock"></i> Remove</button>' +
                 '</td>' +
                 '</tr>';
        }).join('');

        tbody.html(rows);
      }

      function fetchUnlockedDates() {
        $.ajax({
          url: 'unlock_handler.php',
          method: 'POST',
          dataType: 'json',
          data: { action: 'fetch' }
        }).done(function(response) {
          if (response.success) {
            renderUnlockedDates(response.dates);
          } else {
            $('#unlocked-dates-body').html('<tr><td colspan="2" class="text-center text-danger py-4">Unable to load unlocked dates.</td></tr>');
          }
        }).fail(function() {
          $('#unlocked-dates-body').html('<tr><td colspan="2" class="text-center text-danger py-4">Server error while loading dates.</td></tr>');
        });
      }

      $(document).ready(function() {
        fetchUnlockedDates();

        // Logout SweetAlert Confirmation
        $('#logout-btn').on('click', function(e) {
            e.preventDefault(); // Prevent default link behavior
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You will be logged out of the system.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, log out!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
                }
            });
        });

        $('#unlock-form').on('submit', function(e) {
          e.preventDefault();
          var selectedDate = $('#unlock_date').val();
          if (!selectedDate) {
            return;
          }

          $.ajax({
            url: 'unlock_handler.php',
            method: 'POST',
            dataType: 'json',
            data: { action: 'unlock', date: selectedDate }
          }).done(function(response) {
            if (response.success) {
              Swal.fire({ icon: 'success', title: 'Unlocked', text: response.message, timer: 1800, showConfirmButton: false });
              $('#unlock_date').val('');
              fetchUnlockedDates();
            } else {
              Swal.fire({ icon: 'error', title: 'Error', text: response.message });
            }
          }).fail(function() {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Unable to reach unlock service.' });
          });
        });

        $(document).on('click', '.btn-lock-date', function() {
          var dateValue = $(this).data('date');
          if (!dateValue) {
            return;
          }

          Swal.fire({
            title: 'Remove unlocked date?',
            text: 'Employees will no longer be able to sign in on ' + dateValue + '.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, remove it',
            cancelButtonText: 'Cancel'
          }).then(function(result) {
            if (result.isConfirmed) {
              $.ajax({
                url: 'unlock_handler.php',
                method: 'POST',
                dataType: 'json',
                data: { action: 'lock', date: dateValue }
              }).done(function(response) {
                if (response.success) {
                  Swal.fire({ icon: 'success', title: 'Removed', text: response.message, timer: 1600, showConfirmButton: false });
                  fetchUnlockedDates();
                } else {
                  Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                }
              }).fail(function() {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Unable to reach unlock service.' });
              });
            }
          });
        });
      });
    </script>
  </body>
</html>