<style>
    /* Force Modal Backdrop to sit above the fixed sidebar (2000) */
    .modal-backdrop {
        z-index: 2040 !important;
    }
    /* Force Modal to sit at the very top, above the backdrop */
    .modal {
        z-index: 2050 !important;
    }
</style>

<div class="modal fade" id="aboutUsModal" tabindex="-1" aria-labelledby="aboutUsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header text-center" style="padding: 20px 15px; display: flex; flex-direction: column; position: relative;">
                
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position: absolute; top: 15px; right: 15px;">
                    <span aria-hidden="true">&times;</span>
                </button>
                
                <i class="fa fa-info-circle fa-4x" style="color: #1ab394; margin-bottom: 10px;"></i>
                <h4 class="modal-title" id="aboutUsModalLabel">About Us</h4>
                <small class="font-bold">DICT Monday Flag Raising Attendance and Compliance Checking System</small>
            </div>
            
            <div class="modal-body">
                <h5 class="mb-4 text-center" style="color: #676a6c; font-weight: 600;">System Developers</h5>
                
                <div class="row justify-content-center">
                    <div class="col-sm-4 text-center mb-4">
                        <img alt="Walid B. Salih" class="dev-img img-fluid rounded-circle" src="developers/1.jpg" onerror="this.src='img/logo/DICT.png';" style="width: 120px; height: 120px; object-fit: cover; margin-bottom: 15px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);" />
                        <h4 style="font-weight: 600; margin-bottom: 5px;">Walid B. Salih</h4>
                    </div>
                    
                    <div class="col-sm-4 text-center mb-4">
                        <img alt="Mohammad Salih S. Musa" class="dev-img img-fluid rounded-circle" src="developers/2.jpg" onerror="this.src='img/logo/DICT.png';" style="width: 120px; height: 120px; object-fit: cover; margin-bottom: 15px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);" />
                        <h4 style="font-weight: 600; margin-bottom: 5px;">Al-Raji J. Theng</h4>
                    </div>
                    
                    <div class="col-sm-4 text-center mb-4">
                        <img alt="Al-Raji J. Theng" class="dev-img img-fluid rounded-circle" src="developers/3.jpg" onerror="this.src='img/logo/DICT.png';" style="width: 120px; height: 120px; object-fit: cover; margin-bottom: 15px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);" />
                        <h4 style="font-weight: 600; margin-bottom: 5px;">Mohammad Salih S. Musa</h4>
                    </div>
                </div>
            </div>

            <div class="modal-footer justify-content-center">
                <p style="font-size: 13px; color: #888; margin: 0;">
                    &copy; <?php echo date('Y'); ?> Department of Information and Communications Technology (DICT). All rights reserved.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Move modal to the body to prevent stacking context issues with the fixed sidebar -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var aboutUsModal = document.getElementById('aboutUsModal');
        // This stops the modal from rendering behind the backdrop due to being trapped in the sidebar
        if (aboutUsModal) {
            document.body.appendChild(aboutUsModal);
        }
    });
</script>