<?php
// fines_management.php - Overdue Loans & Penalty Settlement (Cyber Emerald Edition)
require_once __DIR__ . '/includes/config.php';
require_admin();

$site = get_site_settings($conn);
$fine_rate = floatval($site['fine_per_day'] ?? 5.0);
$today_str = date('Y-m-d');

// Handle Mark Fine as Paid / Waived (with AJAX Live Mode Support)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $act = $_GET['action'];
    $iid = intval($_GET['id']);
    $is_ajax = isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    
    // Fetch current loan record to calculate correct amount if not set
    $chk = $conn->query("SELECT * FROM book_issued WHERE id = $iid LIMIT 1");
    if ($chk && $chk->num_rows > 0) {
        $rec = $chk->fetch_assoc();
        $due = $rec['due_date'];
        $ret = (!empty($rec['return_date']) && $rec['return_date'] !== '0000-00-00') ? $rec['return_date'] : $today_str;
        
        $d_diff = 0;
        if ($ret > $due) {
            $d1 = new DateTime($due);
            $d2 = new DateTime($ret);
            $d_diff = $d2->diff($d1)->days;
        }
        $calculated_fine = ($rec['fine'] > 0) ? floatval($rec['fine']) : ($d_diff * $fine_rate);

        if ($act === 'pay') {
            $conn->query("UPDATE book_issued SET fine = '$calculated_fine', fine_status = 'paid' WHERE id = $iid");
            $msg = 'Fine of ₹' . number_format($calculated_fine, 2) . ' marked as PAID & Cleared for Loan #' . $iid . '!';
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $msg, 'status' => 'paid', 'id' => $iid, 'fine' => number_format($calculated_fine, 2)]);
                exit();
            }
            set_flash('success', $msg);
        } elseif ($act === 'waive') {
            $conn->query("UPDATE book_issued SET fine = '$calculated_fine', fine_status = 'waived' WHERE id = $iid");
            $msg = 'Fine of ₹' . number_format($calculated_fine, 2) . ' WAIVED and exempted for Loan #' . $iid . '.';
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $msg, 'status' => 'waived', 'id' => $iid, 'fine' => number_format($calculated_fine, 2)]);
                exit();
            }
            set_flash('success', $msg);
        }
    }
    header("Location: fines_management.php");
    exit();
}

// Compute Statistics
$total_fines_collected = $conn->query("SELECT SUM(fine) as s FROM book_issued WHERE fine_status = 'paid'")->fetch_assoc()['s'] ?? 0;
$total_fines_waived = $conn->query("SELECT SUM(fine) as s FROM book_issued WHERE fine_status = 'waived'")->fetch_assoc()['s'] ?? 0;
$unpaid_count = $conn->query("SELECT COUNT(*) as c FROM book_issued WHERE (fine > 0 AND fine_status = 'unpaid') OR (status = 'issued' AND due_date < '$today_str' AND fine_status = 'unpaid')")->fetch_assoc()['c'] ?? 0;
$paid_count = $conn->query("SELECT COUNT(*) as c FROM book_issued WHERE fine_status = 'paid'")->fetch_assoc()['c'] ?? 0;
$waived_count = $conn->query("SELECT COUNT(*) as c FROM book_issued WHERE fine_status = 'waived'")->fetch_assoc()['c'] ?? 0;

