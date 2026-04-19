// Use a global variable passed from PHP, fallback to 'Admin' if not set
const printedByAdmin = typeof window.printedByAdmin !== 'undefined' ? window.printedByAdmin : 'Admin';

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
                fillColor: [34, 42, 53], // Changed from false to black
                textColor: [255, 255, 255], // Changed to white
                fontStyle: 'bold', fontSize: 6,
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
                    { content: 'Proper Attire', rowSpan: 2 }
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
                    doc.setTextColor(255, 255, 255); // Explicitly set text to white for manually drawn content
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