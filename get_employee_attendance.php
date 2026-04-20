<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

include 'connect.php';

if (!$pdo) {
    die('Database connection failed.');
}

$emp_id = isset($_GET['emp_id']) ? (int) $_GET['emp_id'] : 0;

if ($emp_id <= 0) {
    die('Invalid employee selection.');
}

// Fetch employee details for the page header
$empStmt = $pdo->prepare("SELECT full, designation, department FROM employees WHERE emp_id = :emp_id");
$empStmt->execute(['emp_id' => $emp_id]);
$employeeInfo = $empStmt->fetch(PDO::FETCH_ASSOC);

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$records = [];
$compliantCount = 0;
$nonCompliantCount = 0;
$totalRecords = 0;
$totalPages = 1;
$start = 0;
$end = 0;

try {
    // Total records
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_record WHERE emp_id = :emp_id");
    $countStmt->execute(['emp_id' => $emp_id]);
    $totalRecords = (int) $countStmt->fetchColumn();

    $totalPages = ($totalRecords > 0) ? (int) ceil($totalRecords / $limit) : 1;
    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $limit;
    }

    // Compliant totals across all records
    $compStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_record WHERE emp_id = :emp_id AND is_compliant = 1");
    $compStmt->execute(['emp_id' => $emp_id]);
    $compliantCount = (int) $compStmt->fetchColumn();
    $nonCompliantCount = max(0, $totalRecords - $compliantCount);

    // Fetch paginated records
    $stmt = $pdo->prepare(
        "SELECT 
            a.time_recorded, 
            a.with_id, 
            a.is_asean, 
            a.is_compliant, 
            a.photo_path,
            e.designation,
            e.department AS division,
            e.unit, 
            e.area_of_assignment
        FROM attendance_record a
        LEFT JOIN employees e ON a.emp_id = e.emp_id
        WHERE a.emp_id = :emp_id
        ORDER BY a.time_recorded DESC
        LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':emp_id', (int)$emp_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $start = $totalRecords > 0 ? $offset + 1 : 0;
    $end = min($offset + $limit, $totalRecords);

} catch (PDOException $e) {
    $errorMsg = 'Database Error: Could not fetch records.';
}
?>

