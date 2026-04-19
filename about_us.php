<style>
    /* Force Modal Backdrop to sit above the fixed sidebar (2000) */
    .modal-backdrop {
        z-index: 2040 !important;
    }
    /* Force Modal to sit at the very top, above the backdrop */
    .modal {
        z-index: 2050 !important;
    }
    .system-description p {
        color: #676a6c;
        font-size: 14px;
        line-height: 1.5;
        text-align: justify;
        margin-bottom: 10px;
    }
    
    /* Custom Close Button Styles */
    .about-us-close-btn {
        position: absolute;
        top: 10px;
        right: 20px;
        font-size: 28px;
        font-weight: bold;
        background: transparent;
        border: none;
        color: #a0a0a0;
        cursor: pointer;
        padding: 0;
        line-height: 1;
        transition: color 0.2s;
        z-index: 10;
    }
    .about-us-close-btn:hover {
        color: #333;
        text-decoration: none;
    }
</style>

<div class="modal fade" id="aboutUsModal" tabindex="-1" aria-labelledby="aboutUsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header position-relative d-flex flex-column align-items-center text-center pt-3 pb-2">
                
                <!-- Explicit 'X' Close Button -->
                <button type="button" class="about-us-close-btn" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" onclick="closeAboutUsModal()">
                    <span aria-hidden="true">&times;</span>
                </button>
                
                <i class="fa fa-info-circle fa-3x mb-2" style="color: #1ab394;"></i>
                <h4 class="modal-title" id="aboutUsModalLabel" style="font-weight: 600; color: #676a6c;">About Us</h4>
                <small class="font-bold text-muted">DICT Monday Flag Raising Attendance and Compliance Checker</small>
            </div>
            
            <div class="modal-body px-4 px-md-5 pt-3">
                
                <!-- System Description Section -->
                <div class="system-description mb-3">
                    <p>
                        The <strong>DICT Monday Flag Raising Attendance and Compliance Checker</strong> is a web-based platform designed to streamline and modernize the monitoring of employee attendance and compliance during the weekly flag-raising ceremony. This system aims to improve accuracy, efficiency, and transparency in tracking participation, ensuring that all personnel adhere to the required protocols set by the Department of Information and Communications Technology (DICT).
                    </p>
                    <p>
                        Through digital automation, the system minimizes manual processes, reduces errors, and provides real-time data for administrators. It also offers an organized and accessible record-keeping solution, making it easier to generate reports and evaluate compliance over time.
                    </p>
                    <p class="mb-0">
                        Our goal is to support government operations by leveraging technology to enhance accountability, promote discipline, and simplify attendance management in a professional and reliable manner.
                    </p>
                </div>

                <hr style="border-top: 1px solid #e7eaec; margin: 15px 0;">

                <!-- Developers Section -->
                <h5 class="mb-3 text-center" style="color: #676a6c; font-weight: 600;">Web Developers</h5>
                
                <div class="row justify-content-center">
                    <div class="col-sm-4 text-center mb-2">
                        <img alt="Walid B. Salih" class="dev-img img-fluid rounded-circle" src="developers/1.jpg" onerror="this.src='img/logo/DICT.png';" style="width: 90px; height: 90px; object-fit: cover; margin-bottom: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);" />
                        <h4 style="font-weight: 600; margin-bottom: 5px; color: #676a6c; font-size: 15px;">Walid B. Salih</h4>
                    </div>
                    
                    <div class="col-sm-4 text-center mb-2">
                        <img alt="Al-Raji J. Theng" class="dev-img img-fluid rounded-circle" src="developers/2.jpg" onerror="this.src='img/logo/DICT.png';" style="width: 90px; height: 90px; object-fit: cover; margin-bottom: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);" />
                        <h4 style="font-weight: 600; margin-bottom: 5px; color: #676a6c; font-size: 15px;">Al-Raji J. Theng</h4>
                    </div>
                    
                    <div class="col-sm-4 text-center mb-2">
                        <img alt="Mohammad Salih S. Musa" class="dev-img img-fluid rounded-circle" src="developers/3.jpg" onerror="this.src='img/logo/DICT.png';" style="width: 90px; height: 90px; object-fit: cover; margin-bottom: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);" />
                        <h4 style="font-weight: 600; margin-bottom: 5px; color: #676a6c; font-size: 15px;">Mohammad Salih S. Musa</h4>
                    </div>
                </div>
            </div>

            <div class="modal-footer justify-content-center" style="border-top: 1px solid #e7eaec; padding: 10px;">
                <p style="font-size: 13px; color: #888; margin: 0;">
                    &copy; <?php echo date('Y'); ?> Department of Information and Communications Technology (DICT). All rights reserved.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var aboutUsModal = document.getElementById('aboutUsModal');
        // This stops the modal from rendering behind the backdrop due to being trapped in the sidebar
        if (aboutUsModal) {
            document.body.appendChild(aboutUsModal);
        }
    });

    // Custom function to close the modal ensuring compatibility across different versions
    function closeAboutUsModal() {
        // Fallback for jQuery / Bootstrap 3 or 4
        if (typeof $ !== 'undefined' && $.fn.modal) {
            $('#aboutUsModal').modal('hide');
        } 
        // Fallback for Vanilla JS / Bootstrap 5
        else if (typeof bootstrap !== 'undefined') {
            var modalEl = document.getElementById('aboutUsModal');
            var modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) {
                modalInstance.hide();
            }
        }
    }
</script>