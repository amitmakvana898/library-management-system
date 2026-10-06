<?php
// user_history.php - Student's Personal Loan History (Cyber Emerald Edition)
require_once __DIR__ . '/includes/config.php';
require_user();

$user = get_logged_user($conn);
$user_id = $user['id'];

$history = $conn->query("
    SELECT bi.*, b.title as catalog_title, b.book_img, b.authorname, b.category 
    FROM book_issued bi 
    LEFT JOIN books b ON (bi.bookid = b.bookid OR bi.bookid = b.id) 
    WHERE bi.user_id = '$user_id' 
    ORDER BY bi.id DESC
");

$is_dashboard = true;
$page_title = "My Borrowing History";
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/includes/user_sidebar.php'; ?>

    <div class="dashboard-main">
        <div class="dashboard-topbar">
            <div>
                <h5 class="fw-bold mb-0 text-main font-space">My Lifetime Borrowing History</h5>
                <small class="text-muted">Personal audit log of all checked-out books, due dates, and returns</small>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <a href="user_dashboard.php" class="btn btn-secondary-custom px-3 py-2">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
                </a>
                <a href="books.php" class="btn btn-primary-custom px-3 py-2 fw-bold">
                    <i class="fa-solid fa-book-open me-1"></i> Explore Books
                </a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php display_flash(); ?>

            <div class="custom-table-card">
                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-main mb-0 font-space"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Reading Activity Ledger</h6>
                        <small class="text-muted">Complete record of your past and active loans</small>
                    </div>
                    <span class="badge-pill-cyan">
                        <i class="fa-solid fa-list-check"></i> <?php echo $history ? $history->num_rows : 0; ?> Records
                    </span>
                </div>
                
                <?php if ($history && $history->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="custom-table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Book Title</th>
                                    <th>Category</th>
                                    <th>Issue Date</th>
                                    <th>Due Date</th>
                                    <th>Return Date</th>
                                    <th>Late Fine</th>
                                    <th>Status</th>
                                    <th class="text-end">Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 1; while ($row = $history->fetch_assoc()): ?>
                                    <?php 
                                        $is_ret = ($row['status'] === 'returned');
                                        $display_title = !empty($row['book_title']) ? $row['book_title'] : (!empty($row['catalog_title']) ? $row['catalog_title'] : 'Book #'.$row['bookid']);
                                        $b_img = (!empty($row['book_img']) && file_exists($row['book_img'])) ? $row['book_img'] : 'img1.jpg';
                                    ?>
                                    <tr>
                                        <td><span class="text-muted small"><?php echo $i++; ?></span></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?php echo htmlspecialchars($b_img); ?>" alt="Cover" class="rounded shadow-xs flex-shrink-0" style="width: 32px; height: 44px; object-fit: cover;">
                                                <div>
                                                    <div class="fw-bold text-main"><?php echo htmlspecialchars($display_title); ?></div>
                                                    <small class="text-muted font-monospace">Loan: #<?php echo $row['id']; ?> &bull; Book: #<?php echo htmlspecialchars($row['bookid']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge-category"><?php echo htmlspecialchars($row['category'] ?? 'General'); ?></span></td>
                                        <td><span class="small"><?php echo date('d M, Y', strtotime($row['issue_date'])); ?></span></td>
                                        <td><span class="small fw-semibold text-primary"><?php echo date('d M, Y', strtotime($row['due_date'])); ?></span></td>
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
                                            <?php else: ?>
                                                <span class="badge-pill-warning">
                                                    <i class="fa-solid fa-book-open me-1"></i> Active Loan
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="print_receipt.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-sm btn-secondary-custom py-1 px-3 rounded-pill" title="Print Slip">
                                                <i class="fa-solid fa-print me-1"></i> Slip
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 px-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary mb-3" style="width: 70px; height: 70px;">
                            <i class="fa-solid fa-clock-rotate-left fs-2"></i>
                        </div>
                        <h5 class="fw-bold text-main mb-1">No Borrowing History Found</h5>
                        <p class="text-muted small mb-4" style="max-width: 480px; margin: 0 auto;">You have not checked out any books from the library yet. Start exploring our collections today.</p>
                        <a href="books.php" class="btn btn-primary-custom px-4 py-2 fw-bold">
                            <i class="fa-solid fa-book-open me-1"></i> Browse Library Catalog
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
