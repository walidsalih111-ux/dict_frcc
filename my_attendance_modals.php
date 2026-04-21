<!-- VIEW MODAL (Profile) -->
<div class="modal fade" id="profileModal" tabindex="-1" role="dialog" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content profile-modal-content">
            <div class="modal-header p-3">
                <h5 class="modal-title text-primary fw-bold" id="profileModalLabel"><i class="fa fa-user-circle-o me-1"></i> Employee Profile</h5>
                <button type="button" class="btn border-0 shadow-none text-secondary p-1" data-bs-dismiss="modal" aria-label="Close" style="background: transparent;">
                    <i class="fa fa-times fs-5"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4 mt-2">
                    <div class="profile-avatar rounded-circle mb-3 mx-auto">
                        <i class="fa fa-user"></i>
                    </div>
                    <h3 class="mt-2 fw-bold text-dark"><?php echo htmlspecialchars($userProfile['full'] ?? $fullname); ?></h3>
                    <p class="text-muted mb-2"><?php echo htmlspecialchars($userProfile['designation'] ?? 'Designation Not Set'); ?>  |  <?php echo htmlspecialchars($userProfile['department'] ?? 'N/A'); ?></p>
                    <?php 
                        $status = htmlspecialchars($userProfile['status'] ?? 'N/A');
                        $badgeClass = 'bg-secondary';
                        $lowerStatus = strtolower($status);
                        
                        // Badge logic mapping
                        if(in_array($lowerStatus, ['plantilla', 'active', 'permanent', 'regular'])) {
                            $badgeClass = 'bg-success';
                        } elseif(in_array($lowerStatus, ['job order', 'contractual', 'temporary'])) {
                            $badgeClass = 'bg-warning text-dark';
                        } elseif(in_array($lowerStatus, ['inactive', 'resigned'])) {
                            $badgeClass = 'bg-danger';
                        }
                    ?>
                    <span class="badge <?php echo $badgeClass; ?> px-3 py-2 rounded-pill shadow-sm"><?php echo ucfirst($status); ?></span>
                </div>
                <hr>
                <table class="table table-borderless table-sm m-0">
                    <tbody>
                        <tr>
                            <th class="text-end text-muted" width="40%">Employee ID:</th>
                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($userProfile['emp_id'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th class="text-end text-muted">Full Name:</th>
                            <td class="text-dark"><?php echo htmlspecialchars($userProfile['full'] ?? $fullname); ?></td>
                        </tr>
                        <tr>
                            <th class="text-end text-muted">Email Address:</th>
                            <td class="text-dark"><?php echo !empty($userProfile['emp_email']) ? htmlspecialchars($userProfile['emp_email']) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <th class="text-end text-muted">Age:</th>
                            <td class="text-dark"><?php echo htmlspecialchars($userProfile['age'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th class="text-end text-muted">Gender:</th>
                            <td class="text-dark"><?php echo htmlspecialchars($userProfile['gender'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th class="text-end text-muted">Unit:</th>
                            <td class="text-dark"><?php echo htmlspecialchars($userProfile['unit'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th class="text-end text-muted">Area of Assignment:</th>
                            <td class="text-dark"><?php echo htmlspecialchars($userProfile['area_of_assignment'] ?? 'N/A'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer bg-light" style="border-radius: 0 0 15px 15px;">
                <button type="button" class="btn btn-outline-primary me-auto fw-bold" id="openAccountSettingsBtn"><i class="fa fa-cog me-1"></i> Edit Account</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Account Settings Modal -->
<div class="modal fade" id="accountSettingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content profile-modal-content">
            <div class="modal-header p-3">
                <h5 class="modal-title text-primary fw-bold"><i class="fa fa-cog me-1"></i> Account Settings</h5>
                <button type="button" class="btn border-0 shadow-none text-secondary p-1" data-bs-dismiss="modal" aria-label="Close" style="background: transparent;">
                    <i class="fa fa-times fs-5"></i>
                </button>
            </div>
            <form id="accountSettingsForm" method="POST" action="process_account_settings.php">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Username</label>
                        <input type="hidden" name="emp_id" id="acc_emp_id" value="<?php echo htmlspecialchars($userProfile['emp_id'] ?? ''); ?>">
                        <input type="text" name="username" id="acc_username" class="form-control" required value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>">
                        <small id="usernameFeedback" class="form-text mt-1 d-block"></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Email</label>
                        <input type="email" name="emp_email" id="acc_email" class="form-control" value="<?php echo htmlspecialchars($userProfile['emp_email'] ?? ''); ?>">
                    </div>
                    <hr>
                    <p class="small text-info mb-3 fw-bold"><i class="fa fa-lock me-1"></i> Change Password (leave blank to keep current password)</p>
                    
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Current Password</label>
                        <div style="position: relative;">
                            <input type="password" name="current_password" id="acc_current_password" class="form-control" style="padding-right: 40px;">
                            <span class="toggle-password" onclick="togglePassword('acc_current_password', this)" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #858796; z-index: 10;">
                                <i class="fa fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">New Password</label>
                        <div style="position: relative;">
                            <input type="password" name="new_password" id="acc_new_password" class="form-control" style="padding-right: 40px;" minlength="8" placeholder="At least 8 characters">
                            <span class="toggle-password" onclick="togglePassword('acc_new_password', this)" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #858796; z-index: 10;">
                                <i class="fa fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Confirm New Password</label>
                        <div style="position: relative;">
                            <input type="password" name="confirm_password" id="acc_confirm_password" class="form-control" style="padding-right: 40px;">
                            <span class="toggle-password" onclick="togglePassword('acc_confirm_password', this)" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #858796; z-index: 10;">
                                <i class="fa fa-eye"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-radius: 0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold" id="saveAccountBtn"><i class="fa fa-save me-1"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Photo Viewer Modal -->
<div class="modal fade" id="photoViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header p-3 bg-light" style="border-radius: 15px 15px 0 0;">
                <h5 class="modal-title text-primary fw-bold mb-0"><i class="fa fa-camera"></i> Attendance Snapshot</h5>
                <button type="button" class="btn border-0 shadow-none text-secondary p-1" data-bs-dismiss="modal" aria-label="Close" style="background: transparent;">
                    <i class="fa fa-times fs-5"></i>
                </button>
            </div>
            <div class="modal-body text-center bg-dark p-2" style="border-radius: 0 0 15px 15px;">
                <img id="attendanceImagePreview" src="" alt="Captured Attendance Photo" class="img-fluid rounded" style="max-height: 500px; width: 100%; object-fit: contain;">
                <div class="mt-3 mb-2 text-white">
                    <span class="badge bg-primary p-2 shadow-sm rounded-pill" style="font-size: 0.95rem;">
                        <i class="fa fa-clock-o"></i> Captured on: <span id="photoDateTimePreview"></span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>