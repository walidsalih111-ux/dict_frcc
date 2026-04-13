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
        echo "<tr><td colspan='12' class='text-center text-danger'>Database connection failed.</td></tr>";
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
                            <span class='pdf-with-id' style='display:none;'>".htmlspecialchars($row['with_id'] ?? 'No')."</span>
                            <span class='pdf-proper-attire' style='display:none;'>".htmlspecialchars($row['proper_attire'] ?? 'No')."</span>
                          </td>";
                $html .= "<td>" . $formattedTime . "</td>";
                $html .= "<td><span class='pdf-designation'>" . htmlspecialchars($row['designation'] ?? 'N/A') . "</span></td>";
                $html .= "<td>" . htmlspecialchars($row['division'] ?? 'N/A') . "</td>";
                $html .= "<td>" . htmlspecialchars($row['unit'] ?? 'N/A') . "</td>";
                $html .= "<td>" . htmlspecialchars($row['area_of_assignment'] ?? 'N/A') . "</td>";
                
                $gender = strtoupper($row['gender'] ?? '');
                $isMale = ($gender === 'M' || $gender === 'MALE');
                $isFemale = ($gender === 'F' || $gender === 'FEMALE');
                
                $html .= "<td class='text-center'>" . ($isMale ? '<i class="fa fa-check text-success"></i>' : '') . "</td>";
                $html .= "<td class='text-center'>" . ($isFemale ? '<i class="fa fa-check text-success"></i>' : '') . "</td>";
                
                $withIdCheck = ($row['with_id'] === 'Yes') ? '<i class="fa fa-check text-success"></i>' : '';
                $aseanCheck = ($row['proper_attire'] === 'Yes') ? '<i class="fa fa-check text-success"></i>' : '';
                $compliantClass = ($row['is_compliant'] == 1) ? 'badge-success' : 'badge-danger';
                $compliantText = ($row['is_compliant'] == 1) ? 'Yes' : 'No';

                $html .= "<td class='text-center'>{$withIdCheck}</td>";
                $html .= "<td class='text-center'>{$aseanCheck}</td>";
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
            $html .= "<tr class='no-data-row'><td colspan='12' class='text-center text-muted py-4'>
                        <i class='fa fa-folder-open-o fa-2x mb-2 d-block'></i>
                        <em>No attendees found for this date.</em>
                      </td></tr>";
        }
    } catch (\PDOException $e) {
        $html .= "<tr><td colspan='12' class='text-center text-danger'>Error: " . $e->getMessage() . "</td></tr>";
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
                        <th class="text-center">M</th>
                        <th class="text-center">F</th>
                        <th class="text-center">With ID</th>
                        <th class="text-center">Proper Attire</th>
                        <th class="text-center">Compliant</th>
                        <th class="text-center">Photo</th>
                    </tr>
                </thead>
                <tbody id="attendance_records_body">
                    <tr class="no-data-row">
                        <td colspan="12" class="text-center text-muted py-4">
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

    // Preload header image for PDF exports
    let headerImgInfo = null;
    document.addEventListener("DOMContentLoaded", function() {
        const img = new Image();
        img.crossOrigin = "Anonymous";
        img.onload = function() {
            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);
            headerImgInfo = {
                data: canvas.toDataURL('image/jpeg'),
                width: img.width,
                height: img.height
            };
        };
        img.src = 'img/view_attendance/header.jpg';

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

                const dbSex = nameCell.find('.pdf-sex').text().trim().toUpperCase();
                const isM = (dbSex === 'M' || dbSex === 'MALE');
                const isF = (dbSex === 'F' || dbSex === 'FEMALE');
                const isWithId = (nameCell.find('.pdf-with-id').text().trim() === 'Yes');
                const isAttire = (nameCell.find('.pdf-proper-attire').text().trim() === 'Yes');

                const employeeData = [
                    counter++,
                    nameCell.find('.pdf-full-name').text().trim(), // COMPLETE NAME
                    { content: '', isChecked: isM },               // M
                    { content: '', isChecked: isF },               // F
                    cells.eq(2).text().trim(),                     // DESIGNATION
                    nameCell.find('.pdf-division').text().trim(),  // DIVISION
                    { content: '', isChecked: isWithId },          // ID
                    { content: '', isChecked: isAttire }           // Properattire
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
            let currentY = 25;

            // Set global font equivalent to Palatino Linotype / Serif styling
            doc.setFont('times', 'normal');

            // Draw Header Image or Fallback text
            if (headerImgInfo) {
                const targetHeight = 55; // Set image display height
                const ratio = headerImgInfo.width / headerImgInfo.height;
                const targetWidth = targetHeight * ratio;
                const xPos = (pageWidth - targetWidth) / 2; // Center horizontally
                
                doc.addImage(headerImgInfo.data, 'JPEG', xPos, currentY, targetWidth, targetHeight);
                currentY += targetHeight + 15; // move Y down below image
            } else {
                // Fallback text if the image fails to load
                doc.setFont('times', 'bold');
                doc.text('REPUBLIC OF THE PHILIPPINES', pageWidth / 2, currentY, { align: 'center' });
                doc.setFont('times', 'normal');
                doc.text('DEPARTMENT OF INFORMATION AND COMMUNICATIONS TECHNOLOGY', pageWidth / 2, currentY + 12, { align: 'center' });
                currentY += 30;
            }
            
            doc.setFontSize(10);
            doc.setFont('times', 'bold');
            doc.text('ATTENDANCE SHEET', pageWidth / 2, currentY, { align: 'center' });

            doc.setFontSize(8);
            doc.setFont('times', 'normal');
            doc.text('NAME OF ACTIVITY: Flag Raising Ceremony', pageWidth * 0.1, currentY + 15);
            doc.setFont('times', 'bold');
            doc.text(`DATE : ${ceremonyDate}`, pageWidth * 0.1, currentY + 28);

            // Privacy Notice
            doc.setFont('times', 'italic');
            doc.setFontSize(7);
            const noticeText = "DATA PRIVACY NOTICE: The data and information provided in this form are solely intended for the designated activity. Any use of this data for purposes other than those intended by the process owner constitutes a violation of the Data Privacy Act of 2023. By voluntarily providing this data and information, the Data Subject explicitly consents to its use by the office for its intended purpose. This includes, but is not limited to, documentation processes related to the activity and sharing on social media platforms for promotional or informational purposes. Your likeness in event photos may be used. You can withdraw consent by contacting us at region9basulta@dict.gov.ph";
            doc.text(noticeText, pageWidth / 2, currentY + 45, { align: 'center', maxWidth: pageWidth * 0.85 });

            // Table Drawing
            doc.autoTable({
                startY: currentY + 80,
                theme: 'grid',
                margin: { left: pageWidth * 0.05, right: pageWidth * 0.05 },
                headStyles: { 
                    fillColor: false, textColor: [0,0,0], fontStyle: 'bold', fontSize: 6,
                    lineWidth: 0.5, lineColor: [180, 180, 180], halign: 'center', valign: 'middle',
                    font: 'times'
                },
                styles: { 
                    fontSize: 7, cellPadding: 2, font: 'times', textColor: [0,0,0], 
                    lineWidth: 0.5, lineColor: [180, 180, 180], valign: 'middle' 
                },
                head: [
                    [
                        { content: '', rowSpan: 2 },
                        { content: '', rowSpan: 2 }, // COMPLETE NAME (drawn manually)
                        { content: 'SEX', colSpan: 2 }, 
                        { content: 'DESIGNATION', rowSpan: 2 },
                        { content: 'DIVISION', rowSpan: 2 },
                        { content: 'ID', rowSpan: 2 },
                        { content: 'Properattire', rowSpan: 2 }
                    ],
                    [
                        'M', 'F'
                    ]
                ],
                body: dataRows,
                columnStyles: {
                    0: { halign: 'center', fontStyle: 'bold', cellWidth: 25 },
                    1: { minWidth: pageWidth * 0.25 }, // COMPLETE NAME
                    2: { halign: 'center', cellWidth: 25 }, // M
                    3: { halign: 'center', cellWidth: 25 }, // F
                    4: { halign: 'center', minWidth: pageWidth * 0.15 }, // DESIGNATION
                    5: { halign: 'center', minWidth: pageWidth * 0.15 }, // DIVISION
                    6: { halign: 'center', cellWidth: pageWidth * 0.05 }, // ID
                    7: { halign: 'center', cellWidth: pageWidth * 0.08 }, // Properattire
                },
                didDrawCell: function (data) {
                    const doc = data.doc;
                    if (data.section === 'head' && data.column.index === 1 && data.row.index === 0) {
                        const cell = data.cell;
                        doc.setFontSize(6); doc.setFont('times', 'bold');
                        doc.text('COMPLETE NAME', cell.x + cell.width / 2, cell.y + 12, { align: 'center' });
                        doc.setFont('times', 'normal'); doc.setFontSize(5);
                        doc.text('(Firstname, M.I., Surname)', cell.x + cell.width / 2, cell.y + 19, { align: 'center' });
                    }
                    
                    // Draw Checkboxes for M, F, ID, Properattire
                    if (data.section === 'body' && (data.column.index === 2 || data.column.index === 3 || data.column.index === 6 || data.column.index === 7)) {
                        const rawData = data.cell.raw;
                        const isChecked = rawData && rawData.isChecked;
                        
                        // Draw square box
                        const boxSize = 8;
                        const boxX = data.cell.x + (data.cell.width - boxSize) / 2;
                        const boxY = data.cell.y + (data.cell.height - boxSize) / 2;
                        
                        doc.setLineWidth(0.5);
                        doc.setDrawColor(0, 0, 0);
                        doc.rect(boxX, boxY, boxSize, boxSize); // Draw empty box

                        if (isChecked) {
                            doc.setFillColor(0, 0, 0);
                            doc.rect(boxX + 1, boxY + 1, boxSize - 2, boxSize - 2, 'F');
                        }
                    }
                },
                didDrawPage: function (data) {
                    const pageSize = doc.internal.pageSize;
                    const pageHeight = pageSize.height ? pageSize.height : pageSize.getHeight();
                    const pageWidth = pageSize.width ? pageSize.width : pageSize.getWidth();
                    
                    doc.setFont('times', 'normal');
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