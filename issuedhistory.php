<?php
// issuedhistory.php - Complete Circulation History & Logs (Cyber Emerald Edition)
require_once __DIR__ . '/includes/config.php';
require_admin();

$status_filter = trim($_GET['status'] ?? '');
$search_query = trim($_GET['q'] ?? '');
$today_str = date('Y-m-d');

$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;

$where = " WHERE 1=1";
$params = [];
$types = "";

if (!empty($search_query)) {
    $where .= " AND (bi.book_title LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR CAST(bi.bookid AS CHAR) LIKE ?)";
    $like = "%{$search_query}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

if ($status_filter === 'issued') {
    $where .= " AND bi.status = 'issued'";
} elseif ($status_filter === 'returned') {
    $where .= " AND bi.status = 'returned'";
} elseif ($status_filter === 'overdue') {
    $where .= " AND bi.status = 'issued' AND bi.due_date < '{$today_str}'";
}

// 1. Total Count Query
$cnt_sql = "SELECT COUNT(*) as total FROM book_issued bi LEFT JOIN users u ON bi.user_id = u.id" . $where;
$cnt_stmt = $conn->prepare($cnt_sql);
if (!empty($params)) {
    $cnt_stmt->bind_param($types, ...$params);
}
$cnt_stmt->execute();
$total_rows = $cnt_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = max(1, ceil($total_rows / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// 2. Fetch Paginated Records
$sql = "
    SELECT bi.*, u.username, u.email, u.mobileno, u.profile_img, b.title as catalog_title, b.book_img, b.rack_no 
    FROM book_issued bi 
    LEFT JOIN users u ON bi.user_id = u.id 
    LEFT JOIN books b ON bi.bookid = b.bookid 
    $where
    ORDER BY bi.id DESC 
    LIMIT ? OFFSET ?
";
$params_page = $params;
$params_page[] = $per_page;
$params_page[] = $offset;
$types_page = $types . "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types_page, ...$params_page);
$stmt->execute();
$history = $stmt->get_result();

function build_history_page_url($page_num) {
    $p = $_GET;
    $p['page'] = $page_num;
    return 'issuedhistory.php?' . http_build_query($p);
}

$is_dashboard = true;
$page_title = "Circulation Logs";
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/includes/admin_sidebar.php'; ?>

    <div class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="d-flex align-items-center gap-3">
                <a href="admin_dashboard.php" class="btn btn-sm btn-secondary-custom px-3 py-2 rounded-3" title="Back to Dashboard">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back
                </a>
                <div>
                    <h5 class="fw-bold mb-0 text-main font-space">Circulation Ledger & Activity Logs</h5>
                    <small class="text-muted">Audit book issues, track due dates, and monitor fines history</small>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <a href="export_reports.php?type=circulation" class="btn btn-secondary-custom px-3 py-2">
                    <i class="fa-solid fa-file-excel text-success me-1"></i> Export Logs
                </a>
                <a href="issuebooks.php" class="btn btn-primary-custom px-3 py-2 fw-bold">
                    <i class="fa-solid fa-plus me-1"></i> Issue New Book
                </a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php display_flash(); ?>

            <!-- Filter Controls -->
            <div class="custom-card p-3 mb-4">
                <form method="GET" action="issuedhistory.php" class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" name="q" id="tableSearchInput" class="form-control" placeholder="Search by book title, borrower name, email, book ID..." value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">All Loan Records</option>
                            <option value="issued" <?php echo $status_filter === 'issued' ? 'selected' : ''; ?>>Active Loans (Issued)</option>
                            <option value="overdue" <?php echo $status_filter === 'overdue' ? 'selected' : ''; ?>>Overdue Loans</option>
                            <option value="returned" <?php echo $status_filter === 'returned' ? 'selected' : ''; ?>>Returned History</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary-custom w-100 py-2">Filter</button>
                        <?php if (!empty($search_query) || !empty($status_filter)): ?>
                            <a href="issuedhistory.php" class="btn btn-secondary-custom py-2 px-3" title="Reset Filters"><i class="fa-solid fa-rotate-left"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Table Card -->
            <div class="custom-table-card">
                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-main mb-0 font-space"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Master Circulation Logs</h6>
                        <small class="text-muted">Total recorded checkout and return transactions</small>
                    </div>
                    <span class="badge bg-primary bg-opacity-20 text-primary border border-primary border-opacity-30 rounded-pill px-3 py-2 fw-bold">
                        <?php echo $history ? $history->num_rows : 0; ?> Entries
                    </span>
                </div>
                
                <div class="table-responsive">
                    <table class="custom-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Book Title</th>
                                <th>Borrower</th>
                                <th>Issued On</th>
                                <th>Due Date</th>
                                <th>Returned On</th>
                                <th>Fine Collected</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($history && $history->num_rows > 0): ?>
                                <?php $i = 1; while ($row = $history->fetch_assoc()): ?>
                                    <?php 
                                        $is_ret = (strtolower($row['status']) === 'returned');
                                        $is_over = (!$is_ret && $row['due_date'] < $today_str);
                                        $display_title = !empty($row['book_title']) ? $row['book_title'] : (!empty($row['catalog_title']) ? $row['catalog_title'] : 'Book #'.$row['bookid']);
                                        $b_img = (!empty($row['book_img']) && file_exists($row['book_img'])) ? $row['book_img'] : 'img1.jpg';
                                        $u_img = (!empty($row['profile_img']) && file_exists($row['profile_img'])) ? $row['profile_img'] : 'uploads/profile_1.jpg';
                                        if (!file_exists($u_img)) $u_img = 'admin.png';
                                    ?>
                                    <tr>
                                        <td><span class="text-muted small"><?php echo $i++; ?></span></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?php echo htmlspecialchars($b_img); ?>" alt="Cover" class="rounded shadow-xs" style="width: 32px; height: 44px; object-fit: cover;">
                                                <div>
                                                    <div class="fw-bold text-main"><?php echo htmlspecialchars($display_title); ?></div>
                                                    <small class="text-muted font-monospace">Loan: #<?php echo $row['id']; ?> &bull; Book: #<?php echo htmlspecialchars($row['bookid']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?php echo htmlspecialchars($u_img); ?>" alt="User" class="rounded-circle border" style="width: 28px; height: 28px; object-fit: cover;">
                                                <div>
                                                    <div class="fw-semibold text-main small"><?php echo htmlspecialchars($row['username'] ?? 'Patron #'.$row['user_id']); ?></div>
                                                    <small class="text-muted" style="font-size: 0.72rem;"><?php echo htmlspecialchars($row['email'] ?? ''); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="small"><?php echo date('d M, Y', strtotime($row['issue_date'])); ?></span></td>
                                        <td>
                                            <span class="small fw-semibold <?php echo $is_over ? 'text-danger' : 'text-main'; ?>">
                                                <?php echo date('d M, Y', strtotime($row['due_date'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                                if (!empty($row['return_date']) && $row['return_date'] !== '0000-00-00') {
                                                    echo '<span class="small text-success fw-semibold">' . date('d M, Y', strtotime($row['return_date'])) . '</span>';
                                                } else {
                                                    echo '<span class="text-muted small">-</span>';
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($row['fine'] > 0): ?>
                                                <span class="text-danger fw-bold font-monospace">₹<?php echo number_format($row['fine'], 2); ?></span>
                                                <small class="badge bg-light text-muted border d-block mt-1" style="font-size: 0.65rem;"><?php echo htmlspecialchars($row['fine_status'] ?? 'paid'); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted small">₹0.00</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($is_ret): ?>
                                                <span class="badge-pill-success">
                                                    <i class="fa-solid fa-check me-1"></i> Returned
                                                </span>
                                            <?php elseif ($is_over): ?>
                                                <span class="badge-pill-danger">
                                                    <i class="fa-solid fa-clock me-1"></i> Overdue
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-pill-cyan">
                                                    <i class="fa-solid fa-book-open me-1"></i> Active
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a href="print_receipt.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-sm btn-secondary-custom py-1 px-2" title="Print Slip">
                                                    <i class="fa-solid fa-print"></i>
                                                </a>
                                                <?php if (!$is_ret): ?>
                                                    <a href="returnbook.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary-custom py-1 px-3 fw-bold" title="Return Book">
                                                        Return
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-box-open fs-1 text-primary d-block mb-3"></i>
                                        <h6 class="fw-bold">No Circulation History Found</h6>
                                        <p class="small mb-0">Try changing your search or filter options.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Server-Side Pagination Bar -->
                <?php if ($total_pages > 1): ?>
                    <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Page <strong><?php echo $page; ?></strong> of <strong><?php echo $total_pages; ?></strong> (<?php echo $total_rows; ?> Circulation Records)</small>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo build_history_page_url($page - 1); ?>"><i class="fa-solid fa-chevron-left"></i></a>
                                </li>
                                <?php for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++): ?>
                                    <li class="page-item <?php echo ($p == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo build_history_page_url($p); ?>"><?php echo $p; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo build_history_page_url($page + 1); ?>"><i class="fa-solid fa-chevron-right"></i></a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
