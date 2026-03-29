<!-- VIEW MODAL -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
        <h5 class="modal-title" id="viewModalLabel"><i class="fa fa-user-circle"></i> Employee Profile</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        </div>
        <div class="modal-body">
        <div class="text-center mb-4">
            <div class="profile-avatar rounded-circle mb-2">
                <i class="fa fa-user"></i>
            </div>
            <h3 class="mt-2 font-weight-bold" id="view_full_name"></h3>
            <p class="text-muted mb-1" id="view_designation_dept"></p>
            <span class="badge" id="view_status_badge"></span>
        </div>
        <hr>
        <table class="table table-borderless table-sm m-0">
            <tbody>
                <tr>
                    <th class="text-right text-muted" width="40%">Employee ID:</th>
                    <td id="view_emp_id" class="font-weight-bold"></td>
                </tr>
                <tr>
                    <th class="text-right text-muted">Full Name:</th>
                    <td id="view_full"></td>
                </tr>
                <tr>
                    <th class="text-right text-muted">Email Address:</th>
                    <td id="view_email"></td>
                </tr>
                <tr>
                    <th class="text-right text-muted">Age:</th>
                    <td id="view_age"></td>
                </tr>
                <tr>
                    <th class="text-right text-muted">Gender:</th>
                    <td id="view_gender"></td>
                </tr>
                <tr>
                    <th class="text-right text-muted">Unit:</th>
                    <td id="view_unit"></td>
                </tr>
                <tr>
                    <th class="text-right text-muted">Area of Assignment:</th>
                    <td id="view_area"></td>
                </tr>
            </tbody>
        </table>
        </div>
        <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
    </div>
    </div>
</div>