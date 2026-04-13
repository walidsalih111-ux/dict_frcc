<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// AJAX HANDLER: Fetch Attendance By Date
// ==========================================
if (isset($_POST['action']) && $_POST['action'] === 'fetch_date_attendance') {
    
    include 'connect.php'; 

    if (!$pdo) {
        echo "<tr><td colspan='10' class='text-center text-danger'>Database connection failed.</td></tr>";
        exit;
    }

    $ceremony_date = $_POST['ceremony_date'] ?? '';
    $html = '';

    try {
        // Query updated with CORRECT columns: e.gender, e.emp_id, a.is_asean
        $stmt = $pdo->prepare("
            SELECT 
                e.full as employee_name,
                e.gender,
                e.emp_id,
                a.time_recorded, 
                a.designation, 
                e.department as division,
                e.unit,
                e.area_of_assignment, 
                a.with_id, 
                a.is_asean as proper_attire,
                a.is_compliant,
                a.photo_path
            FROM attendance_record a 
            JOIN employees e ON a.emp_id = e.emp_id 
            WHERE DATE(a.time_recorded) = :ceremony_date 
            ORDER BY e.full ASC
        ");
        $stmt->execute(['ceremony_date' => $ceremony_date]);
        $records = $stmt->fetchAll();

        if ($records) {
            foreach ($records as $row) {
                $formattedTime = date('h:i A', strtotime($row['time_recorded']));
                $fullDateTime = date('M d, Y - h:i A', strtotime($row['time_recorded']));
                
                $html .= "<tr>";
                // Hidden spans carry the DB data into the PDF JS
                $html .= "<td class='font-weight-bold text-primary'>
                            <span class='pdf-full-name'>".ucwords(htmlspecialchars($row['employee_name'] ?? 'Unknown'))."</span>
                            <span class='pdf-sex' style='display:none;'>".strtoupper($row['gender'] ?? '')."</span>
                            <span class='pdf-division' style='display:none;'>".htmlspecialchars($row['division'] ?? '')."</span>
                            <span class='pdf-id-number' style='display:none;'>".htmlspecialchars($row['emp_id'])."</span>
                          </td>";
                $html .= "<td>" . $formattedTime . "</td>";
                $html .= "<td><span class='pdf-designation'>" . htmlspecialchars($row['designation'] ?? 'N/A') . "</span></td>";
                $html .= "<td>" . htmlspecialchars($row['division'] ?? 'N/A') . "</td>";
                $html .= "<td>" . htmlspecialchars($row['unit'] ?? 'N/A') . "</td>";
                $html .= "<td>" . htmlspecialchars($row['area_of_assignment'] ?? 'N/A') . "</td>";
                
                $withIdClass = ($row['with_id'] === 'Yes') ? 'badge-success' : 'badge-danger';
                $aseanClass = ($row['proper_attire'] === 'Yes') ? 'badge-success' : 'badge-danger';
                $compliantClass = ($row['is_compliant'] == 1) ? 'badge-success' : 'badge-danger';
                $compliantText = ($row['is_compliant'] == 1) ? 'Yes' : 'No';

                $html .= "<td class='text-center'><span class='badge {$withIdClass}'>" . htmlspecialchars($row['with_id'] ?? 'No') . "</span></td>";
                $html .= "<td class='text-center'><span class='badge {$aseanClass}'>" . htmlspecialchars($row['proper_attire'] ?? 'No') . "</span></td>";
                $html .= "<td class='text-center'><span class='badge {$compliantClass}'>" . $compliantText . "</span></td>";

                if (!empty($row['photo_path'])) {
                    $safePath = htmlspecialchars($row['photo_path'], ENT_QUOTES, 'UTF-8');
                    $html .= "<td class='text-center'><a href='javascript:void(0);' onclick='viewAttendancePhoto(\"{$safePath}\", \"{$fullDateTime}\")' class='text-primary font-weight-bold'><i class='fa fa-camera'></i> View</a></td>";
                } else {
                    $html .= "<td class='text-center'><span class='badge bg-light text-muted border px-2 py-1 fw-normal'><i class='fa fa-eye-slash'></i> No Photo</span></td>";
                }
                $html .= "</tr>";
            }
        } else {
            $html .= "<tr class='no-data-row'><td colspan='10' class='text-center text-muted py-4'>
                        <i class='fa fa-folder-open-o fa-2x mb-2 d-block'></i>
                        <em>No attendees found for this date.</em>
                      </td></tr>";
        }
    } catch (\PDOException $e) {
        $html .= "<tr><td colspan='10' class='text-center text-danger'>Error: " . $e->getMessage() . "</td></tr>";
    }
    
    echo $html;
    exit;
}
?>

<!-- ATTENDANCE MODAL -->
<div class="modal fade" id="attendanceModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document" style="max-width: 98%;">
    <div class="modal-content">
      <div class="modal-header bg-success p-3">
        <h5 class="modal-title text-white"><i class="fa fa-users"></i> Flag Ceremony Attendees</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-3">
        <h4 class="mb-3">Attendance Records for: <strong id="ceremony_date_display" class="text-primary"></strong></h4>
        
        <div class="table-responsive border" style="max-height: 550px; overflow-y: auto;">
            <table class="table table-bordered table-striped table-hover mb-0 mt-0">
                <thead style="position: sticky; top: 0; background-color: #fff; z-index: 10; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                    <tr>
                        <th>Employee Name</th>
                        <th style="white-space: nowrap;">Time Recorded</th>
                        <th>Designation</th>
                        <th>Department</th>
                        <th>Unit</th>
                        <th>Area of Assignment</th>
                        <th class="text-center">With ID</th>
                        <th class="text-center">Proper Attire</th>
                        <th class="text-center">Compliant</th>
                        <th class="text-center">Photo</th>
                    </tr>
                </thead>
                <tbody id="attendance_records_body">
                    <tr class="no-data-row">
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="fa fa-calendar fa-2x mb-2 d-block"></i>
                            <em>Select a date from the table to view attendees.</em>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer p-2">
            <button id="viewAttendancePdfBtn" class="btn btn-primary mr-auto">
                <i class="fa fa-eye"></i> View Export
            </button>
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
            <div class="modal-header p-2">
                <h5 class="modal-title text-muted"><i class="fa fa-camera"></i> Attendance Snapshot</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center bg-dark p-2">
                <img id="attendanceImagePreview" src="" alt="Captured Photo" class="img-fluid rounded" style="max-height: 500px; width: 100%; object-fit: contain;">
                <div class="mt-2 text-white">
                    <span class="badge badge-info p-2" style="font-size: 0.95rem;">
                        <i class="fa fa-clock-o"></i> Captured on: <span id="attendancePhotoTime"></span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const printedByAdmin = "<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') : 'Admin'; ?>";

    function viewAttendancePhoto(imagePath, dateTime) {
        document.getElementById('attendanceImagePreview').src = imagePath;
        document.getElementById('attendancePhotoTime').textContent = dateTime;
        if(window.jQuery) { $('#photoViewerModal').modal('show'); }
    }

    document.addEventListener("DOMContentLoaded", function() {
        
        // Complex Spreadsheet PDF Generation
        $(document).on('click', '#exportAttendancePdfBtn, #viewAttendancePdfBtn', function(e) {
            e.preventDefault();
            
            // Check which button was clicked
            const isPreview = $(this).attr('id') === 'viewAttendancePdfBtn';

            if (typeof window.jspdf === 'undefined' || typeof window.jspdf.jsPDF === 'undefined') {
                alert('jsPDF library is not loaded properly. Please try refreshing.');
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('landscape', 'pt', 'a4');

            const ceremonyDate = $('#ceremony_date_display').text().trim() || 'Attendance Report';
            const dataRows = [];

            let counter = 1;
            $('#attendance_records_body tr:not(.no-data-row)').each(function() {
                const cells = $(this).find('td');
                if (cells.length < 10) return;

                const nameCell = cells.eq(0);
                const properAttireVal = cells.eq(7).text().trim(); // Proper Attire column

                const employeeData = [
                    counter++,
                    nameCell.find('.pdf-full-name').text().trim(),
                    nameCell.find('.pdf-sex').text().trim(), // from e.gender
                    cells.eq(2).text().trim(), // DESIGNATION
                    nameCell.find('.pdf-division').text().trim(), // DIVISION
                    nameCell.find('.pdf-id-number').text().trim(), // from e.emp_id
                    properAttireVal // Compliant Status
                ];
                dataRows.push(employeeData);
            });

            if (dataRows.length === 0) {
                alert('No attendance records found for this date. Cannot export empty report.');
                return;
            }

            const now = new Date();
            const dateOptions = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
            const printedDate = now.toLocaleDateString('en-US', dateOptions);

            const pageWidth = doc.internal.pageSize.getWidth();
            const textY = 25;

            doc.setFontSize(8);
            doc.setFont('Helvetica', 'normal');

            // Draw placeholders for logos
            doc.setFillColor(78, 115, 223); doc.circle(pageWidth * 0.15, textY + 5, 20, 'F');
            doc.setFillColor(255, 193, 7); doc.circle(pageWidth * 0.85, textY + 5, 20, 'F');

            // Header Text
            doc.setFont('Helvetica', 'bold');
            doc.text('REPUBLIC OF THE PHILIPPINES', pageWidth / 2, textY, { align: 'center' });
            doc.setFont('Helvetica', 'normal');
            doc.text('DEPARTMENT OF INFORMATION AND COMMUNICATIONS TECHNOLOGY', pageWidth / 2, textY + 12, { align: 'center' });
            
            doc.setFontSize(10);
            doc.setFont('Helvetica', 'bold');
            doc.text('ATTENDANCE SHEET', pageWidth / 2, textY + 30, { align: 'center' });

            doc.setFontSize(8);
            doc.setFont('Helvetica', 'normal');
            doc.text('NAME OF ACTIVITY: Flag Raising Ceremony', pageWidth * 0.1, textY + 45);
            doc.setFont('Helvetica', 'bold');
            doc.text(`DATE : ${ceremonyDate}`, pageWidth * 0.1, textY + 58);

            // Privacy Notice
            doc.setFont('Helvetica', 'italic');
            doc.setFontSize(7);
            const noticeText = "DATA PRIVACY NOTICE: The data and information provided in this form are solely intended for the designated activity. Any use of this data for purposes other than those intended by the process owner constitutes a violation of the Data Privacy Act of 2023. By voluntarily providing this data and information, the Data Subject explicitly consents to its use by the office for its intended purpose. This includes, but is not limited to, documentation processes related to the activity and sharing on social media platforms for promotional or informational purposes. Your likeness in event photos may be used. You can withdraw consent by contacting us at region9basulta@dict.gov.ph";
            doc.text(noticeText, pageWidth / 2, textY + 75, { align: 'center', maxWidth: pageWidth * 0.85 });

            // Table Drawing
            doc.autoTable({
                startY: textY + 110,
                theme: 'grid',
                margin: { left: pageWidth * 0.05, right: pageWidth * 0.05 },
                headStyles: { 
                    fillColor: false, textColor: [0,0,0], fontStyle: 'bold', fontSize: 6,
                    lineWidth: 0.5, lineColor: [180, 180, 180], halign: 'center', valign: 'middle'
                },
                styles: { 
                    fontSize: 7, cellPadding: 2, font: 'Helvetica', textColor: [0,0,0], 
                    lineWidth: 0.5, lineColor: [180, 180, 180], valign: 'middle' 
                },
                head: [
                    [
                        { content: '', rowSpan: 2 },
                        { content: 'COMPLETE NAME', rowSpan: 2 },
                        { content: 'SEX', colSpan: 2 }, 
                        { content: 'DESIGNATION', rowSpan: 2 },
                        { content: 'DIVISION', rowSpan: 2 },
                        { content: 'ID', rowSpan: 2 },
                        { content: 'Properattire', rowSpan: 2 } 
                    ],
                    [
                        { content: '', colSpan: 2 }, 'M', 'F'
                    ]
                ],
                body: dataRows,
                columnStyles: {
                    0: { halign: 'center', fontStyle: 'bold' },
                    1: { minWidth: pageWidth * 0.20 }, 
                    2: { halign: 'center' }, 3: { halign: 'center' },
                    4: { minWidth: pageWidth * 0.15 },
                    5: { minWidth: pageWidth * 0.10 },
                    6: { minWidth: pageWidth * 0.05 },
                    7: { halign: 'center', fontStyle: 'bold' },
                },
                didDrawCell: function (data) {
                    const doc = data.doc;
                    if (data.section === 'head' && data.column.index === 1 && data.row.index === 0) {
                        const cell = data.cell;
                        doc.setFontSize(6); doc.setFont('Helvetica', 'bold');
                        doc.text('COMPLETE NAME', cell.x + cell.width / 2, cell.y + 12, { align: 'center' });
                        doc.setFont('Helvetica', 'normal'); doc.setFontSize(5);
                        doc.text('(Firstname, M.I., Surname)', cell.x + cell.width / 2, cell.y + 19, { align: 'center' });
                    }
                    
                    // Manual M/F Checkmarks
                    if (data.section === 'body' && data.row.index === 0 && (data.column.index === 2 || data.column.index === 3)) {
                        doc.setFont('ZapfDingbats', 'normal');
                        const dbSexVal = data.row.raw[2].toUpperCase(); // MALE or M / FEMALE or F
                        const checkmark = "\u2714"; 

                        if(data.column.index === 2 && (dbSexVal === 'M' || dbSexVal === 'MALE')) {
                            doc.text(checkmark, data.cell.x + data.cell.width / 2, data.cell.y + 12, { align: 'center' });
                        }
                        if(data.column.index === 3 && (dbSexVal === 'F' || dbSexVal === 'FEMALE')) {
                            doc.text(checkmark, data.cell.x + data.cell.width / 2, data.cell.y + 12, { align: 'center' });
                        }
                        doc.setFont('Helvetica', 'normal');
                    }
                    
                    // Proper Attire Checkmarks
                    if (data.section === 'body' && data.column.index === 7) {
                        doc.setFont('ZapfDingbats', 'normal');
                        if(data.cell.raw === 'Yes') { 
                            doc.text("\u2714", data.cell.x + data.cell.width / 2, data.cell.y + 12, { align: 'center' }); 
                        }
                        doc.setFont('Helvetica', 'normal');
                    }
                },
                didDrawPage: function (data) {
                    const pageSize = doc.internal.pageSize;
                    const pageHeight = pageSize.height ? pageSize.height : pageSize.getHeight();
                    const pageWidth = pageSize.width ? pageSize.width : pageSize.getWidth();
                    
                    doc.setFontSize(7); doc.setTextColor(100);
                    doc.text(`Printed by: ${printedByAdmin}`, data.settings.margin.left, pageHeight - 12);
                    doc.text(`Date Printed: ${printedDate}`, data.settings.margin.left, pageHeight - 7);
                    doc.text("Page " + doc.internal.getNumberOfPages(), pageWidth - data.settings.margin.right, pageHeight - 10, { align: 'right' });
                }
            });

            // Output
            const safeFilename = ceremonyDate.replace(/[^a-z0-9]/gi, '_').toLowerCase();
            if (isPreview) {
                window.open(doc.output('bloburl'), '_blank');
            } else {
                doc.save(`flag_ceremony_${safeFilename}.pdf`);
            }
        });
    });
</script>