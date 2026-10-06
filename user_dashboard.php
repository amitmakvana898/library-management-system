<?php
// user_dashboard.php - Student Portal with Live Notifications, Quick Renew & Reading Stats
require_once __DIR__ . '/includes/config.php';
require_user();

$user = get_logged_user($conn);
$user_id = $user['id'];
$today_str = date('Y-m-d');

// User Metrics
$active_loans_cnt = $conn->query("SELECT COUNT(*) as c FROM book_issued WHERE user_id = '$user_id' AND status = 'issued'")->fetch_assoc()['c'] ?? 0;
$total_history_cnt = $conn->query("SELECT COUNT(*) as c FROM book_issued WHERE user_id = '$user_id'")->fetch_assoc()['c'] ?? 0;
$pending_req_cnt = $conn->query("SELECT COUNT(*) as c FROM book_requests WHERE user_id = '$user_id' AND status = 'pending'")->fetch_assoc()['c'] ?? 0;
$wishlist_cnt = $conn->query("SELECT COUNT(*) as c FROM wishlist WHERE user_id = '$user_id'")->fetch_assoc()['c'] ?? 0;

$unpaid_fine_res = $conn->query("SELECT SUM(fine) as s FROM book_issued WHERE user_id = '$user_id' AND fine_status = 'unpaid'");
$unpaid_fine = $unpaid_fine_res ? floatval($unpaid_fine_res->fetch_assoc()['s']) : 0;

// Fetch Student Notifications
$notifs_res = $conn->query("SELECT * FROM notifications WHERE user_id = '$user_id' ORDER BY id DESC LIMIT 5");
$unread_notifs_cnt = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id = '$user_id' AND is_read = 0")->fetch_assoc()['c'] ?? 0;

