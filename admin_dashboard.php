<?php
// admin_dashboard.php - Quantum & Enterprise Librarian Command Center
require_once __DIR__ . '/includes/config.php';
require_admin();

$site = get_site_settings($conn);
$admin = get_logged_admin($conn);
$today_str = date('Y-m-d');

// 1. Live Metrics Computation
$total_books = $conn->query("SELECT COUNT(*) as c FROM books")->fetch_assoc()['c'] ?? 0;
$avail_copies_res = $conn->query("SELECT SUM(available_copies) as s, SUM(quantity) as q FROM books");
$avail_row = $avail_copies_res ? $avail_copies_res->fetch_assoc() : [];
$total_physical_copies = intval($avail_row['q'] ?? $total_books);
$avail_copies = intval($avail_row['s'] ?? $total_books);

$active_loans_cnt = $conn->query("SELECT COUNT(*) as c FROM book_issued WHERE status='issued'")->fetch_assoc()['c'] ?? 0;
$overdue_res = $conn->query("SELECT COUNT(*) as c FROM book_issued WHERE status='issued' AND due_date < '$today_str'");
$overdue_count = $overdue_res ? $overdue_res->fetch_assoc()['c'] : 0;

$total_users = $conn->query("SELECT COUNT(*) as c FROM users WHERE status='active'")->fetch_assoc()['c'] ?? 0;
$pending_requests = $conn->query("SELECT COUNT(*) as c FROM book_requests WHERE status='pending'")->fetch_assoc()['c'] ?? 0;
$unread_messages = $conn->query("SELECT COUNT(*) as c FROM messages WHERE status='unread'")->fetch_assoc()['c'] ?? 0;

$total_fines_collected = $conn->query("SELECT SUM(fine) as s FROM book_issued WHERE fine_status='paid'")->fetch_assoc()['s'] ?? 0;
$outstanding_fines = $conn->query("SELECT SUM(fine) as s FROM book_issued WHERE fine_status='unpaid'")->fetch_assoc()['s'] ?? 0;

