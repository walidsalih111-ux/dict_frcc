<?php
// --- DATE LOGIC (WEEKLY OR UNLOCKED DATE REFRESH) ---
// Calculate the most recent Monday at 12:00 AM (00:00:00)
$dt = new DateTime();
$today = new DateTime();
$today->setTime(0, 0, 0);

if ($dt->format('N') != 1) { // If today is not Monday (1)
    $dt->modify('last monday');
}
$dt->setTime(0, 0, 0);
$startDate = clone $dt;

// Check for any recently unlocked date that is <= today
try {
    $stmt = $pdo->prepare("SELECT MAX(target_date) FROM unlocked_dates WHERE target_date <= :today");
    $stmt->execute(['today' => $today->format('Y-m-d')]);
    $recentUnlockedDate = $stmt->fetchColumn();
    
    if ($recentUnlockedDate) {
        $unlockedDt = new DateTime($recentUnlockedDate);
        $unlockedDt->setTime(0, 0, 0);
        
        // If the unlocked date is newer than the last Monday, use it as the new start date
        if ($unlockedDt > $startDate) {
            $startDate = clone $unlockedDt;
        }
    }
} catch (PDOException $e) {
    // Table might not exist yet, proceed with normal Monday logic
}

$startOfWeek = $startDate->format('Y-m-d H:i:s');
$displayMondayDate = $startDate->format('F d, Y'); // For the modal header display

// --- DATA TABLE PAGINATION & FETCHING ---
// Define allowed limits and get current page/limit from URL
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], [10, 25, 50]) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch total records for calculating pagination pages (Filtered by this week/unlocked date)
$countSql = "SELECT COUNT(*) FROM attendance_record a 
             JOIN employees e ON a.emp_id = e.emp_id 
             WHERE a.time_recorded >= :startOfWeek";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute(['startOfWeek' => $startOfWeek]);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Fetch the attendance records across all employees (Filtered by this week/unlocked date)
$tableSql = "SELECT a.designation, a.with_id, a.is_asean, a.status, a.is_compliant, a.time_recorded, a.photo_path, 
               e.full, e.area_of_assignment, e.department, e.unit 
        FROM attendance_record a
        JOIN employees e ON a.emp_id = e.emp_id
        WHERE a.time_recorded >= :startOfWeek
        ORDER BY a.time_recorded DESC
        LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

