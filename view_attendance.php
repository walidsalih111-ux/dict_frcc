<?php
// To ensure session is available to fetch the logged-in admin's username
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// AJAX HANDLER: Fetch Attendance Records
// ==========================================
// This block only runs when called via AJAX
if (isset($_POST['action']) && $_POST['action'] === 'fetch_attendance') {
    
    // Initialize database connection for the independent AJAX request
    include 'connect.php'; // Use the same connection settings as the main app

    if (!$pdo) {
        echo "<tr><td colspan='10' class='text-center text-danger'>Database connection failed: " . htmlspecialchars($db_error ?? 'Unknown error') . "</td></tr>";
        exit;
    }

    $emp_id = $_POST['emp_id'] ?? 0;
    $html = '';

    try {
        $stmt = $pdo->prepare("
            SELECT 
                a.time_recorded, 
                a.designation, 
                e.department,
                e.unit,
                e.area_of_assignment, 
                a.status,
                a.with_id, 
                a.is_asean,
                a.is_compliant,
                a.photo_path
            FROM attendance_record a 
            JOIN employees e ON a.emp_id = e.emp_id 
            WHERE a.emp_id = :emp_id 
            ORDER BY a.time_recorded DESC
        ");
        $stmt->execute(['emp_id' => $emp_id]);
        $records = $stmt->fetchAll();

        if ($records) {
            foreach ($records as $row) {
                $formattedTime = date('M d, Y - h:i A', strtotime($row['time_recorded']));
                
                $html .= "<tr>";
                $html .= "<td>" . $formattedTime . "</td>";
                $html .= "<td>" . htmlspecialchars($row['designation'] ?? 'N/A') . "</td>";
                $html .= "<td>" . htmlspecialchars($row['department'] ?? 'N/A') . "</td>";
                $html .= "<td>" . htmlspecialchars($row['unit'] ?? 'N/A') . "</td>";
                $html .= "<td>" . htmlspecialchars($row['area_of_assignment'] ?? 'N/A') . "</td>";
                
                // Attendance Status
                $attStatus = $row['status'] ?? 'N/A';
                $statusClass = ($attStatus === 'On Time') ? 'badge-success' : 'badge-warning';
                $html .= "<td class='text-center'><span class='badge {$statusClass}'>" . htmlspecialchars($attStatus) . "</span></td>";

                // Style badges for With ID, ASEAN Attire, and Compliant
                $withIdClass = ($row['with_id'] === 'Yes') ? 'badge-success' : 'badge-danger';
                $aseanClass = ($row['is_asean'] === 'Yes') ? 'badge-success' : 'badge-danger';
                
                // is_compliant is tinyint(1) where 1 is true/compliant and 0 is false/not compliant
                $compliantClass = ($row['is_compliant'] == 1) ? 'badge-success' : 'badge-danger';
                $compliantText = ($row['is_compliant'] == 1) ? 'Yes' : 'No';

                $html .= "<td class='text-center'><span class='badge {$withIdClass}'>" . htmlspecialchars($row['with_id'] ?? 'No') . "</span></td>";
                $html .= "<td class='text-center'><span class='badge {$aseanClass}'>" . htmlspecialchars($row['is_asean'] ?? 'No') . "</span></td>";
                $html .= "<td class='text-center'><span class='badge {$compliantClass}'>" . $compliantText . "</span></td>";

                // Added Photo Column data at the end (passing formatted time to JS function)
                if (!empty($row['photo_path'])) {
                    $safePath = htmlspecialchars($row['photo_path'], ENT_QUOTES, 'UTF-8');
                    $html .= "<td class='text-center'><a href='javascript:void(0);' onclick='viewAttendancePhoto(\"{$safePath}\", \"{$formattedTime}\")' class='text-primary font-weight-bold'><i class='fa fa-camera'></i> View</a></td>";
                } else {
                    $html .= "<td class='text-center'><span class='badge bg-light text-muted border px-2 py-1 fw-normal'><i class='fa fa-eye-slash'></i> Photo not captured</span></td>";
                }
                
                $html .= "</tr>";
            }
        } else {
            $html .= "<tr class='no-data-row'><td colspan='10' class='text-center text-muted py-4'>
                        <i class='fa fa-folder-open-o fa-2x mb-2 d-block'></i>
                        <em>No attendance records found for this employee.</em>
                      </td></tr>";
        }
    } catch (\PDOException $e) {
        $html .= "<tr><td colspan='10' class='text-center text-danger'>Error: " . $e->getMessage() . "</td></tr>";
    }
    
    // Output HTML and exit so the rest of the page doesn't load in the AJAX response
    echo $html;
    exit;
}
?>

<!-- ATTENDANCE MODAL (Rendered when included in the main file) -->
<div class="modal fade" id="attendanceModal" tabindex="-1" role="dialog" aria-labelledby="attendanceModalLabel" aria-hidden="true">
  <!-- Changed modal-lg to modal-xl to make the modal wider -->
  <div class="modal-dialog modal-xl" role="document" style="max-width: 95%;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="attendanceModalLabel"><i class="fa fa-calendar"></i> Employee Attendance</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <h4 class="mb-3">Attendance Records for: <strong id="attendance_full_name" class="text-primary"></strong></h4>
        
        <!-- Added scroll bar styling to table-responsive -->
        <div class="table-responsive border" style="max-height: 500px; overflow-y: auto;">
            <table class="table table-bordered table-striped mb-0 mt-0">
                <!-- Added sticky header so it stays visible when scrolling -->
                <thead style="position: sticky; top: 0; background-color: #fff; z-index: 1; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                    <tr>
                        <th style="white-space: nowrap;">Date & Time Recorded</th>
                        <th>Designation</th>
                        <th>Department</th>
                        <th>Unit</th>
                        <th>Area of Assignment</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">With ID</th>
                        <th class="text-center">Proper Attire</th>
                        <th class="text-center">Compliant</th>
                        <!-- Added Photo Column at the end -->
                        <th class="text-center">Photo</th>
                    </tr>
                </thead>
                <tbody id="attendance_records_body">
                    <!-- Loaded dynamically via AJAX -->
                    <tr class="no-data-row">
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="fa fa-clock-o fa-2x mb-2 d-block"></i>
                            <em>Select an employee to view their attendance history.</em>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer">
        <!-- Example of the button inside your modal -->
            <button id="exportAttendancePdfBtn" class="btn btn-danger">
                <i class="fa fa-file-pdf-o"></i> Export to PDF
            </button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Photo Viewer Modal -->
<div class="modal fade" id="photoViewerModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-muted"><i class="fa fa-camera"></i> Attendance Snapshot</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center bg-light pt-3 pb-4 px-4">
                <img id="attendanceImagePreview" src="" alt="Captured Attendance Photo" class="img-fluid rounded shadow-sm" style="max-height: 500px; width: 100%; object-fit: contain; background: #000;" onerror="this.onerror=null; this.src='data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22100%25%22%20height%3D%22100%25%22%20viewBox%3D%220%200%20800%20400%22%20preserveAspectRatio%3D%22xMidYMid%20slice%22%3E%3Cg%20fill%3D%22%232b2b2b%22%3E%3Crect%20width%3D%22800%22%20height%3D%22400%22%2F%3E%3C%2Fg%3E%3Cg%20fill%3D%22%23777%22%3E%3Ctext%20x%3D%2250%25%22%20y%3D%2250%25%22%20font-family%3D%22Arial%2C%20sans-serif%22%20font-size%3D%2224%22%20text-anchor%3D%22middle%22%20dy%3D%22.3em%22%3E%E2%9A%A0%20Photo%20not%20captured%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fsvg%3E';">
                
                <!-- Display Date and Time Below Image -->
                <div class="mt-3">
                    <span class="badge badge-info p-2" style="font-size: 0.95rem;">
                        <i class="fa fa-clock-o"></i> Captured on: <span id="attendancePhotoTime"></span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Fetch the logged-in user dynamically (Falls back to 'Admin' if session isn't set)
    const printedByAdmin = "<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') : 'Admin'; ?>";

    // Function to handle viewing the specific photo path in the modal
    // Global declaration works before or after DOMContentLoaded
    function viewAttendancePhoto(imagePath, dateTime) {
        document.getElementById('attendanceImagePreview').src = imagePath;
        document.getElementById('attendancePhotoTime').textContent = dateTime;
        if(window.jQuery) {
            $('#photoViewerModal').modal('show');
        }
    }

    // WAIT FOR DOM READY BEFORE USING JQUERY ($)
    document.addEventListener("DOMContentLoaded", function() {
        
        // --- Added Show Entries & Pagination Logic ---
        function applyEntryLimit() {
            const limitEl = $('#entryLimit');
            if (limitEl.length === 0) return; // Prevent failure if #entryLimit doesn't exist
            
            const limit = limitEl.val();
            const rows = $('#attendance_records_body tr:not(.no-data-row)');
            
            if (limit === 'all') {
                rows.show();
            } else {
                const limitNum = parseInt(limit, 10);
                rows.each(function(index) {
                    if (index < limitNum) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        }

        // Listen for changes on the Show Entries dropdown (Dynamic)
        $(document).on('change', '#entryLimit', applyEntryLimit);

        // Setup a MutationObserver to automatically apply the limit when AJAX loads new data
        const targetNode = document.getElementById('attendance_records_body');
        if (targetNode) {
            const observer = new MutationObserver(function(mutationsList) {
                for(let mutation of mutationsList) {
                    if (mutation.type === 'childList') {
                        applyEntryLimit();
                    }
                }
            });
            observer.observe(targetNode, { childList: true });
        }

        // --- Added Export to PDF Logic ---
        $(document).on('click', '#exportAttendancePdfBtn', function(e) {
            e.preventDefault();

            if (typeof window.jspdf === 'undefined') {
                alert('jsPDF library is not loaded properly. Please refresh the page and try again.');
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('landscape');

            const empName = $('#attendance_full_name').text().trim() || 'Attendance_Report';
            const headers = ['Date & Time Recorded', 'Designation', 'Department', 'Unit', 'Area of Assignment', 'Status', 'With ID', 'Proper Attire', 'Compliant'];
            const data = [];

            $('#attendance_records_body tr:not(.no-data-row)').each(function() {
                const cells = $(this).find('td');
                if (cells.length < 9) {
                    return;
                }

                data.push([
                    cells.eq(0).text().trim(),
                    cells.eq(1).text().trim(),
                    cells.eq(2).text().trim(),
                    cells.eq(3).text().trim(),
                    cells.eq(4).text().trim(),
                    cells.eq(5).text().trim(),
                    cells.eq(6).text().trim(),
                    cells.eq(7).text().trim(),
                    cells.eq(8).text().trim()
                ]);
            });

            if (data.length === 0) {
                alert('No attendance records found to export.');
                return;
            }

            // --- Capture Current Date & Time ---
            const now = new Date();
            const dateOptions = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            const printedDate = now.toLocaleDateString('en-US', dateOptions);

            // Document Title
            doc.setFontSize(14);
            doc.text(`Attendance Records - ${empName}`, 14, 15);

            if (typeof doc.autoTable !== 'function') {
                alert('jsPDF autotable plugin is not loaded.');
                return;
            }

            // Generate Table and Footer
            doc.autoTable({
                startY: 22,
                head: [headers],
                body: data,
                theme: 'grid',
                headStyles: { fillColor: [78, 115, 223] },
                styles: { fontSize: 8, cellPadding: 2 },
                margin: { bottom: 20 }, // Extra margin to ensure space for the footer
                didDrawPage: function (data) {
                    // Create Footer text on every page
                    const pageSize = doc.internal.pageSize;
                    const pageHeight = pageSize.height ? pageSize.height : pageSize.getHeight();
                    const pageWidth = pageSize.width ? pageSize.width : pageSize.getWidth();
                    
                    doc.setFontSize(9);
                    doc.setTextColor(100); // Gray text
                    
                    // Left side details (Printed By & Date)
                    doc.text(`Printed by: ${printedByAdmin}`, data.settings.margin.left, pageHeight - 12);
                    doc.text(`Date & Time Printed: ${printedDate}`, data.settings.margin.left, pageHeight - 7);
                    
                    // Right side details (Page Numbering)
                    const str = "Page " + doc.internal.getNumberOfPages();
                    doc.text(str, pageWidth - data.settings.margin.right, pageHeight - 10, { align: 'right' });
                }
            });

            const safeFilename = empName.replace(/[^a-z0-9]/gi, '_').toLowerCase();
            doc.save(`attendance_${safeFilename}.pdf`);
        });
    });
</script>