// Fetch all records with overdue or fines
$fines_records = $conn->query("
    SELECT bi.*, u.username, u.email, u.mobileno, u.profile_img, b.title as catalog_title, b.book_img, b.authorname 
    FROM book_issued bi 
    LEFT JOIN users u ON bi.user_id = u.id 
    LEFT JOIN books b ON (bi.bookid = b.id OR bi.bookid = b.bookid) 
    WHERE bi.fine > 0 OR (bi.status = 'issued' AND bi.due_date < '$today_str') OR bi.fine_status = 'paid' OR bi.fine_status = 'waived' 
    ORDER BY bi.id DESC
");

$is_dashboard = true;
$page_title = "Late Fines & Overdue Management";
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/includes/admin_sidebar.php'; ?>

    <div class="dashboard-main">
        <!-- Executive Header Topbar -->
        <div class="dashboard-topbar">
            <div class="d-flex align-items-center gap-3">
                <a href="admin_dashboard.php" class="btn btn-sm btn-secondary-custom px-3 py-2 rounded-3" title="Back to Dashboard">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back
                </a>
                <div>
                    <h5 class="fw-bold mb-0 text-main font-space">Overdue Loans & Fine Penalties</h5>
                    <small class="text-muted">Track outstanding dues, collect late fines, or waive penalty fees</small>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="send_reminders.php" class="btn btn-sm btn-amber-custom px-3 py-2 rounded-3" onclick="return confirm('Send automated return reminder notifications to all members with approaching or overdue loans?');">
                    <i class="fa-solid fa-bell me-1"></i> Send Reminders
                </a>
                <a href="export_reports.php?type=fines" class="btn btn-sm btn-secondary-custom px-3 py-2 rounded-3">
                    <i class="fa-solid fa-file-excel text-success me-1"></i> Export Fines
                </a>
                <a href="returnbook.php" class="btn btn-sm btn-primary-custom px-3 py-2 rounded-3">
                    <i class="fa-solid fa-arrow-rotate-left me-1"></i> Return Desk
                </a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php display_flash(); ?>

            <!-- Metrics Summary Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card h-100">
                        <div class="stat-icon-wrap" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold text-main font-monospace">₹<?php echo number_format($total_fines_collected, 2); ?></div>
                            <div class="text-muted small fw-semibold">Total Fines Collected (<?php echo $paid_count; ?> Paid)</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card h-100">
                        <div class="stat-icon-wrap" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold text-main"><?php echo $unpaid_count; ?> Accounts</div>
                            <div class="text-muted small fw-semibold">Pending / Unpaid Dues</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card h-100">
                        <div class="stat-icon-wrap" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
                            <i class="fa-solid fa-hand-holding-heart"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold text-main font-monospace">₹<?php echo number_format($total_fines_waived, 2); ?></div>
                            <div class="text-muted small fw-semibold">Total Fines Waived (<?php echo $waived_count; ?>)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fines Table Card -->
            <div class="custom-table-card">
                <div class="p-4 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h6 class="fw-bold text-main mb-0 font-space">
                            <i class="fa-solid fa-receipt text-primary me-2"></i> Overdue & Fine Settlement Ledger
                        </h6>
                        <small class="text-muted">Review, collect payment, or waive overdue penalty fees</small>
                    </div>

                    <!-- Instant Filter Tabs -->
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-secondary-custom active-tab-btn fine-filter-btn" data-filter="all">
                            All Records (<?php echo $fines_records ? $fines_records->num_rows : 0; ?>)
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning fine-filter-btn" data-filter="unpaid">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Unpaid (<?php echo $unpaid_count; ?>)
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success fine-filter-btn" data-filter="paid">
                            <i class="fa-solid fa-circle-check me-1"></i> Paid (<?php echo $paid_count; ?>)
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info fine-filter-btn" data-filter="waived">
                            <i class="fa-solid fa-hand-holding-heart me-1"></i> Waived (<?php echo $waived_count; ?>)
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="custom-table mb-0" id="finesTable">
                        <thead>
                            <tr>
                                <th>Book Details</th>
                                <th>Borrower Patron</th>
                                <th>Due Date</th>
                                <th>Overdue Days</th>
                                <th>Fine Calculated</th>
                                <th>Payment Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($fines_records && $fines_records->num_rows > 0): ?>
                                <?php while ($row = $fines_records->fetch_assoc()): ?>
                                    <?php 
                                        $due = $row['due_date'];
                                        $ret = (!empty($row['return_date']) && $row['return_date'] !== '0000-00-00') ? $row['return_date'] : $today_str;
                                        $is_active = ($row['status'] === 'issued');
                                        
                                        // Overdue calculation
                                        $d_diff = 0;
                                        if ($ret > $due) {
                                            $d1 = new DateTime($due);
                                            $d2 = new DateTime($ret);
                                            $d_diff = $d2->diff($d1)->days;
                                        }

                                        $fine_display = ($row['fine'] > 0) ? floatval($row['fine']) : ($d_diff * $fine_rate);
                                        $f_status = strtolower(trim($row['fine_status'] ?? 'unpaid'));
                                        if (empty($f_status)) $f_status = 'unpaid';
                                        
                                        $display_title = !empty($row['book_title']) ? $row['book_title'] : (!empty($row['catalog_title']) ? $row['catalog_title'] : 'Catalog Book #'.$row['bookid']);
                                        $b_img = (!empty($row['book_img']) && file_exists($row['book_img'])) ? $row['book_img'] : 'img1.jpg';
                                        $u_img = (!empty($row['profile_img']) && file_exists($row['profile_img'])) ? $row['profile_img'] : 'uploads/profile_1.jpg';
                                        if (!file_exists($u_img)) $u_img = 'admin.png';
                                    ?>
                                    <tr class="fine-row" data-status="<?php echo htmlspecialchars($f_status); ?>">
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?php echo htmlspecialchars($b_img); ?>" alt="Cover" class="rounded-3 shadow-sm flex-shrink-0" style="width: 42px; height: 58px; object-fit: cover;">
                                                <div>
                                                    <div class="fw-bold text-main"><?php echo htmlspecialchars($display_title); ?></div>
                                                    <small class="text-muted">Loan ID: #<?php echo $row['id']; ?> &bull; Book: #<?php echo htmlspecialchars($row['bookid']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?php echo htmlspecialchars($u_img); ?>" alt="User" class="rounded-circle border flex-shrink-0" style="width: 32px; height: 32px; object-fit: cover;">
                                                <div>
                                                    <div class="fw-semibold text-main small"><?php echo htmlspecialchars($row['username'] ?? 'Patron #'.$row['user_id']); ?></div>
                                                    <small class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($row['email'] ?? ''); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small fw-semibold <?php echo ($today_str > $due && $is_active) ? 'text-danger' : 'text-main'; ?>">
                                                <i class="fa-regular fa-calendar me-1 text-muted"></i> <?php echo date('d M, Y', strtotime($due)); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($d_diff > 0): ?>
                                                <span class="badge-pill-danger">
                                                    <i class="fa-solid fa-clock-rotate-left"></i> <?php echo $d_diff; ?> Days Late
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-pill-success">
                                                    <i class="fa-solid fa-check"></i> On Schedule
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fs-6 fw-bold text-main font-monospace">
                                                ₹<?php echo number_format($fine_display, 2); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($f_status === 'paid'): ?>
                                                <span class="badge-pill-success">
                                                    <i class="fa-solid fa-circle-check"></i> Paid & Cleared
                                                </span>
                                            <?php elseif ($f_status === 'waived'): ?>
                                                <span class="badge-pill-info">
                                                    <i class="fa-solid fa-hand-holding-heart"></i> Waived
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-pill-warning">
                                                    <i class="fa-solid fa-triangle-exclamation"></i> Unpaid
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2 align-items-center">
                                                <?php if ($f_status === 'unpaid'): ?>
                                                    <a href="fines_management.php?action=pay&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-emerald py-1 px-3 rounded-2 fw-bold ajax-action-btn" data-ajax="true" onclick="return confirm('Collect ₹<?php echo number_format($fine_display, 2); ?> and mark fine as Paid?');" title="Mark Paid & Collect Fine">
                                                        <i class="fa-solid fa-check me-1"></i> Mark Paid
                                                    </a>
                                                    <a href="fines_management.php?action=waive&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger py-1 px-2 rounded-2 fw-semibold ajax-action-btn" data-ajax="true" onclick="return confirm('Waive and exempt this fine penalty?');" title="Waive Fine">
                                                        <i class="fa-solid fa-xmark me-1"></i> Waive
                                                    </a>
                                                    <?php if ($is_active): ?>
                                                        <a href="returnbook.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary-custom py-1 px-2 fw-bold rounded-2" title="Process Book Return Desk">
                                                            <i class="fa-solid fa-arrow-rotate-left"></i> Return
                                                        </a>
                                                    <?php endif; ?>
                                                <?php elseif ($f_status === 'paid'): ?>
                                                    <span class="badge-pill-success"><i class="fa-solid fa-check-double"></i> Settled</span>
                                                    <a href="print_receipt.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-sm btn-secondary-custom py-1 px-2 rounded-2" title="Print Return Receipt">
                                                        <i class="fa-solid fa-print"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge-pill-info"><i class="fa-solid fa-hand-holding-heart"></i> Exempted</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-circle-check fs-2 text-success mb-2 d-block"></i>
                                        No pending fines or overdue loans! All member accounts are completely settled.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.fine-filter-btn');
    const rows = document.querySelectorAll('.fine-row');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => {
                b.classList.remove('active', 'btn-primary-custom');
                if (b.dataset.filter === 'unpaid') b.className = 'btn btn-sm btn-outline-warning fine-filter-btn';
                else if (b.dataset.filter === 'paid') b.className = 'btn btn-sm btn-outline-success fine-filter-btn';
                else if (b.dataset.filter === 'waived') b.className = 'btn btn-sm btn-outline-info fine-filter-btn';
                else b.className = 'btn btn-sm btn-secondary-custom fine-filter-btn';
            });

            this.classList.add('active', 'btn-primary-custom');
            const target = this.dataset.filter;

            rows.forEach(row => {
                const st = row.dataset.status;
                if (target === 'all' || st === target) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