<style>
    .custom-table th {
        border-top: none !important;
        border-bottom: 2px solid #f1f3f5 !important;
        color: #4a5568;
        font-weight: 600;
        font-size: 13px;
        text-transform: capitalize;
        padding: 15px 10px !important;
        white-space: nowrap;
    }
    .custom-table td {
        border-top: 1px solid #f1f3f5 !important;
        vertical-align: middle !important;
        font-size: 14px;
        color: #333;
        padding: 12px 10px !important;
        white-space: nowrap;
    }
    .badge-yes, .badge-no {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        color: white;
    }
    .badge-yes { background-color: #1cc88a; }
    .badge-no { background-color: #e74a3b; }
    .date-main { font-weight: bold; color: #2d3748; display: block; font-size: 14px; margin-bottom: 2px; }
    .time-sub { color: #a0aec0; font-size: 12px; display: flex; align-items: center; gap: 4px; }
    
    .stats-header {
        display: flex;
        justify-content: flex-end;
        gap: 15px;
        margin-bottom: 0;
    }
    .stat-pill {
        font-size: 13px;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 50px;
    }
    .stat-compliant { background: rgba(28, 200, 138, 0.1); color: #1cc88a; }
    .stat-noncompliant { background: rgba(231, 74, 59, 0.1); color: #e74a3b; }
    
    .tab-content-wrapper {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 0 12px 12px 12px;
        padding: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        width: 100%;
    }
</style>

<div class="tab-content-wrapper animated fadeIn">
    <div class="page-callout" style="margin-bottom: 20px; width: 100%;">
        <div>
            <p class="callout-title">Attendance Record: <?= htmlspecialchars($employeeInfo['full'] ?? 'Unknown Employee') ?></p>
            <p class="callout-subtitle"><?= htmlspecialchars($employeeInfo['designation'] ?? '') ?> | <?= htmlspecialchars($employeeInfo['department'] ?? '') ?></p>
        </div>
        <div>
            <button type="button" class="btn btn-danger shadow-sm close-inner-tab" data-tab="#emp-tab-<?= $emp_id ?>" title="Close this tab">
                <i class="fa fa-times"></i> Close Tab
            </button>
        </div>
    </div>

    <div style="background: #fff; border-radius: 12px; border: 1px solid #e3e6f0; overflow: hidden; width: 100%;">
        <?php if (isset($errorMsg)): ?>
            <div class="alert alert-danger m-3"><?= $errorMsg ?></div>
        <?php else: ?>
            <div class="p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="text-muted">Showing <?= $start ?> to <?= $end ?> of <?= $totalRecords ?> records</div>
                    <div class="stats-header">
                        <div class="stat-pill stat-compliant">
                            <i class="fa fa-check-circle"></i> Compliant: <?= $compliantCount ?>
                        </div>
                        <div class="stat-pill stat-noncompliant">
                            <i class="fa fa-times-circle"></i> Non-Compliant: <?= $nonCompliantCount ?>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table custom-table table-hover mb-0 w-100">
                        <thead class="bg-light">
                            <tr>
                                <th>Date & Time Recorded</th>
                                <th>Designation</th>
                                <th>Division</th>
                                <th>Unit</th>
                                <th>Area of Assignment</th>
                                <th>With ID</th>
                                <th>Proper Attire</th>
                                <th>Compliant</th>
                                <th class="text-center">Photo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($records)): ?>
                                <?php foreach ($records as $row): 
                                    $timestamp = strtotime($row['time_recorded']);
                                    $dateStr = date('M d, Y', $timestamp);
                                    $timeStr = date('h:i A', $timestamp);
                                    
                                    // Required for the modal popup time display
                                    $fullDateTime = date('M d, Y - h:i A', $timestamp);

                                    $isWithId = in_array(strtolower(trim((string)$row['with_id'])), ['1', 'yes', 'true', 'y']);
                                    $isProperAttire = in_array(strtolower(trim((string)$row['is_asean'])), ['1', 'yes', 'true', 'y']);
                                    $isCompliant = ((int)$row['is_compliant'] === 1);
                                    $unit = isset($row['unit']) && !empty($row['unit']) ? $row['unit'] : 'N/A';
                                ?>
                                    <tr>
                                        <td>
                                            <span class="date-main"><?= htmlspecialchars($dateStr) ?></span>
                                            <span class="time-sub"><i class="fa fa-clock-o"></i> <?= htmlspecialchars($timeStr) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($row['designation'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($row['division'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($unit) ?></td>
                                        <td><?= htmlspecialchars($row['area_of_assignment'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php if ($isWithId): ?>
                                                <span class="badge-yes"><i class="fa fa-check"></i> Yes</span>
                                            <?php else: ?>
                                                <span class="badge-no"><i class="fa fa-times"></i> No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isProperAttire): ?>
                                                <span class="badge-yes"><i class="fa fa-check"></i> Yes</span>
                                            <?php else: ?>
                                                <span class="badge-no"><i class="fa fa-times"></i> No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isCompliant): ?>
                                                <span class="badge-yes"><i class="fa fa-check"></i> Yes</span>
                                            <?php else: ?>
                                                <span class="badge-no"><i class="fa fa-times"></i> No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if (!empty($row['photo_path'])): ?>
                                                <?php $safePath = htmlspecialchars($row['photo_path'], ENT_QUOTES, 'UTF-8'); ?>
                                                <img src="<?= $safePath ?>" 
                                                     alt="Photo" 
                                                     class="shadow-sm"
                                                     style="width: 45px; height: 45px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid #e3e6f0; transition: transform 0.2s;" 
                                                     onmouseover="this.style.transform='scale(1.1)'" 
                                                     onmouseout="this.style.transform='scale(1)'"
                                                     onclick="viewAttendancePhoto('<?= $safePath ?>', '<?= $fullDateTime ?>')" 
                                                     title="Click to view full image">
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border px-2 py-1 fw-normal"><i class="fa fa-eye-slash"></i> No Photo</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">
                                        <i class="fa fa-folder-open-o fa-2x mb-2 d-block"></i>
                                        No attendance records found for this employee.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalRecords > 0): ?>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">Page <?= $page ?> of <?= $totalPages ?></div>
                        <nav aria-label="Attendance pagination">
                            <ul class="pagination mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item"><a class="page-link attendance-page-link" href="get_employee_attendance.php?emp_id=<?= $emp_id ?>&page=<?php echo $page - 1; ?>" data-page="<?php echo $page - 1; ?>">&laquo; Prev</a></li>
                                <?php else: ?>
                                    <li class="page-item disabled"><span class="page-link">&laquo; Prev</span></li>
                                <?php endif; ?>

                                <?php
                                    $adjacents = 2;
                                    $startPage = max(1, $page - $adjacents);
                                    $endPage = min($totalPages, $page + $adjacents);

                                    if ($startPage > 1) {
                                        echo '<li class="page-item"><a class="page-link attendance-page-link" href="get_employee_attendance.php?emp_id='.$emp_id.'&page=1" data-page="1">1</a></li>';
                                        if ($startPage > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }

                                    for ($p = $startPage; $p <= $endPage; $p++) {
                                        if ($p == $page) {
                                            echo '<li class="page-item active" aria-current="page"><span class="page-link">'.$p.'</span></li>';
                                        } else {
                                            echo '<li class="page-item"><a class="page-link attendance-page-link" href="get_employee_attendance.php?emp_id='.$emp_id.'&page='.$p.'" data-page="'.$p.'">'.$p.'</a></li>';
                                        }
                                    }

                                    if ($endPage < $totalPages) {
                                        if ($endPage < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        echo '<li class="page-item"><a class="page-link attendance-page-link" href="get_employee_attendance.php?emp_id='.$emp_id.'&page='.$totalPages.'" data-page="'.$totalPages.'">'.$totalPages.'</a></li>';
                                    }
                                ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item"><a class="page-link attendance-page-link" href="get_employee_attendance.php?emp_id=<?= $emp_id ?>&page=<?php echo $page + 1; ?>" data-page="<?php echo $page + 1; ?>">Next &raquo;</a></li>
                                <?php else: ?>
                                    <li class="page-item disabled"><span class="page-link">Next &raquo;</span></li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>

            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function($){
    var empId = <?= json_encode($emp_id) ?>;
    var $tab = $('#emp-tab-' + empId);

    // Delegated handler for pagination links inside this tab
    $tab.off('click', '.attendance-page-link').on('click', '.attendance-page-link', function(e){
        e.preventDefault();
        var page = $(this).data('page') || 1;
        $tab.html('<div class="text-center p-4 bg-white rounded shadow-sm"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i> <p class="mt-2 text-muted">Loading attendance records...</p></div>');
        $.get('get_employee_attendance.php', { emp_id: empId, page: page })
            .done(function(html){
                $tab.html(html);
            })
            .fail(function(){
                $tab.html('<div class="alert alert-danger bg-white shadow-sm">Failed to load attendance. Please check your connection or database.</div>');
            });
    });

    // Close button inside tab
    $tab.off('click', '.close-inner-tab').on('click', '.close-inner-tab', function(){
        var tabId = $(this).data('tab');
        $('#attendanceTabs a[href="' + tabId + '"]').find('.close-tab').trigger('click');
    });
})(jQuery);
</script>