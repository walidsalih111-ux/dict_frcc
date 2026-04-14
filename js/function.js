$(document).ready(function() {
    
    // Inject CSS directly to guarantee SweetAlert2 is above any Bootstrap/Inspinia modal
   $('<style>')
        .prop('type', 'text/css')
        .html('.swal2-container, .swal2-container.swal2-backdrop-show { z-index: 999999 !important; }')
        .appendTo('head');

    // ==========================================
    // 1. DELETE CONFIRMATION ALERT
    // ==========================================
    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault(); // Prevent accidental navigation
        var empId = $(this).data('id'); // Get the employee ID from data-id attribute
        var row = $(this).closest('tr'); // Get the table row so we can remove it later

        // Show SweetAlert2 confirmation
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this! This will also delete their user account.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74a3b', // Matches btn-danger
            cancelButtonColor: '#858796',  // Secondary grey
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Send the POST request via AJAX
                $.ajax({
                    url: 'process_delete.php',
                    type: 'POST',
                    data: { id: empId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire(
                                'Deleted!',
                                response.message,
                                'success'
                            );
                            // Remove the row from the DataTable dynamically
                            var table = $('.dataTables-example').DataTable();
                            table.row(row).remove().draw(false);
                        } else {
                            Swal.fire(
                                'Error!',
                                response.message,
                                'error'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire(
                            'Error!',
                            'Something went wrong with the request.',
                            'error'
                        );
                    }
                });
            }
        });
    });

    // ==========================================
    // 2. ADD EMPLOYEE FORM CONFIRMATION
    // ==========================================
    $('#addEmployeeForm').on('submit', function(e) {
        e.preventDefault(); // Pause the submission
        var form = this; // Store the form reference

        Swal.fire({
            title: 'Save New Employee?',
            text: "Are you sure all details are correct?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1cc88a', // Matches btn-success / index theme
            cancelButtonColor: '#858796',
            confirmButtonText: 'Yes, save employee!'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit(); // Actually submit the form to process_add.php
            }
        });
    });

    // ==========================================
    // 3. EDIT EMPLOYEE FORM CONFIRMATION
    // ==========================================
    $('#editEmployeeForm').on('submit', function(e) {
        e.preventDefault(); // Pause the submission
        var form = this;

        Swal.fire({
            title: 'Update Employee?',
            text: "Are you sure you want to apply these changes?",
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#4e73df', // Matches btn-primary
            cancelButtonColor: '#858796',
            confirmButtonText: 'Yes, update it!'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit(); // Actually submit the form to process_edit.php
            }
        });
    });

    // ==========================================
    // 4. SUCCESS / ERROR ALERT TRIGGER 
    // ==========================================
    // Reads URL parameters to pop up a Success/Error alert after PHP redirect
    // E.g., redirect like: header("Location: employee_management.php?status=added");

    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const msg = urlParams.get('msg'); // Added to support your backend URL format

    if (status === 'added' || msg === 'add_success') {
        Swal.fire('Success!', 'New employee has been added.', 'success');
        cleanUrl();
    } 
    else if (status === 'updated' || msg === 'edit_success') {
        Swal.fire('Updated!', 'Employee details have been saved.', 'success');
        cleanUrl();
    } 
    else if (status === 'deleted' || msg === 'delete_success') {
        Swal.fire('Deleted!', 'The employee has been removed.', 'success');
        cleanUrl();
    } 
    else if (status === 'error' || msg === 'error') {
        Swal.fire('Error!', 'Something went wrong processing your request.', 'error');
        cleanUrl();
    }

    // Helper function to remove '?status=...' from the URL so it doesn't pop up on refresh
    function cleanUrl() {
        window.history.replaceState(null, null, window.location.pathname);
    }
});


$(document).ready(function() {
    
    // Attach click event to the Modal's Export Button
    $('#exportAttendancePdfBtn').on('click', function(e) {
        e.preventDefault();
        
        // Ensure jsPDF is ready
        if (typeof window.jspdf === 'undefined') {
            alert("jsPDF library is not loaded properly.");
            return;
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('landscape'); // 'landscape' fits 9 columns better
        
        // Get the employee name.
        let modalTitle = $('.modal-title').text().trim();
        let empName = modalTitle.replace('Attendance Records - ', '') || 'Employee';
        
        // Define the headers based on your modal table
        var headers = ['Time Recorded', 'Designation', 'Department', 'Unit', 'Area', 'Status', 'With ID', 'Attire', 'Compliant'];
        var data = [];
        
        // Use DataTables API to get ONLY the rows on the CURRENT PAGE
        var table = $('#attendanceTable').DataTable();
        
        // CHANGED: { page: 'current' } only grabs what is currently visible
        table.rows({ page: 'current' }).every(function() {
            var rowData = this.data();
            var cleanRow = [];
            
            // Loop through the 9 columns
            for (var i = 0; i < 9; i++) {
                // Strip out any HTML tags (like badges, icons, or spans)
                var cellText = $('<div>').html(rowData[i]).text().trim();
                cleanRow.push(cellText);
            }
            data.push(cleanRow);
        });
        
        if (data.length === 0) {
            alert("No attendance records found to export on this page.");
            return;
        }
        
        // Format the PDF Document Title
        doc.setFontSize(14);
        doc.text(`Attendance Records - ${empName} (Current Page)`, 14, 15);
        
        // Generate the Table inside the PDF
        doc.autoTable({
            startY: 20,
            head: [headers],     // MUST be a 2D array, so we wrap headers in []
            body: data,
            theme: 'grid',
            headStyles: { fillColor: [78, 115, 223] }, // Matches Bootstrap btn-primary color
            styles: { fontSize: 8, cellPadding: 2 } // Slightly smaller font to fit 9 columns cleanly
        });
        
        // Trigger the PDF download
        doc.save(`Attendance_Records_${empName.replace(/\s+/g, '_')}_Page.pdf`);
    });

});