// 2. Recent Circulation Feed (with Borrower Details)
$recent_issues = $conn->query("
    SELECT bi.*, u.username, u.email, u.profile_img, b.title as catalog_title, b.book_img, b.rack_no, b.category, b.authorname 
    FROM book_issued bi 
    LEFT JOIN users u ON bi.user_id = u.id 
    LEFT JOIN books b ON (bi.bookid = b.id OR bi.bookid = b.bookid) 
    ORDER BY bi.id DESC 
    LIMIT 10
");

// 3. Urgent Overdue Loans
$overdue_list = $conn->query("
    SELECT bi.*, u.username, u.email, u.mobileno, b.title as catalog_title, b.book_img 
    FROM book_issued bi 
    LEFT JOIN users u ON bi.user_id = u.id 
    LEFT JOIN books b ON (bi.bookid = b.id OR bi.bookid = b.bookid) 
    WHERE bi.status='issued' AND bi.due_date < '$today_str' 
    ORDER BY bi.due_date ASC 
    LIMIT 4
");

// 4. Category Distribution
$cat_summary = $conn->query("
    SELECT c.name, COUNT(b.id) as book_count 
    FROM categories c 
    LEFT JOIN books b ON c.name = b.category 
    GROUP BY c.id 
    ORDER BY book_count DESC 
    LIMIT 6
");

$is_dashboard = true;
$page_title = "Admin Command Center";
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/includes/admin_sidebar.php'; ?>

    <!-- Main Content Stream -->
    <div class="dashboard-main">
        <!-- Executive Header Banner -->
        <div class="dashboard-topbar">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h4 class="fw-bold mb-0 text-main font-space">Librarian Command Center</h4>
                    <span class="badge bg-success bg-opacity-25 text-success rounded-pill small px-2 py-1">
                        <i class="fa-solid fa-circle fa-2xs me-1 animate-pulse"></i> LIVE
                    </span>
                </div>
                <small class="text-muted">Master control panel for circulation, catalog, and patron management</small>
            </div>

            <!-- Quick Action Buttons -->
            <div class="d-flex align-items-center gap-2">
                <a href="scanner.php" class="btn btn-secondary-custom px-3 py-2">
                    <i class="fa-solid fa-qrcode text-warning"></i> Scanner
                </a>
                <a href="issuebooks.php" class="btn btn-primary-custom px-3 py-2 fw-bold">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Issue Book Desk
                </a>
            </div>
        </div>

        <!-- Dashboard Content Body -->
        <div class="dashboard-content">
            <?php display_flash(); ?>

            <!-- Overdue Emergency Radar Banner -->
            <?php if ($overdue_count > 0): ?>
                <div class="alert alert-warning border-0 shadow-md d-flex flex-column flex-md-row align-items-md-center justify-content-between p-3 p-md-4 mb-4 rounded-4" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(239, 68, 68, 0.15) 100%); border-left: 4px solid #f59e0b !important;">
                    <div class="d-flex align-items-center gap-3 mb-3 mb-md-0">
                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-warning text-dark flex-shrink-0" style="width: 48px; height: 48px; font-size: 1.3rem;">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-main mb-1"><?php echo $overdue_count; ?> Book Loan(s) Require Immediate Attention</h6>
                            <p class="text-muted small mb-0">These items have exceeded the loan duration. Collect late fees or dispatch automated notification reminders.</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="send_reminders.php" class="btn btn-sm btn-warning text-dark fw-bold px-3 py-2 rounded-3" onclick="return confirm('Send automated overdue reminder notifications to all students?');">
                            <i class="fa-solid fa-bell me-1"></i> Send Reminders
                        </a>
                        <a href="fines_management.php" class="btn btn-sm btn-dark px-3 py-2 rounded-3">
                            <i class="fa-solid fa-receipt me-1"></i> Overdue Radar
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- High-Impact Futuristic KPI Metric Cards -->
            <div class="row g-3 mb-4">
                <!-- KPI 1: Catalog Titles & Copies -->
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card h-100 position-relative overflow-hidden">
                        <div class="stat-icon primary">
                            <i class="fa-solid fa-book-sparkles"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-muted small fw-semibold text-uppercase">Catalog Titles</div>
                            <div class="stat-value text-main my-1 font-space"><?php echo number_format($total_books); ?></div>
                            <div class="d-flex align-items-center justify-content-between small text-muted">
                                <span><strong class="text-success"><?php echo $avail_copies; ?></strong> Available</span>
                                <span><?php echo $total_physical_copies; ?> Total</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPI 2: Active Circulations -->
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card h-100 position-relative overflow-hidden">
                        <div class="stat-icon warning">
                            <i class="fa-solid fa-hand-holding-box"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-muted small fw-semibold text-uppercase">Active Borrowed</div>
                            <div class="stat-value text-warning my-1 font-space"><?php echo number_format($active_loans_cnt); ?></div>
                            <div class="d-flex align-items-center justify-content-between small text-muted">
                                <span class="text-danger fw-bold"><?php echo $overdue_count; ?> Overdue</span>
                                <span>In-Hands</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPI 3: Registered Students / Patrons -->
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card h-100 position-relative overflow-hidden">
                        <div class="stat-icon info">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-muted small fw-semibold text-uppercase">Active Patrons</div>
                            <div class="stat-value text-info my-1 font-space"><?php echo number_format($total_users); ?></div>
                            <div class="d-flex align-items-center justify-content-between small text-muted">
                                <span><?php echo $pending_requests; ?> Requests</span>
                                <span><a href="manageusers.php" class="text-info fw-bold text-decoration-none">Manage &rarr;</a></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPI 4: Revenue & Late Fees -->
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card h-100 position-relative overflow-hidden">
                        <div class="stat-icon success">
                            <i class="fa-solid fa-circle-dollar-to-slot"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-muted small fw-semibold text-uppercase">Fines Collected</div>
                            <div class="stat-value text-success my-1 font-space">₹<?php echo number_format($total_fines_collected, 2); ?></div>
                            <div class="d-flex align-items-center justify-content-between small text-muted">
                                <span>Pending: <strong>₹<?php echo number_format($outstanding_fines, 2); ?></strong></span>
                                <span>Settled</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Executive Action Deck -->
            <div class="custom-card p-3 mb-4">
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="scanner.php" class="btn btn-secondary-custom py-2 px-3 fw-semibold">
                            <i class="fa-solid fa-qrcode text-warning me-1"></i> Live Camera Scanner
                        </a>
                        <a href="issuebooks.php" class="btn btn-primary-custom py-2 px-3">
                            <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Issue Book
                        </a>
                        <a href="returnbook.php" class="btn btn-secondary-custom py-2 px-3">
                            <i class="fa-solid fa-arrow-rotate-left text-info me-1"></i> Return Desk
                        </a>
                        <a href="addbook.php" class="btn btn-secondary-custom py-2 px-3">
                            <i class="fa-solid fa-circle-plus text-success me-1"></i> Add Title (with E-Book)
                        </a>
                        <a href="export_reports.php?type=circulation" class="btn btn-secondary-custom py-2 px-3">
                            <i class="fa-solid fa-file-excel text-success me-1"></i> Export Excel
                        </a>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <?php if ($pending_requests > 0): ?>
                            <a href="book_requests.php" class="btn btn-danger py-2 px-3 fw-bold rounded-3">
                                <i class="fa-solid fa-bell me-1"></i> <?php echo $pending_requests; ?> New Requests
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Left Column: Live Circulation Intelligence Stream -->
                <div class="col-lg-8">
                    <div class="custom-card h-100 d-flex flex-column p-4">
                        <!-- Stream Header & Filter Tabs -->
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 pb-3 border-bottom mb-3">
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <h5 class="fw-bold text-main mb-0 font-space">
                                        <i class="fa-solid fa-bolt-lightning text-primary me-2"></i> Live Circulation Intelligence
                                    </h5>
                                    <span class="badge bg-success bg-opacity-20 text-success rounded-pill small px-2 py-1" style="font-size: 0.7rem;">
                                        REAL-TIME FEED
                                    </span>
                                </div>
                                <small class="text-muted">Live ledger of active book loans, patron checkout flows, and returns</small>
                            </div>
                            
                            <a href="issuedhistory.php" class="btn btn-sm btn-secondary-custom rounded-pill px-3 align-self-start align-self-md-auto">
                                All Logs <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>

                        <!-- Filter Pill Bar -->
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-3 pb-2">
                            <button type="button" class="circ-tab-btn btn btn-sm btn-primary-custom active rounded-pill px-3 py-1" onclick="filterCirculation('all', this)">
                                All Stream
                            </button>
                            <button type="button" class="circ-tab-btn btn btn-sm btn-secondary-custom rounded-pill px-3 py-1" onclick="filterCirculation('issued', this)">
                                🟢 On Loan
                            </button>
                            <button type="button" class="circ-tab-btn btn btn-sm btn-secondary-custom rounded-pill px-3 py-1" onclick="filterCirculation('overdue', this)">
                                🔴 Overdue
                            </button>
                            <button type="button" class="circ-tab-btn btn btn-sm btn-secondary-custom rounded-pill px-3 py-1" onclick="filterCirculation('returned', this)">
                                🔵 Returned
                            </button>
                        </div>

                        <!-- Activity Cards Stream -->
                        <div class="circulation-stream-container flex-grow-1" id="circulationStreamList">
                            <?php if ($recent_issues && $recent_issues->num_rows > 0): ?>
                                <?php while ($row = $recent_issues->fetch_assoc()): ?>
                                    <?php 
                                        $is_ret = (strtolower($row['status']) === 'returned');
                                        $is_over = (!$is_ret && $row['due_date'] < $today_str);
                                        $b_img = (!empty($row['book_img']) && file_exists($row['book_img'])) ? $row['book_img'] : 'img1.jpg';
                                        $u_img = (!empty($row['profile_img']) && file_exists($row['profile_img'])) ? $row['profile_img'] : 'uploads/profile_1.jpg';
                                        if (!file_exists($u_img)) $u_img = 'admin.png';
                                        
                                        $filter_category = $is_ret ? 'returned' : ($is_over ? 'overdue' : 'issued');
                                        $display_title = !empty($row['book_title']) ? $row['book_title'] : (!empty($row['catalog_title']) ? $row['catalog_title'] : 'Catalog Book #'.$row['bookid']);
                                    ?>
                                    <div class="circulation-stream-item p-3 rounded-4 mb-3 border bg-card-surface transition-all" data-status="<?php echo $filter_category; ?>" style="background: var(--card-bg); border-color: var(--border-color) !important;">
                                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                                            <!-- Book Info -->
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="position-relative flex-shrink-0">
                                                    <img src="<?php echo htmlspecialchars($b_img); ?>" alt="Cover" class="rounded-3 shadow-sm" style="width: 46px; height: 64px; object-fit: cover;">
                                                    <span class="position-absolute bottom-0 end-0 translate-middle-y badge bg-dark text-white p-1" style="font-size: 0.6rem; border-radius: 4px;">#<?php echo htmlspecialchars($row['bookid']); ?></span>
                                                </div>
                                                <div>
                                                    <h6 class="fw-bold text-main mb-1"><?php echo htmlspecialchars($display_title); ?></h6>
                                                    <div class="d-flex align-items-center gap-2 flex-wrap small text-muted">
                                                        <span><i class="fa-solid fa-layer-group text-primary me-1"></i><?php echo htmlspecialchars($row['category'] ?? 'General'); ?></span>
                                                        <span>&bull;</span>
                                                        <span><i class="fa-solid fa-map-pin text-warning me-1"></i><?php echo htmlspecialchars($row['rack_no'] ?? 'Rack 1'); ?></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Borrower & Dates Flow -->
                                            <div class="d-flex align-items-center gap-3 p-2 px-3 rounded-3 flex-wrap patron-timeline-box">
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="<?php echo htmlspecialchars($u_img); ?>" alt="Borrower" class="rounded-circle border" style="width: 32px; height: 32px; object-fit: cover;">
                                                    <div>
                                                        <div class="fw-semibold text-main small"><?php echo htmlspecialchars($row['username'] ?? 'Patron #'.$row['user_id']); ?></div>
                                                        <small class="text-muted" style="font-size: 0.7rem;">ID: #LMS-<?php echo str_pad($row['user_id'], 4, '0', STR_PAD_LEFT); ?></small>
                                                    </div>
                                                </div>
                                                
                                                <div class="vr mx-1 opacity-25 d-none d-sm-block"></div>
                                                
                                                <div class="small">
                                                    <div class="text-muted" style="font-size: 0.68rem; font-weight: 600;">TIMELINE</div>
                                                    <div class="fw-semibold text-main" style="font-size: 0.8rem;">
                                                        <?php echo date('d M', strtotime($row['issue_date'])); ?> 
                                                        <i class="fa-solid fa-arrow-right text-muted mx-1" style="font-size: 0.7rem;"></i> 
                                                        <span class="<?php echo $is_over ? 'text-danger fw-bold' : ($is_ret ? 'text-success' : 'text-primary'); ?>">
                                                            <?php echo date('d M, Y', strtotime($row['due_date'])); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Status & Action Buttons -->
                                            <div class="d-flex align-items-center justify-content-between justify-content-md-end gap-2 flex-shrink-0">
                                                <div>
                                                    <?php if ($is_ret): ?>
                                                        <span class="badge-pill-success">
                                                            <i class="fa-solid fa-circle-check"></i> Returned
                                                        </span>
                                                    <?php elseif ($is_over): ?>
                                                        <span class="badge-pill-danger">
                                                            <i class="fa-solid fa-triangle-exclamation"></i> Overdue
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge-pill-info">
                                                            <i class="fa-solid fa-circle-dot"></i> On Loan
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="d-flex gap-1">
                                                    <a href="print_receipt.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-sm btn-secondary-custom py-2 px-3 rounded-3" title="Print Slip">
                                                        <i class="fa-solid fa-print"></i>
                                                    </a>
                                                    <?php if (!$is_ret): ?>
                                                        <a href="returnbook.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary-custom py-2 px-3 fw-bold rounded-3">
                                                            <i class="fa-solid fa-arrow-rotate-left me-1"></i> Return
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="p-5 text-center text-muted">
                                    <i class="fa-solid fa-boxes-stacked fs-2 d-block mb-2 text-primary"></i>
                                    <h6 class="fw-bold">No Circulation Records Found</h6>
                                    <p class="small mb-0">Use the Issue Book Desk to lend books to library members.</p>
                                </div>
                            <?php endif; ?>
                            
                            <div id="circ-empty-msg" class="p-4 text-center text-muted" style="display: none;">
                                <i class="fa-solid fa-filter-circle-xmark fs-2 d-block mb-2 text-warning"></i>
                                <span class="small fw-semibold">No transactions found matching this filter.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Urgent Overdue Radar & Categories -->
                <div class="col-lg-4">
                    <!-- Overdue Radar Card -->
                    <div class="custom-card mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-main mb-0"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> Critical Overdue Items</h6>
                            <span class="badge bg-danger rounded-pill"><?php echo $overdue_count; ?> Due</span>
                        </div>
                        <?php if ($overdue_list && $overdue_list->num_rows > 0): ?>
                            <div class="d-flex flex-column gap-3">
                                <?php while ($ov = $overdue_list->fetch_assoc()): ?>
                                    <?php $ov_title = !empty($ov['book_title']) ? $ov['book_title'] : (!empty($ov['catalog_title']) ? $ov['catalog_title'] : 'Catalog Book #'.$ov['bookid']); ?>
                                    <div class="p-3 bg-light rounded-3 border border-danger border-opacity-25">
                                        <div class="fw-bold text-main text-truncate"><?php echo htmlspecialchars($ov_title); ?></div>
                                        <div class="small text-muted mb-2">Borrower: <strong><?php echo htmlspecialchars($ov['username'] ?? 'Patron #'.$ov['user_id']); ?></strong></div>
                                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                             <small class="text-danger fw-bold"><i class="fa-solid fa-calendar-xmark me-1"></i> Due <?php echo date('d M, Y', strtotime($ov['due_date'])); ?></small>
                                             <a href="returnbook.php?id=<?php echo $ov['id']; ?>" class="btn btn-sm btn-danger py-1 px-3 fw-bold rounded-2">
                                                 Settle & Return
                                             </a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="p-4 bg-light rounded-3 text-center">
                                <i class="fa-solid fa-shield-check fs-2 text-success mb-2 d-block"></i>
                                <span class="small fw-semibold text-success">Zero Overdue Items</span>
                                <p class="text-muted small mb-0 mt-1">All borrowed library materials are within their permitted loan dates.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Category Inventory Distribution -->
                    <div class="custom-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-main mb-0"><i class="fa-solid fa-layer-group text-primary me-2"></i> Category Volumes</h6>
                            <a href="manage_categories.php" class="small text-primary fw-semibold">Manage</a>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <?php if ($cat_summary && $cat_summary->num_rows > 0): ?>
                                <?php while ($cs = $cat_summary->fetch_assoc()): ?>
                                    <?php 
                                        $cnt = intval($cs['book_count']);
                                        $pct = $total_books > 0 ? round(($cnt / $total_books) * 100) : 0;
                                    ?>
                                    <div class="p-2 px-3 rounded-3 bg-light d-flex flex-column gap-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-semibold small text-main"><?php echo htmlspecialchars($cs['name']); ?></span>
                                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill fw-bold"><?php echo $cnt; ?> Books</span>
                                        </div>
                                        <div class="progress" style="height: 4px; background: rgba(0,0,0,0.05);">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $pct; ?>%;" aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Live Clock Updater
setInterval(() => {
    const clockEl = document.getElementById('liveClock');
    if (clockEl) {
        const now = new Date();
        clockEl.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    }
}, 1000);

// Live Circulation Stream Filter Tabs
function filterCirculation(status, btn) {
    document.querySelectorAll('.circ-tab-btn').forEach(b => {
        b.classList.remove('active', 'btn-primary-custom');
        b.classList.add('btn-secondary-custom');
    });
    if (btn) {
        btn.classList.remove('btn-secondary-custom');
        btn.classList.add('active', 'btn-primary-custom');
    }

    const items = document.querySelectorAll('.circulation-stream-item');
    let visibleCount = 0;
    items.forEach(item => {
        const itemStatus = item.getAttribute('data-status');
        if (status === 'all' || itemStatus === status) {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    const emptyMsg = document.getElementById('circ-empty-msg');
    if (emptyMsg) {
        emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