$tableStmt = $pdo->prepare($tableSql);
$tableStmt->execute(['startOfWeek' => $startOfWeek]);
$attendance_records = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ========================================== -->
<!-- MODAL: DATA TABLE                          -->
<!-- ========================================== -->
<div class="modal fade" id="tableRecordsModal" tabindex="-1" aria-labelledby="tableRecordsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content table-modal-content">
            <div class="modal-header border-bottom-0 pb-0 align-items-start pt-3">
                <h5 class="modal-title text-dark fw-bold mt-1" id="tableRecordsModalLabel"><i class="bi bi-card-list me-2 text-inspinia"></i> Recent Attendance Records</h5>
                
                <!-- Display Current Start Date & Refresh Reminder -->
                <div class="ms-auto me-3 d-flex flex-column align-items-end">
                    <span class="text-muted small fw-medium" style="background: #f8f9fa; padding: 5px 12px; border-radius: 6px; border: 1px solid #dee2e6;">
                        <i class="bi bi-calendar-week me-1"></i><?php echo htmlspecialchars($displayMondayDate); ?>
                    </span>
                    <small class="text-muted mt-1" style="font-size: 11px;">
                        <i class="bi bi-info-circle me-1"></i>Refreshes every Monday or Unlocked Date
                    </small>
                </div>

                <button type="button" class="btn-close mt-1" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pb-4">
                
                <!-- Show Entries Dropdown -->
                <div class="row mb-3 mt-2 align-items-center">
                    <div class="col-sm-12">
                        <!-- Removed onchange submit to let AJAX handle the change smoothly -->
                        <form method="GET" action="index.php" class="d-inline-flex align-items-center" id="entriesForm" onsubmit="return false;">
                            <label class="mb-0 me-2 text-muted fw-normal">Show</label>
                            <select name="limit" class="form-select form-select-sm w-auto d-inline-block shadow-none">
                                <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                            </select>
                            <label class="mb-0 ms-2 text-muted fw-normal">entries</label>
                            <input type="hidden" name="page" value="1"> 
                        </form>
                    </div>
                </div>
                
                <!-- Main Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Employee Name</th>
                                <th class="text-center">Status</th>   
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($attendance_records) > 0): ?>
                                <?php foreach ($attendance_records as $record): ?>
                                    <?php 
                                        $hasId = ($record['with_id'] === 'Yes');
                                        $hasProperAttire = ($record['is_asean'] === 'Yes');
                                        
                                        // Use db fields directly
                                        $isCompliant = ($record['is_compliant'] == 1);

                                        // Format Date & Time for passing to photo modal
                                        $formattedDateTime = date('M d, Y - h:i A', strtotime($record['time_recorded']));
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><i class="bi bi-clock me-1 text-muted"></i><?php echo date('h:i A', strtotime($record['time_recorded'])); ?></strong>
                                        </td>
                                        
                                        <td>
                                            <strong><?php echo htmlspecialchars($record['full'] ?? 'N/A'); ?></strong><br>
                                            <small class="text-muted d-block mt-1" style="line-height: 1.2;">
                                                <?php echo htmlspecialchars($record['designation'] ?? 'N/A'); ?>
                                            </small>
                                            <small class="text-muted d-block mt-1" style="line-height: 1.2;">
                                                <i class="bi bi-diagram-3 me-1"></i><?php echo htmlspecialchars(!empty($record['unit']) ? $record['unit'] : (!empty($record['department']) ? $record['department'] : 'N/A')); ?>
                                            </small>
                                        </td>
                                        
                                        <td class="text-center">
                                            <?php if (isset($record['status']) && $record['status'] === 'Late'): ?>
                                                <span class="label label-danger"><i class="bi bi-exclamation-circle me-1"></i>Late</span>
                                            <?php else: ?>
                                                <span class="label label-primary"><i class="bi bi-check-circle me-1"></i>Signed In</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-5">
                                        <h5 class="fw-medium mb-1 mt-3">No Records Yet Since Reset</h5>
                                        <p class="small text-muted mb-4">Waiting for the first attendance entry since <?php echo htmlspecialchars($displayMondayDate); ?>.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Pagination -->
                <div class="row mt-3 align-items-center">
                    <div class="col-sm-12 col-md-5">
                        <?php
                            $startEntry = ($totalRecords > 0) ? $offset + 1 : 0;
                            $endEntry = min($offset + $limit, $totalRecords);
                        ?>
                        <div class="text-muted" style="font-size: 13px;">
                            Showing <?php echo $startEntry; ?> to <?php echo $endEntry; ?> of <?php echo $totalRecords; ?> entries
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-7 d-flex justify-content-md-end justify-content-center mt-3 mt-md-0">
                        <?php if ($totalPages > 1): ?>
                            <ul class="pagination pagination-sm mb-0 list-unstyled d-flex">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?limit=<?php echo $limit; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                                </li>
                                
                                <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    
                                    if ($startPage > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?limit=' . $limit . '&page=1">1</a></li>';
                                        if ($startPage > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }

                                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?limit=<?php echo $limit; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; 
                                    
                                    if ($endPage < $totalPages) {
                                        if ($endPage < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        echo '<li class="page-item"><a class="page-link" href="?limit=' . $limit . '&page=' . $totalPages . '">' . $totalPages . '</a></li>';
                                    }
                                ?>

                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?limit=<?php echo $limit; ?>&page=<?php echo $page + 1; ?>">Next</a>
                                </li>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- AJAX Pagination Script for the Modal -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const modal = document.getElementById('tableRecordsModal');
    if (!modal) return;
    
    // Listen for clicks on pagination links
    modal.addEventListener('click', function(e) {
        const pageLink = e.target.closest('.page-link');
        // Prevent default behavior if it's a valid link
        if (pageLink && pageLink.hasAttribute('href') && !pageLink.parentElement.classList.contains('disabled')) {
            e.preventDefault();
            updateModalContent(pageLink.getAttribute('href'));
        }
    });

    // Listen for changes on the Show Entries dropdown
    modal.addEventListener('change', function(e) {
        if (e.target.name === 'limit') {
            const limit = e.target.value;
            const url = new URL(window.location.href);
            url.searchParams.set('limit', limit);
            url.searchParams.set('page', 1); // Reset to page 1 on limit change
            updateModalContent(url.toString());
        }
    });

    // Function to fetch and replace modal body smoothly
    function updateModalContent(url) {
        const modalBody = modal.querySelector('.modal-body');
        
        // Visual feedback for loading state
        modalBody.style.opacity = '0.5';
        modalBody.style.pointerEvents = 'none';

        fetch(url)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newBody = doc.querySelector('#tableRecordsModal .modal-body');
                
                if (newBody) {
                    modalBody.innerHTML = newBody.innerHTML;
                }
                
                // Restore interactivity
                modalBody.style.opacity = '1';
                modalBody.style.pointerEvents = 'auto';
                
                // Update URL silently so refresh still works on the current page context
                window.history.pushState({path: url}, '', url);
            })
            .catch(error => {
                console.error('Error fetching pagination data:', error);
                modalBody.style.opacity = '1';
                modalBody.style.pointerEvents = 'auto';
            });
    }
});
</script>