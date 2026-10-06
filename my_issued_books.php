<?php
// my_issued_books.php - Student's Currently Borrowed Books with 1-Click Renew (Cyber Emerald Edition)
require_once __DIR__ . '/includes/config.php';
require_user();

$user = get_logged_user($conn);
$user_id = $user['id'];
$today_str = date('Y-m-d');

$issued_books = $conn->query("
    SELECT bi.*, b.title as catalog_title, b.book_img, b.authorname, b.category, b.rack_no 
    FROM book_issued bi 
    LEFT JOIN books b ON (bi.bookid = b.bookid OR bi.bookid = b.id) 
    WHERE bi.user_id = '$user_id' AND bi.status = 'issued' 
    ORDER BY bi.due_date ASC
");

$is_dashboard = true;
$page_title = "My Borrowed Books";
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/includes/user_sidebar.php'; ?>

    <div class="dashboard-main">
        <div class="dashboard-topbar">
            <div>
                <h5 class="fw-bold mb-0 text-main font-space">My Active Borrowed Books</h5>
                <small class="text-muted">Track return deadlines, print circulation slips, and request +7 day renewal</small>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <a href="user_dashboard.php" class="btn btn-secondary-custom px-3 py-2">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
                </a>
                <a href="books.php" class="btn btn-primary-custom px-3 py-2 fw-bold">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Browse More Books
                </a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php display_flash(); ?>

            <div class="row g-4">
                <?php if ($issued_books && $issued_books->num_rows > 0): ?>
                    <?php while ($b = $issued_books->fetch_assoc()): ?>
                        <?php 
                            $display_title = !empty($b['book_title']) ? $b['book_title'] : (!empty($b['catalog_title']) ? $b['catalog_title'] : 'Book #'.$b['bookid']);
                            $b_img = (!empty($b['book_img']) && file_exists($b['book_img'])) ? $b['book_img'] : 'img1.jpg';
                            $is_over = ($b['due_date'] < $today_str);
                            
                            $due = new DateTime($b['due_date']);
                            $now = new DateTime($today_str);
                            $diff = $now->diff($due);
                            $days_left = $due >= $now ? $diff->days : -$diff->days;
                        ?>
                        <div class="col-lg-6">
                            <div class="custom-card p-4 h-100 shadow-md <?php echo $is_over ? 'border-danger' : ''; ?>">
                                <div class="d-flex gap-3 align-items-start">
                                    <img src="<?php echo htmlspecialchars($b_img); ?>" alt="Cover" class="rounded-3 shadow-sm flex-shrink-0" style="width: 95px; height: 135px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <span class="badge-category mb-2 d-inline-block"><?php echo htmlspecialchars($b['category'] ?? 'General'); ?></span>
                                        <h5 class="fw-bold text-main mb-1"><?php echo htmlspecialchars($display_title); ?></h5>
                                        <p class="text-muted small mb-2">By <?php echo htmlspecialchars($b['authorname'] ?? 'Library Catalog'); ?></p>
                                        
                                        <div class="p-3 rounded-3 mb-3 small" style="background: var(--light); border: 1px solid var(--border-color);">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted">Issued Date:</span>
                                                <span class="fw-semibold text-main"><?php echo date('d M, Y', strtotime($b['issue_date'])); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted">Return Due Date:</span>
                                                <span class="fw-bold <?php echo $is_over ? 'text-danger' : 'text-primary'; ?>"><?php echo date('d M, Y', strtotime($b['due_date'])); ?></span>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top" style="border-color: var(--border-color) !important;">
                                            <?php if ($days_left < 0): ?>
                                                <span class="badge-pill-danger">
                                                    <i class="fa-solid fa-triangle-exclamation"></i> Overdue (<?php echo abs($days_left); ?> Days)
                                                </span>
                                            <?php elseif ($days_left <= 2): ?>
                                                <span class="badge-pill-warning">
                                                    <i class="fa-solid fa-clock"></i> Due in <?php echo $days_left; ?> Days
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-pill-success">
                                                    <i class="fa-solid fa-calendar-check"></i> <?php echo $days_left; ?> Days Left
                                                </span>
                                            <?php endif; ?>

                                            <div class="d-flex gap-2 align-items-center">
                                                <a href="print_receipt.php?id=<?php echo $b['id']; ?>" target="_blank" class="btn btn-sm btn-secondary-custom py-1 px-3 rounded-pill" title="Print Circulation Slip">
                                                    <i class="fa-solid fa-print me-1"></i> Slip
                                                </a>
                                                <?php if (!$is_over): ?>
                                                    <a href="renew_book.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-primary-custom py-1 px-3 fw-bold rounded-pill" onclick="return confirm('Extend borrowing duration by +7 days?');">
                                                        <i class="fa-solid fa-arrows-rotate me-1"></i> Renew +7D
                                                    </a>
                                                <?php else: ?>
                                                    <a href="renew_book.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-amber-custom py-1 px-3 fw-bold rounded-pill" onclick="return confirm('Request renewal for overdue loan (+7 days)?');" title="Renew Overdue Book">
                                                        <i class="fa-solid fa-arrows-rotate me-1"></i> Renew
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="fa-solid fa-book-open fs-1 text-primary d-block mb-3"></i>
                        <h4 class="fw-bold text-main">No Active Book Loans</h4>
                        <p class="text-muted">You currently do not have any borrowed books from the library.</p>
                        <a href="books.php" class="btn btn-primary-custom px-4 py-2 mt-2">Explore Library Catalog</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php 
require_once __DIR__ . '/includes/fine_payment_modal.php';
require_once __DIR__ . '/includes/footer.php'; 
?>
