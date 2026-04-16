<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

include 'connect.php';

$ceremony_date = $_GET['date'] ?? '';

if (empty($ceremony_date)) {
    die("<h3 style='text-align:center; margin-top:50px; font-family:sans-serif;'>Error: No date provided.</h3>");
}

$dateObj = new DateTime($ceremony_date);
$formattedDate = $dateObj->format('F d, Y');

// Variables for stats
$totalCount = 0;
$compliantCount = 0;
$nonCompliantCount = 0;
$records = [];

try {
    if ($pdo) {
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
        
        foreach ($records as $row) {
            $totalCount++;
            if ($row['is_compliant'] == 1) {
                $compliantCount++;
            } else {
                $nonCompliantCount++;
            }
        }
    }
} catch (\PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Attendance: <?php echo $formattedDate; ?></title>

    <!-- Bootstrap 5 & Inspinia CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet" />
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="css/animate.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />
    
    <style>
        /* Animated Gradient Background matching dashboard */
        body.gray-bg, .wrapper.wrapper-content {
            background: linear-gradient(135deg, #4e73df, #1cc88a) !important;
            background-size: 200% 200% !important;
            animation: gradientBG 10s ease infinite !important;
            min-height: 100vh;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Ibox Card Styling matching dashboard */
        .ibox {
            border-radius: 15px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
            background: rgba(255, 255, 255, 0.95) !important;
            border: none !important;
            margin-top: 30px;
            margin-bottom: 25px;
            transition: transform 0.3s ease;
        }

        .ibox:hover {
            transform: translateY(-3px);
        }
        
        .ibox-title {
            background: transparent !important;
            border-bottom: 1px solid rgba(0,0,0,0.05) !important;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px 25px !important;
        }

        .ibox-content {
            background: transparent !important;
            border-radius: 0 0 15px 15px !important;
            border: none !important;
            padding: 20px 25px !important;
        }

        /* Theme Colors */
        .text-success { color: #1cc88a !important; }
        .text-primary { color: #4e73df !important; }
        .text-info { color: #36b9cc !important; }
        .text-danger { color: #e74a3b !important; }
        
        .bg-success { background-color: #1cc88a !important; }
        .bg-primary { background-color: #4e73df !important; }
        .bg-info { background-color: #36b9cc !important; }
        .bg-danger { background-color: #e74a3b !important; }

        /* Stats Cards */
        .stats-card { 
            border-radius: 10px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
            background: #ffffff !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        .border-start-3 { border-left-width: 4px !important; }

        /* Table & Layout */
        .table-responsive { 
            max-height: 60vh; 
            overflow-y: auto; 
            background: #fff; 
            border-radius: 10px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            border: 1px solid rgba(0,0,0,0.05);
        }
        thead th { 
            position: sticky; 
            top: 0; 
            background-color: #f8f9fc !important; 
            z-index: 10; 
            box-shadow: 0 1px 2px rgba(0,0,0,0.1); 
            border-bottom: 2px solid #e3e6f0 !important;
            color: #4e73df;
            font-weight: 700;
        }
        .table { margin-bottom: 0; }
        .table-hover tbody tr:hover { background-color: #f8f9fc; }
        
        /* Table Cell Alignment */
        .align-middle { vertical-align: middle !important; }
    </style>
</head>
<body class="gray-bg">

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row justify-content-center">
        <div class="col-lg-12 col-xl-11">
            
            <div class="ibox">
                <div class="ibox-title d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 text-primary fw-bold"><i class="fa fa-users me-2"></i> Flag Ceremony Attendees: <strong class="text-dark" id="ceremony_date_display"><?php echo htmlspecialchars($formattedDate); ?></strong></h4>
                    <div>
                        <button id="viewAttendancePdfBtn" class="btn btn-primary btn-sm me-2 fw-bold shadow-sm">
                            <i class="fa fa-eye"></i> View Export
                        </button>
                        <button id="exportAttendancePdfBtn" class="btn btn-danger btn-sm fw-bold shadow-sm me-2">
                            <i class="fa fa-file-pdf-o"></i> Export to PDF
                        </button>
                        <button onclick="window.close();" class="btn btn-secondary btn-sm shadow-sm">
                            <i class="fa fa-times"></i> Close Tab
                        </button>
                    </div>
                </div>

                <div class="ibox-content">
                    <?php if ($totalCount > 0): ?>
                    <!-- ATTENDANCE STATISTICS CARDS -->
                    <div class="row mb-4" id="attendance_stats_container">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <div class="p-3 bg-white border-start border-info border-start-3 stats-card text-center">
                                <h6 class="text-info mb-1 fw-bold"><i class="fa fa-users"></i> Total Attendees</h6>
                                <h3 class="mb-0 text-info fw-bold" id="stat_total"><?php echo $totalCount; ?></h3>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2 mb-md-0">
                            <div class="p-3 bg-white border-start border-success border-start-3 stats-card text-center">
                                <h6 class="text-success mb-1 fw-bold"><i class="fa fa-check-circle"></i> Compliant</h6>
                                <h3 class="mb-0 text-success fw-bold" id="stat_compliant"><?php echo $compliantCount; ?></h3>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-white border-start border-danger border-start-3 stats-card text-center">
                                <h6 class="text-danger mb-1 fw-bold"><i class="fa fa-times-circle"></i> Non-Compliant</h6>
                                <h3 class="mb-0 text-danger fw-bold" id="stat_noncompliant"><?php echo $nonCompliantCount; ?></h3>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="table-responsive border-0">
                        <table class="table table-bordered table-hover mb-0 mt-0">
                            <thead>
                                <tr>
                                    <th>Employee Name</th>
                                    <th style="white-space: nowrap;">Time Recorded</th>
                                    <th>Designation</th>
                                    <th>Division</th>
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
                                <?php
                                if ($records) {
                                    foreach ($records as $row) {
                                        $formattedTime = date('h:i A', strtotime($row['time_recorded']));
                                        $fullDateTime = date('M d, Y - h:i A', strtotime($row['time_recorded']));
                                        
                                        $gender = strtoupper($row['gender'] ?? '');
                                        $isMale = ($gender === 'M' || $gender === 'MALE');
                                        $isFemale = ($gender === 'F' || $gender === 'FEMALE');
                                        
                                        $withIdCheck = ($row['with_id'] === 'Yes') ? '<i class="fa fa-check text-success fa-lg"></i>' : '';
                                        $aseanCheck = ($row['proper_attire'] === 'Yes') ? '<i class="fa fa-check text-success fa-lg"></i>' : '';
                                        
                                        // Bootstrap 5 uses bg-success instead of badge-success
                                        $compliantClass = ($row['is_compliant'] == 1) ? 'bg-success' : 'bg-danger';
                                        $compliantText = ($row['is_compliant'] == 1) ? 'Yes' : 'No';

                                        echo "<tr>";
                                        echo "<td class='fw-bold text-dark align-middle'>
                                                <span class='pdf-full-name'>".ucwords(htmlspecialchars($row['employee_name'] ?? 'Unknown'))."</span>
                                                <span class='pdf-sex' style='display:none;'>".strtoupper($row['gender'] ?? '')."</span>
                                                <span class='pdf-division' style='display:none;'>".htmlspecialchars($row['division'] ?? '')."</span>
                                                <span class='pdf-id-number' style='display:none;'>".htmlspecialchars($row['emp_id'])."</span>
                                                <span class='pdf-with-id' style='display:none;'>".htmlspecialchars($row['with_id'] ?? 'No')."</span>
                                                <span class='pdf-proper-attire' style='display:none;'>".htmlspecialchars($row['proper_attire'] ?? 'No')."</span>
                                              </td>";
                                        echo "<td class='text-muted align-middle'>" . $formattedTime . "</td>";
                                        echo "<td class='align-middle'><span class='pdf-designation text-muted'>" . htmlspecialchars($row['designation'] ?? 'N/A') . "</span></td>";
                                        echo "<td class='text-muted align-middle'>" . htmlspecialchars($row['division'] ?? 'N/A') . "</td>";
                                        echo "<td class='text-muted align-middle'>" . htmlspecialchars($row['unit'] ?? 'N/A') . "</td>";
                                        echo "<td class='text-muted align-middle'>" . htmlspecialchars($row['area_of_assignment'] ?? 'N/A') . "</td>";
                                        
                                        echo "<td class='text-center align-middle'>" . ($isMale ? '<i class="fa fa-check text-primary fa-lg"></i>' : '') . "</td>";
                                        echo "<td class='text-center align-middle'>" . ($isFemale ? '<i class="fa fa-check text-primary fa-lg"></i>' : '') . "</td>";
                                        
                                        echo "<td class='text-center align-middle'>{$withIdCheck}</td>";
                                        echo "<td class='text-center align-middle'>{$aseanCheck}</td>";
                                        echo "<td class='text-center align-middle'><span class='badge {$compliantClass} px-3 py-2 shadow-sm rounded-pill'>" . $compliantText . "</span></td>";

                                        if (!empty($row['photo_path'])) {
                                            $safePath = htmlspecialchars($row['photo_path'], ENT_QUOTES, 'UTF-8');
                                            echo "<td class='text-center align-middle'>
                                                    <img src='{$safePath}' 
                                                         alt='Photo' 
                                                         class='shadow-sm'
                                                         style='width: 45px; height: 45px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid #e3e6f0; transition: transform 0.2s;' 
                                                         onmouseover='this.style.transform=\"scale(1.1)\"' 
                                                         onmouseout='this.style.transform=\"scale(1)\"'
                                                         onclick='viewAttendancePhoto(\"{$safePath}\", \"{$fullDateTime}\")' 
                                                         title='Click to view full image'>
                                                  </td>";
                                        } else {
                                            echo "<td class='text-center align-middle'><span class='badge bg-light text-muted border px-2 py-1 fw-normal'><i class='fa fa-eye-slash'></i> No Photo</span></td>";
                                        }
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr class='no-data-row'><td colspan='12' class='text-center text-muted py-5'>
                                            <i class='fa fa-folder-open-o fa-3x mb-3 d-block text-primary opacity-50'></i>
                                            <em style='font-size: 1.2rem;'>No attendees found for this date.</em>
                                          </td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Photo Viewer Modal (Bootstrap 5) -->
<div class="modal fade" id="photoViewerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header p-3 bg-light" style="border-radius: 15px 15px 0 0;">
                <h5 class="modal-title text-primary fw-bold mb-0"><i class="fa fa-camera"></i> Attendance Snapshot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="closePhotoModal()"></button>
            </div>
            <div class="modal-body text-center bg-dark p-2">
                <img id="attendanceImagePreview" src="" alt="Captured Photo" class="img-fluid rounded" style="max-height: 500px; width: 100%; object-fit: contain;">
                <div class="mt-3 mb-2 text-white">
                    <span class="badge bg-primary p-2 shadow-sm rounded-pill" style="font-size: 0.95rem;">
                        <i class="fa fa-clock-o"></i> Captured on: <span id="attendancePhotoTime"></span>
                    </span>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 d-flex justify-content-center" style="border-radius: 0 0 15px 15px;">
                <button type="button" class="btn btn-danger px-4 rounded-pill fw-bold shadow-sm" data-bs-dismiss="modal" onclick="closePhotoModal()">
                    <i class="fa fa-sign-out"></i> Exit
                </button>
            </div>
        </div>
    </div>
</div>

<script src="js/jquery-3.1.1.min.js"></script>
<script src="js/popper.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
    const printedByAdmin = "<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') : 'Admin'; ?>";

    function viewAttendancePhoto(imagePath, dateTime) {
        document.getElementById('attendanceImagePreview').src = imagePath;
        document.getElementById('attendancePhotoTime').textContent = dateTime;
        
        // Native Bootstrap 5 Modal implementation
        if (typeof bootstrap !== 'undefined') {
            var photoModal = new bootstrap.Modal(document.getElementById('photoViewerModal'));
            photoModal.show();
        } else if(window.jQuery) { 
            // Fallback
            $('#photoViewerModal').modal('show'); 
        }
    }

    function closePhotoModal() {
        if (typeof bootstrap !== 'undefined') {
            var modalEl = document.getElementById('photoViewerModal');
            var modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) {
                modalInstance.hide();
            } else {
                $('#photoViewerModal').modal('hide');
            }
        } else if(window.jQuery) { 
            $('#photoViewerModal').modal('hide'); 
        }
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
            doc.setFont('times', 'bold');
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
                    doc.text(`Printed by: ${printedByAdmin}`, data.settings.margin.left, pageHeight - 10);
                    doc.text(`Date Printed: ${printedDate}`, data.settings.margin.left, pageHeight - 3);
                    doc.text("Page " + doc.internal.getNumberOfPages(), pageWidth - data.settings.margin.right, pageHeight - 10, { align: 'right' });
                }
            });

            // Draw QP05-F2/r0/01Feb24 at the bottom-right corner of the table (just below last row)
            const tableEndY = doc.lastAutoTable.finalY;
            const rightMarginX = pageWidth - pageWidth * 0.05;
            doc.setFont('times', 'normal');
            doc.setFontSize(7);
            doc.setTextColor(100);
            doc.text('QP05-F2/r0/01Feb24', rightMarginX, tableEndY + 9, { align: 'right' });

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
</body>
</html>