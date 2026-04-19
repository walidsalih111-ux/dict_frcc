$(document).ready(function () {
  // ==========================================
  // Handle URL Parameters for Alerts
  // ==========================================
  const urlParams = new URLSearchParams(window.location.search);
  const msg = urlParams.get("msg");
  const error = urlParams.get("error");

  if (msg === "add_success") {
    Swal.fire({
      icon: "success",
      title: "Added Successfully!",
      text: "The new employee was successfully added.",
      confirmButtonColor: "#1cc88a",
    });
    window.history.replaceState({}, document.title, window.location.pathname);
  } else if (msg === "edit_success") {
    Swal.fire({
      icon: "success",
      title: "Updated Successfully!",
      text: "The employee record was successfully updated.",
      confirmButtonColor: "#1cc88a",
    });
    window.history.replaceState({}, document.title, window.location.pathname);
  } else if (error === "duplicate_employee") {
    Swal.fire({
      icon: "error",
      title: "Duplicate Found!",
      text: "An employee with this exact Name or Email already exists in the system.",
      confirmButtonColor: "#e74a3b",
    });
    window.history.replaceState({}, document.title, window.location.pathname);
  } else if (error === "duplicate_username") {
    Swal.fire({
      icon: "error",
      title: "Username Taken!",
      text: "The user account could not be created because the username is already taken. Please try another.",
      confirmButtonColor: "#e74a3b",
    });
    window.history.replaceState({}, document.title, window.location.pathname);
  } else if (error === "db_error") {
    Swal.fire({
      icon: "error",
      title: "Database Error!",
      text: "An unexpected error occurred while saving. Please try again.",
      confirmButtonColor: "#e74a3b",
    });
    window.history.replaceState({}, document.title, window.location.pathname);
  } else if (error === "admin_limit") {
    Swal.fire({
      icon: "warning",
      title: "Admin Limit Reached!",
      html: "You cannot assign more than <strong>3 admins</strong>.<br>Please remove an existing admin before assigning a new one.",
      confirmButtonColor: "#f6c23e",
      confirmButtonText: "Got it",
    });
    window.history.replaceState({}, document.title, window.location.pathname);
  }

  // ==========================================
  // Initialize DataTables
  // ==========================================
  var table = $(".dataTables-example").DataTable({
    pageLength: 10,
    responsive: true,
    lengthMenu: [
      [10, 25, 50, -1],
      [10, 25, 50, "All"],
    ],
  });

  // ==========================================
  // Column Filters
  // ==========================================

  // Filter by Department (Column index 2)
  $("#filter-department").on("change", function () {
    table.column(2).search(this.value).draw();
  });

  // Filter by Area of Assignment (Column index 3)
  $("#filter-area").on("change", function () {
    table.column(3).search(this.value).draw();
  });

  // Filter by Status (Column index 5)
  $("#filter-status").on("change", function () {
    table.column(5).search(this.value).draw();
  });

  // Filter by Role (Column index 6)
  $("#filter-role").on("change", function () {
    table.column(6).search(this.value).draw();
  });

  // ==========================================
  // Account Status Button Handler (Activate / Deactivate)
  // ==========================================
  $(document).on("click", ".status-btn", function (e) {
    e.preventDefault();
    const empId = $(this).data("id");
    const accountStatus = $(this).data("account-status");
    const empName = $(this).closest("tr").find("td:eq(0)").text();
    const actionLabel = accountStatus === "active" ? "Deactivate" : "Activate";
    const confirmText =
      accountStatus === "active"
        ? `Are you sure you want to deactivate ${empName}'s user account?`
        : `${empName} does not currently have an account. Please add a user account through the Edit modal.`;

    if (accountStatus === "inactive") {
      const editBtn = $(this).closest("td").find(".edit-btn");
      if (editBtn.length) {
        editBtn.trigger("click");
      }
      return;
    }

    Swal.fire({
      title: `${actionLabel} Account?`,
      text: confirmText,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#f6c23e",
      cancelButtonColor: "#6c757d",
      confirmButtonText: `${actionLabel}`,
      cancelButtonText: "Cancel",
    }).then((result) => {
      if (result.isConfirmed) {
        Swal.fire({
          title: `${actionLabel}ing...`,
          text: "Please wait while we update the account status.",
          icon: "info",
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          didOpen: () => {
            Swal.showLoading();
          },
        });

        $.ajax({
          url: "process_account_status.php",
          type: "POST",
          dataType: "json",
          data: { emp_id: empId, action: "deactivate" },
          timeout: 10000,
          success: function (result) {
            if (result && result.status === "success") {
              Swal.fire({
                icon: "success",
                title: "Updated!",
                text: result.message,
                confirmButtonColor: "#1cc88a",
                timer: 2000,
                showConfirmButton: false,
              }).then(() => location.reload());
            } else {
              Swal.fire({
                icon: "error",
                title: "Error",
                text:
                  result && result.message
                    ? result.message
                    : "Invalid response from server.",
                confirmButtonColor: "#e74a3b",
              });
            }
          },
          error: function (xhr, status, error) {
            let errorMessage =
              "Failed to update account status. Please try again.";
            if (status === "timeout") {
              errorMessage =
                "Request timed out. Please check your connection and try again.";
            } else if (xhr.status === 0) {
              errorMessage =
                "Unable to connect to server. Please check your internet connection.";
            } else if (xhr.status >= 500) {
              errorMessage = "Server error occurred. Please try again later.";
            }
            Swal.fire({
              icon: "error",
              title: "Error",
              text: errorMessage,
              confirmButtonColor: "#e74a3b",
            });
            console.error("Account status error:", error);
          },
        });
      }
    });
  });

  // ==========================================
  // SweetAlert Logout Confirmation
  // ==========================================
  $("#logout-btn").on("click", function (e) {
    e.preventDefault();
    Swal.fire({
      title: "Are you sure?",
      text: "You will be logged out of your current session.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#1cc88a",
      cancelButtonColor: "#e74a3b",
      confirmButtonText: "Yes, log out!",
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = "logout.php";
      }
    });
  });

  // ==========================================
  // Password Matching Validation
  // ==========================================
  
  // Real-time validation for Add Modal
  $('#add_modal_password, #add_modal_confirm_password').on('keyup', function () {
    const password = $('#add_modal_password').val();
    const confirm = $('#add_modal_confirm_password').val();
    
    if (confirm && password !== confirm) {
        $('#add_modal_confirm_password').addClass('is-invalid');
    } else {
        $('#add_modal_confirm_password').removeClass('is-invalid');
    }
  });

  // Real-time validation for Edit Modal
  $('#edit_password, #edit_confirm_password').on('keyup', function () {
    const password = $('#edit_password').val();
    const confirm = $('#edit_confirm_password').val();
    
    // Only validate if user is trying to change the password
    if (password !== '' && confirm !== '' && password !== confirm) {
        $('#edit_confirm_password').addClass('is-invalid');
    } else {
        $('#edit_confirm_password').removeClass('is-invalid');
    }
  });

  // Prevent Add Form Submission if passwords don't match
  $('#addEmployeeForm').on('submit', function (e) {
    const password = $('#add_modal_password').val();
    const confirm = $('#add_modal_confirm_password').val();
    
    if (password !== confirm) {
        e.preventDefault();
        $('#add_modal_confirm_password').addClass('is-invalid');
        Swal.fire({
            icon: 'error',
            title: 'Wait!',
            text: 'Your passwords do not match. Please re-type them.',
            confirmButtonColor: '#e74a3b'
        });
    }
  });

  // Prevent Edit Form Submission if passwords don't match
  $('#editEmployeeForm').on('submit', function (e) {
    const password = $('#edit_password').val();
    const confirm = $('#edit_confirm_password').val();
    
    if (password !== '' && password !== confirm) {
        e.preventDefault();
        $('#edit_confirm_password').addClass('is-invalid');
        Swal.fire({
            icon: 'error',
            title: 'Wait!',
            text: 'Your new passwords do not match. Please re-type them.',
            confirmButtonColor: '#e74a3b'
        });
    }
  });
});