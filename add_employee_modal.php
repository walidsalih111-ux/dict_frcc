<!-- ADD EMPLOYEE MODAL -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Employee</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="process_add.php" id="addEmployeeForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="full" class="form-control" placeholder="e.g. Juan D. Cruz" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="emp_email" class="form-control" placeholder="example@email.com">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Age</label>
                                <input type="number" name="age" class="form-control" min="18" max="100">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender" class="form-control">
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
                                <label>Department</label>
                                <select name="department" class="form-control">
                                    <option value="">Select...</option>
                                    <option value="AFD">AFD</option>
                                    <option value="TOD">TOD</option>
                                    <option value="ORD">ORD</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Area of Assignment Dropdown -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Area of Assignment</label>
                                <select name="area_of_assignment" class="form-control">
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
                                <input type="text" name="designation" class="form-control" placeholder="e.g. Planning Assistant">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Unit</label>
                                <input type="text" name="unit" class="form-control" placeholder="e.g. CSB/PNPKI">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">Select...</option>
                                    <option value="Plantilla">Plantilla</option>
                                    <option value="Job Order">Job Order</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="hr-line-dashed" style="border-top: 1px dashed #e7eaec; margin: 20px 0;"></div>
                    <h5 style="color: #4e73df; font-weight: bold; margin-bottom: 15px;">User Account Information</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control" placeholder="Assign a username" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Assign a password" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Role</label>
                                <select name="role" class="form-control" required>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Employee & Account</button>
                </div>
            </form>
        </div>
    </div>
</div>