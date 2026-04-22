<!-- EDIT EMPLOYEE MODAL -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Employee / Account</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="process_edit.php" id="editEmployeeForm">
                <div class="modal-body">
                    <!-- Hidden ID to know which employee to update -->
                    <input type="hidden" name="emp_id" id="editEmpId">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="full" id="edit_full" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="emp_email" id="edit_email" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Age</label>
                                <input type="number" name="age" id="edit_age" class="form-control" min="18" max="100">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender" id="edit_gender" class="form-control">
                                    <option value="">Select...</option>
                                    <option value="MALE">Male</option>
                                    <option value="FEMALE">Female</option>
                                    <option value="OTHER">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Division</label>
                                <select name="department" id="edit_department" class="form-control">
                                    <option value="">Select...</option>
                                    <option value="AFD">AFD</option>
                                    <option value="TOD">TOD</option>
                                    <option value="ORD">ORD</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Area of Assignment</label>
                                <select name="area_of_assignment" id="edit_area" class="form-control">
                                    <option value="">Select...</option>
                                    <option value="Regional Office">Regional Office</option>
                                    <option value="Zamboanga City">Zamboanga City</option>
                                    <option value="Zamboanga Del Sur">Zamboanga Del Sur</option>
                                    <option value="Zamboanga Del Norte">Zamboanga Del Norte</option>
                                    <option value="Basilan">Basilan</option>
                                    <option value="Tawi-Tawi">Tawi-Tawi</option>
                                    <option value="Sulu">Sulu</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>Designation</label>
                                <input type="text" name="designation" id="edit_designation" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Unit</label>
                                <input type="text" name="unit" id="edit_unit" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" id="edit_status" class="form-control">
                                    <option value="Plantilla">Plantilla</option>
                                    <option value="Job Order">Job Order</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="hr-line-dashed" style="border-top: 1px dashed #e7eaec; margin: 20px 0;"></div>
                    <h5 style="color: #4e73df; font-weight: bold; margin-bottom: 15px;">User Account Settings</h5>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" id="editUsername" class="form-control" placeholder="Update username">
                                <div id="editUsernameMsg" class="mt-1" style="display: none; font-size: 0.85em;"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="password" id="edit_password" class="form-control" placeholder="Leave blank to keep">
                                <small class="text-muted">Fill only to change.</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Re-type Password</label>
                                <input type="password" id="edit_confirm_password" class="form-control" placeholder="Re-type password">
                                <div class="invalid-feedback">Passwords do not match.</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Account Role</label>
                                <select name="role" id="edit_role" class="form-control" required>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                                <small class="text-muted">Grant or revoke Admin.</small>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" id="editEmployeeSubmitBtn" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script to populate the Edit Modal dynamically -->
<script>
    $(document).ready(function(){
        // Use event delegation so buttons still work after searching or changing pages
        $('.dataTables-example tbody').on('click', '.edit-btn', function(){
            // Grab all data from the clicked button's data attributes
            var id = $(this).data('id');
            var full = $(this).data('full');
            var email = $(this).data('email');
            var age = $(this).data('age');
            var gender = $(this).data('gender');
            var dept = $(this).data('dept');
            var area = $(this).data('area');
            var desig = $(this).data('desig');
            var unit = $(this).data('unit');
            var status = $(this).data('status');
            
            var role = $(this).data('role');
            var username = $(this).data('username'); 

            // Populate the Edit Modal fields
            $('#editEmpId').val(id);
            $('#edit_full').val(full);
            $('#edit_email').val(email);
            $('#edit_age').val(age);
            $('#edit_gender').val(gender);
            $('#edit_department').val(dept);
            $('#edit_area').val(area);
            $('#edit_designation').val(desig);
            $('#edit_unit').val(unit);
            $('#edit_status').val(status);
            
            // Populate Account Details
            $('#editUsername').val(username);
            $('#edit_password').val(''); // Always clear out the password field
            $('#edit_confirm_password').val(''); // Clear confirm password
            $('#edit_confirm_password').removeClass('is-invalid'); // Remove any error styling
            
            // If the user has a role, set it (force to lower case to match option value). 
            // Otherwise default to user.
            if(role && role !== '') {
                $('#edit_role').val(String(role).toLowerCase());
            } else {
                $('#edit_role').val('user');
            }
        });
    });
</script>