// Currently borrowed books
$current_books = $conn->query("
    SELECT bi.*, b.book_img, b.authorname, b.category, b.title as catalog_title 
    FROM book_issued bi 
    LEFT JOIN books b ON (bi.bookid = b.id OR bi.bookid = b.bookid) 
    WHERE bi.user_id = '$user_id' AND bi.status = 'issued' 
    ORDER BY bi.due_date ASC
");

// Recent reading history
$history_books = $conn->query("
    SELECT bi.*, b.authorname, b.category, b.title as catalog_title 
    FROM book_issued bi 
    LEFT JOIN books b ON (bi.bookid = b.id OR bi.bookid = b.bookid) 
    WHERE bi.user_id = '$user_id' AND bi.status = 'returned' 
    ORDER BY bi.id DESC 
    LIMIT 5
");

$is_dashboard = true;
$page_title = "Member Dashboard";
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/includes/user_sidebar.php'; ?>

    <div class="dashboard-main">
        <div class="dashboard-topbar">
            <div>
                <h5 class="fw-bold mb-0 text-main">Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</h5>
                <small class="text-muted">Patron ID: #LMS-<?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></small>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <!-- Notifications Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-secondary-custom position-relative p-2 px-3" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-bell"></i>
                        <?php if ($unread_notifs_cnt > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2" style="width: 300px;">
                        <li class="p-2 border-bottom fw-bold text-dark d-flex justify-content-between align-items-center">
                            <span>Notifications</span>
                            <span class="badge bg-primary rounded-pill"><?php echo $unread_notifs_cnt; ?> New</span>
                        </li>
                        <?php if ($notifs_res && $notifs_res->num_rows > 0): ?>
                            <?php while ($n = $notifs_res->fetch_assoc()): ?>
                                <li class="p-2 border-bottom small">
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($n['title']); ?></div>
                                    <div class="text-muted"><?php echo htmlspecialchars($n['message']); ?></div>
                                    <small class="text-primary mt-1 d-block"><?php echo date('d M, h:i A', strtotime($n['created_at'])); ?></small>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="p-3 text-center text-muted small">No notifications right now.</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <a href="student_id_card.php" target="_blank" class="btn btn-sm btn-warning text-dark fw-bold">
                    <i class="fa-solid fa-id-card me-1"></i> ID Card
                </a>
                <button class="theme-toggle btn btn-sm btn-secondary-custom px-3" title="Toggle Day/Night Theme">
                    <i class="fa-solid fa-moon"></i>
                </button>
            </div>
        </div>

        <div class="dashboard-content">
            <?php display_flash(); ?>

            <!-- Metrics Cards -->
            <div class="row g-3 mb-4">
                <?php 
                    $quota_pct = min(100, round(($active_loans_cnt / 3) * 100)); 
                    $quota_slots = max(0, 3 - $active_loans_cnt);
                ?>
                <div class="col-xl-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="fa-solid fa-book-open-reader"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="stat-value"><?php echo $active_loans_cnt; ?><small class="fs-6 text-muted">/3</small></span>
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-0" style="font-size: 0.7rem; font-weight: 700;"><?php echo $quota_pct; ?>% Used</span>
                            </div>
                            <div class="stat-label mb-1">Borrowed Quota</div>
                            <!-- Visual Progress Capsule -->
                            <div class="progress rounded-pill" style="height: 6px; background: rgba(99, 102, 241, 0.15);">
                                <div class="progress-bar rounded-pill" role="progressbar" style="width: <?php echo $quota_pct; ?>%; background: linear-gradient(90deg, #6366f1 0%, #06b6d4 100%);" aria-valuenow="<?php echo $quota_pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                                <?php if ($quota_slots > 0): ?>
                                    <span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i><?php echo $quota_slots; ?> slot<?php echo $quota_slots > 1 ? 's' : ''; ?> open</span>
                                <?php else: ?>
                                    <span class="text-warning fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i>Quota full</span>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo $total_history_cnt; ?></div>
                            <div class="stat-label">Total Books Read</div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fa-solid fa-heart"></i>
                        </div>
                        <div>
                            <div class="stat-value"><?php echo $wishlist_cnt; ?></div>
                            <div class="stat-label">Saved Wishlist</div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon <?php echo $unpaid_fine > 0 ? 'danger' : 'info'; ?>">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="stat-value">₹<?php echo number_format($unpaid_fine, 2); ?></div>
                            <div class="stat-label">Outstanding Dues</div>
                            <?php if ($unpaid_fine > 0): ?>
                                <button type="button" class="btn btn-sm btn-danger py-0 px-2 rounded-pill mt-1 fw-bold" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#finePaymentModal">
                                    <i class="fa-solid fa-qrcode me-1"></i> Pay UPI Online
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Borrowed Books Section with 1-Click Renew -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-book-bookmark text-primary me-2"></i> Currently Borrowed Books (<?php echo $active_loans_cnt; ?>)</h6>
                    <a href="my_issued_books.php" class="small text-primary fw-semibold">View All Loans</a>
                </div>

                <?php if ($current_books && $current_books->num_rows > 0): ?>
                    <div class="row g-3">
                        <?php while ($cb = $current_books->fetch_assoc()): ?>
                            <?php 
                                $b_img = (!empty($cb['book_img']) && file_exists($cb['book_img'])) ? $cb['book_img'] : 'img1.jpg';
                                $is_over = ($cb['due_date'] < $today_str);
                                
                                $due = new DateTime($cb['due_date']);
                                $now = new DateTime($today_str);
                                $diff = $now->diff($due);
                                $days_left = $due >= $now ? $diff->days : -$diff->days;
                            ?>
                            <div class="col-lg-6">
                                <div class="custom-card p-3 d-flex gap-3 align-items-center <?php echo $is_over ? 'border-danger' : ''; ?>">
                                    <img src="<?php echo htmlspecialchars($b_img); ?>" alt="Book Cover" class="rounded shadow-sm" style="width: 70px; height: 95px; object-fit: cover;">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <span class="badge-category mb-1"><?php echo htmlspecialchars($cb['category'] ?? 'General'); ?></span>
                                        <?php $cb_title = !empty($cb['book_title']) ? $cb['book_title'] : (!empty($cb['catalog_title']) ? $cb['catalog_title'] : 'Book #'.$cb['bookid']); ?>
                                        <h6 class="fw-bold text-dark text-truncate mb-1"><?php echo htmlspecialchars($cb_title); ?></h6>
                                        <p class="text-muted small mb-2">Due Date: <strong><?php echo date('d M, Y', strtotime($cb['due_date'])); ?></strong></p>
                                        
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <?php if ($days_left < 0): ?>
                                                <span class="badge-pill-danger"><i class="fa-solid fa-triangle-exclamation"></i> Overdue (<?php echo abs($days_left); ?>D)</span>
                                            <?php elseif ($days_left <= 2): ?>
                                                <span class="badge-pill-warning"><i class="fa-solid fa-clock"></i> Due in <?php echo $days_left; ?>D</span>
                                            <?php else: ?>
                                                <span class="badge-pill-success"><i class="fa-solid fa-calendar-check"></i> <?php echo $days_left; ?> days left</span>
                                            <?php endif; ?>

                                            <?php if (!$is_over): ?>
                                                <a href="renew_book.php?id=<?php echo $cb['id']; ?>" class="btn btn-sm btn-outline-primary py-0 px-2" title="Extend Loan by +7 Days" onclick="return confirm('Extend borrowing duration by +7 days?');">
                                                    <i class="fa-solid fa-arrows-rotate me-1"></i> Renew +7D
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="custom-card p-4 text-center">
                        <i class="fa-solid fa-book-open fs-2 text-muted mb-2"></i>
                        <h6 class="fw-bold text-dark">No Books Currently Borrowed</h6>
                        <p class="text-muted small mb-3">Your 3 book borrowing slots are open! Browse our catalog to reserve books.</p>
                        <a href="books.php" class="btn btn-sm btn-primary-custom px-4">Browse Catalog</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Reading History Table -->
            <div class="custom-table-card">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Recent Returned Books</h6>
                    <a href="user_history.php" class="small text-primary fw-semibold">Complete History</a>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Returned On</th>
                                <th>Late Fine</th>
                                <th>Status</th>
                                <th class="text-end">Slip</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($history_books && $history_books->num_rows > 0): ?>
                                <?php while ($hb = $history_books->fetch_assoc()): ?>
                                    <?php $hb_title = !empty($hb['book_title']) ? $hb['book_title'] : (!empty($hb['catalog_title']) ? $hb['catalog_title'] : 'Book #'.$hb['bookid']); ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($hb_title); ?></td>
                                        <td><?php echo date('d M, Y', strtotime($hb['issue_date'])); ?></td>
                                        <td><?php echo date('d M, Y', strtotime($hb['due_date'])); ?></td>
                                        <td><?php echo date('d M, Y', strtotime($hb['return_date'])); ?></td>
                                        <td>
                                            <?php if ($hb['fine'] > 0): ?>
                                                <span class="text-danger fw-bold">₹<?php echo number_format($hb['fine'], 2); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">₹0.00</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge-pill-success"><i class="fa-solid fa-check"></i> Returned</span></td>
                                        <td class="text-end">
                                            <a href="print_receipt.php?id=<?php echo $hb['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2" title="Print Return Slip">
                                                <i class="fa-solid fa-print"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No completed borrow history yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
require_once __DIR__ . '/includes/fine_payment_modal.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
