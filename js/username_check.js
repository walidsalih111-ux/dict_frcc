$(function () {
  // --- CONFIGURE IDs ---
  var ADD_INPUT = "#addUsername";
  var ADD_MSG = "#addUsernameMsg";
  var ADD_SUBMIT = "#addEmployeeSubmitBtn";

  var EDIT_INPUT = "#editUsername";
  var EDIT_MSG = "#editUsernameMsg";
  var EDIT_SUBMIT = "#editEmployeeSubmitBtn";
  var EDIT_EMP_ID = "#editEmpId";

  var checkDelay = 450;
  var addTimer, editTimer;

  function setFeedback($msg, $input, available) {
    if (available) {
      $msg
        .html(
          '<span class="text-success" style="font-weight: 500;"><i class="fa fa-check-circle"></i> Username is available.</span>',
        )
        .show();
      $input.removeClass("is-invalid").addClass("is-valid");
    } else {
      $msg
        .html(
          '<span class="text-danger" style="font-weight: 500;"><i class="fa fa-times-circle"></i> Username is already taken.</span>',
        )
        .show();
      $input.removeClass("is-valid").addClass("is-invalid");
    }
  }

  function clearFeedback($msg, $input) {
    $msg.hide().html("");
    $input.removeClass("is-valid is-invalid");
  }

  function checkUsername(username, empId, $input, $msg, $submitBtn) {
    if (!username) {
      clearFeedback($msg, $input);
      $submitBtn.prop("disabled", false);
      return;
    }

    $.ajax({
      url: "check_username.php",
      method: "POST",
      data: { username: username, emp_id: empId || "" },
      dataType: "json",
      success: function (res) {
        setFeedback($msg, $input, res.available);
        $submitBtn.prop("disabled", !res.available);
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", status, error);
        $msg
          .html(
            '<span class="text-warning"><i class="fa fa-exclamation-triangle"></i> Validation error. Check console.</span>',
          )
          .show();
        $submitBtn.prop("disabled", true);
      },
    });
  }

  // Add Employee Modal Trigger
  $(document).on("input", ADD_INPUT, function () {
    clearTimeout(addTimer);
    var val = $(this).val().trim();
    var $msg = $(ADD_MSG);
    var $btn = $(ADD_SUBMIT);

    if (!val) {
      clearFeedback($msg, $(ADD_INPUT));
      $btn.prop("disabled", false);
      return;
    }

    addTimer = setTimeout(function () {
      checkUsername(val, null, $(ADD_INPUT), $msg, $btn);
    }, checkDelay);
  });

  // Edit Employee Modal Trigger
  $(document).on("input", EDIT_INPUT, function () {
    clearTimeout(editTimer);
    var val = $(this).val().trim();
    var empId = $(EDIT_EMP_ID).val();
    var $msg = $(EDIT_MSG);
    var $btn = $(EDIT_SUBMIT);

    if (!val) {
      clearFeedback($msg, $(EDIT_INPUT));
      $btn.prop("disabled", false);
      return;
    }

    editTimer = setTimeout(function () {
      checkUsername(val, empId, $(EDIT_INPUT), $msg, $btn);
    }, checkDelay);
  });

  // Clear states when modals close
  $("#addEmployeeModal").on("hidden.bs.modal", function () {
    clearFeedback($(ADD_MSG), $(ADD_INPUT));
    $(ADD_SUBMIT).prop("disabled", false);
  });

  $("#editEmployeeModal").on("hidden.bs.modal", function () {
    clearFeedback($(EDIT_MSG), $(EDIT_INPUT));
    $(EDIT_SUBMIT).prop("disabled", false);
  });
});
