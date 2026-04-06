/**
 * js/attendance.js
 * Handles live clock, camera capture, modal previews, and form validations
 * for the DICT Monday Flag Raising Attendance.
 */

// Live Clock Logic
function updateClock() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    const dateStr = now.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    
    const clockEl = document.getElementById('live-clock');
    const dateEl = document.getElementById('live-date');
    
    if (clockEl) clockEl.textContent = timeStr;
    if (dateEl) dateEl.textContent = dateStr;
}

setInterval(updateClock, 1000);
updateClock();

$(document).ready(function() {
    // Initialize Select2. 
    // It will automatically respect the 'disabled' attribute set by PHP in the HTML.
    $('#emp_id').select2({
        placeholder: "Search your name...",
        allowClear: true,
        width: '100%'
    }).on('change', function() {
        // When employee is changed, uncheck both toggles
        $('#with_id').prop('checked', false);
        $('#is_asean').prop('checked', false);
    });
});

let cameraStream = null;
let captureInterval = null;

// Open Camera Capture Function
function capturePhoto() {
    // Clear any existing intervals if we're reopening
    if (captureInterval) clearInterval(captureInterval);

    Swal.fire({
        title: 'Capture Half-Body Photo',
        width: '600px', // WIDENED MODAL
        html: `
            <div style="font-size: 0.95rem; color: #676a6c; margin-bottom: 15px;">
                Please align your face and upper body within the frame.<br>
                Auto-capturing in <strong id="countdown-text" style="color: #ed5565;">5</strong> seconds...
            </div>
            <div id="camera-container" style="position: relative; width: 100%; max-width: 550px; margin: 0 auto; overflow: hidden; border-radius: 4px; background: #000; min-height: 350px; display: flex; align-items: center; justify-content: center;">
                <video id="camera-stream" width="100%" autoplay playsinline style="transform: scaleX(-1); display: block;"></video>
                <div class="camera-overlay" id="camera-overlay"></div>
                <div class="countdown-timer" id="countdown-display">5</div>
            </div>
            <canvas id="camera-canvas" style="display: none;"></canvas>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-camera-fill me-1"></i> Capture Now',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#1ab394',
        allowOutsideClick: false,
        didOpen: () => {
            const video = document.getElementById('camera-stream');
            const container = document.getElementById('camera-container');
            const overlay = document.getElementById('camera-overlay');
            const countdownDisplay = document.getElementById('countdown-display');
            const countdownText = document.getElementById('countdown-text');
            
            // Disable confirm button initially while camera loads
            Swal.disableButtons();
            
            // Check if browser supports mediaDevices
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } })
                    .then(function(stream) {
                        cameraStream = stream;
                        video.srcObject = stream;
                        
                        // Enable the capture button once the video metadata is loaded
                        video.onloadedmetadata = () => {
                            Swal.enableButtons();
                            
                            // Start the 5-second countdown
                            let timeLeft = 5;
                            captureInterval = setInterval(() => {
                                timeLeft--;
                                if (timeLeft > 0) {
                                    countdownDisplay.innerText = timeLeft;
                                    countdownText.innerText = timeLeft;
                                } else {
                                    // Time's up! Clear interval and trigger capture
                                    clearInterval(captureInterval);
                                    countdownDisplay.innerText = '';
                                    countdownText.innerText = '0';
                                    
                                    // Programmatically click the confirm button
                                    Swal.clickConfirm();
                                }
                            }, 1000);
                        };
                    })
                    .catch(function(err) {
                        console.error("Camera access error: ", err);
                        video.style.display = 'none';
                        if (overlay) overlay.style.display = 'none';
                        if (countdownDisplay) countdownDisplay.style.display = 'none';
                        
                        let errorTitle = "Camera Access Denied";
                        let errorMsg = "Could not access the camera. Please check your device permissions.";
                        
                        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                            errorMsg = "Please allow camera access in your browser site settings (click the lock icon in your URL bar) and try again.";
                        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                            errorTitle = "No Camera Found";
                            errorMsg = "We could not find a camera connected to your device.";
                        } else if (!window.isSecureContext) {
                            errorTitle = "Insecure Connection";
                            errorMsg = "Camera access requires a secure connection (HTTPS) or localhost. Please check your URL.";
                        }

                        // Replace the video feed with a clear error message
                        container.style.background = '#fdf0f0';
                        container.style.border = '1px solid #ed5565';
                        container.innerHTML = `
                            <div style="padding: 20px; color: #ed5565; text-align: center;">
                                <i class="bi bi-camera-video-off-fill" style="font-size: 2.5rem; display: block; margin-bottom: 10px;"></i>
                                <strong>${errorTitle}</strong>
                                <p style="font-size: 0.85rem; margin-top: 5px; color: #676a6c; margin-bottom: 0;">${errorMsg}</p>
                            </div>
                        `;
                    });
            } else {
                // Browser does not support getUserMedia API at all
                video.style.display = 'none';
                if (overlay) overlay.style.display = 'none';
                if (countdownDisplay) countdownDisplay.style.display = 'none';
                
                container.style.background = '#fdf0f0';
                container.style.border = '1px solid #ed5565';
                container.innerHTML = `
                    <div style="padding: 20px; color: #ed5565; text-align: center;">
                        <i class="bi bi-exclamation-triangle-fill" style="font-size: 2.5rem; display: block; margin-bottom: 10px;"></i>
                        <strong>Browser Unsupported</strong>
                        <p style="font-size: 0.85rem; margin-top: 5px; color: #676a6c; margin-bottom: 0;">Your browser does not support camera access, or you are accessing the site via HTTP instead of HTTPS.</p>
                    </div>
                `;
            }
        },
        willClose: () => {
            // Ensure countdown is stopped and camera stream is closed
            if (captureInterval) {
                clearInterval(captureInterval);
            }
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
            }
        },
        preConfirm: () => {
            const video = document.getElementById('camera-stream');
            const canvas = document.getElementById('camera-canvas');
            
            if (!cameraStream || !video || !video.videoWidth) {
                Swal.showValidationMessage('Camera is not ready yet or access was denied.');
                return false;
            }
            
            // Set canvas size to match video feed
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const context = canvas.getContext('2d');
            
            // Mirror the canvas image to match the video preview
            context.translate(canvas.width, 0);
            context.scale(-1, 1);
            
            // Draw the video frame onto the canvas
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Convert canvas to Base64 image data string and return it
            return canvas.toDataURL('image/jpeg', 0.85); // 0.85 quality
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Instead of submitting immediately, show the preview modal
            showPreviewModal(result.value);
        }
    });
}

// Modal to Preview Photo and offer Recapture vs Submit
function showPreviewModal(photoData) {
    Swal.fire({
        title: 'Review Attendance Photo',
        width: '600px', // WIDENED MODAL
        html: `
            <div style="font-size: 0.95rem; color: #676a6c; margin-bottom: 15px;">
                Please ensure your ID and Proper Attire are clearly visible.
            </div>
            <div style="width: 100%; max-width: 550px; margin: 0 auto; overflow: hidden; border-radius: 4px; border: 2px solid #e5e6e7; background: #000;">
                <img src="${photoData}" style="width: 100%; display: block;" alt="Captured Photo" />
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-check-circle-fill me-1"></i> Submit Attendance',
        cancelButtonText: '<i class="bi bi-arrow-counterclockwise me-1"></i> Retake Photo',
        confirmButtonColor: '#1ab394',
        cancelButtonColor: '#f8ac59', // A slightly different color to distinguish retake from cancel
        allowOutsideClick: false,
        reverseButtons: true // Put the submit button on the right, retake on the left
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit the form containing both attendance data and the photo
            document.getElementById('photo_data').value = photoData;
            
            Swal.fire({
                title: 'Saving...',
                text: 'Uploading attendance record.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            $('#attendance-form').submit();
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            // User chose to retake the photo, reopen the camera modal
            capturePhoto();
        }
    });
}

// Function to handle the sign-in prompt
function confirmSignIn() {
    const emp = $('#emp_id').val();
    if (!emp) {
        Swal.fire({
            icon: 'warning',
            title: 'Required',
            text: 'Please select your name first.',
            confirmButtonColor: '#1ab394'
        });
        return;
    }

    // Check if it's currently past 8:00 AM
    const now = new Date();
    const isLate = (now.getHours() > 8) || (now.getHours() === 8 && (now.getMinutes() > 0 || now.getSeconds() > 0));
    
    let warningHtml = '';
    if (isLate) {
        warningHtml = `
            <div style="background-color: #fcf8e3; color: #8a6d3b; padding: 12px; border-radius: 3px; margin-bottom: 15px; font-size: 13px; border: 1px solid #faebcc; text-align: left;">
                <strong><i class="bi bi-clock-history"></i> Notice:</strong> It is past 8:00 AM. Your attendance will be marked as <strong>LATE</strong>.
            </div>
        `;
    }

    let cameraNoticeHtml = `
        <div style="background-color: #eaf6f4; color: #15967c; padding: 12px; border-radius: 3px; margin-bottom: 15px; font-size: 13px; border: 1px solid #cdefe8; text-align: left;">
            <strong><i class="bi bi-camera-video-fill"></i> Camera Notice:</strong> Proceeding will open your device's camera to capture a photo for attendance verification.
        </div>
    `;

    Swal.fire({
        title: 'Sign In Agreement',
        html: `
            ${warningHtml}
            ${cameraNoticeHtml}
            <div style="font-size: 14px; color: #676a6c; margin-bottom: 20px;">
                I confirm that the information provided is accurate and I agree to the office rules.
            </div>
            <div class="form-check d-flex justify-content-center align-items-center gap-2">
                <input class="form-check-input mt-0 shadow-none" type="checkbox" id="agree-checkbox" style="cursor: pointer; width: 1.2rem !important; height: 1.2rem !important; border-color: #1ab394;">
                <label class="form-check-label text-dark" for="agree-checkbox" style="cursor: pointer; user-select: none; font-size: 14px;">
                    I agree to this condition
                </label>
            </div>
        `,
        icon: isLate ? 'warning' : 'info',
        showCancelButton: true,
        confirmButtonColor: '#1ab394', // Match Inspinia Primary Color
        cancelButtonColor: '#ffffff',
        cancelButtonText: '<span style="color:#676a6c; font-weight: 600;">Cancel</span>',
        confirmButtonText: 'Proceed to Capture',
        customClass: {
            cancelButton: 'border' // Add border to cancel button to look like btn-white
        },
        preConfirm: () => {
            const isAgreed = document.getElementById('agree-checkbox').checked;
            if (!isAgreed) {
                Swal.showValidationMessage('You must check "I agree to this condition" to proceed.');
                return false; 
            }
            return true;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Instead of immediately submitting, trigger the camera
            capturePhoto();
        }
    });
}