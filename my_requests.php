<?php
// my_requests.php - Student's Book Requests Tracker (Cyber Emerald Edition)
require_once __DIR__ . '/includes/config.php';
require_user();

$user = get_logged_user($conn);
$user_id = $user['id'];

// Handle Cancel Request
if (isset($_GET['cancel'])) {
    $req_id = intval($_GET['cancel']);
    $del = $conn->prepare("DELETE FROM book_requests WHERE id = ? AND user_id = ? AND status = 'pending'");
    $del->bind_param("ii", $req_id, $user_id);
    if ($del->execute() && $del->affected_rows > 0) {
        set_flash('success', 'Book reservation request cancelled successfully.');
    } else {
        set_flash('danger', 'Unable to cancel request.');
    }
    header("Location: my_requests.php");
    exit();
}

$requests = $conn->query("
    SELECT br.*, b.title as book_title, b.authorname, b.category, b.book_img 
    FROM book_requests br 
    LEFT JOIN books b ON br.book_id = b.id 
    WHERE br.user_id = '$user_id' 
    ORDER BY br.id DESC
");

$is_dashboard = true;
$page_title = "My Book Requests";
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/includes/user_sidebar.php'; ?>

    <div class="dashboard-main">
        <div class="dashboard-topbar">
            <div>
                <h5 class="fw-bold mb-0 text-main font-space">My Book Reservations & Requests</h5>
                <small class="text-muted">Track librarian approval status for books you have requested to borrow</small>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <a href="user_dashboard.php" class="btn btn-secondary-custom px-3 py-2">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
                </a>
                <a href="books.php" class="btn btn-primary-custom px-3 py-2 fw-bold">
                    <i class="fa-solid fa-plus me-1"></i> Request New Book
                </a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php display_flash(); ?>

            <div class="custom-table-card">
                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-main mb-0 font-space"><i class="fa-solid fa-bookmark text-primary me-2"></i> Reservation Requests Status</h6>
                        <small class="text-muted">Live queue updates</small>
                    </div>
                    <span class="badge-pill-cyan">
                        <i class="fa-solid fa-layer-group"></i> <?php echo $requests ? $requests->num_rows : 0; ?> Requests
                    </span>
                </div>
                
                <?php if ($requests && $requests->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="custom-table mb-0">
                            <thead>
                                <tr>
                                    <th>Cover</th>
                                    <th>Book Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Requested On</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($r = $requests->fetch_assoc()): ?>
                                    <?php 
                                        $b_img = (!empty($r['book_img']) && file_exists($r['book_img'])) ? $r['book_img'] : 'img1.jpg';
                                    ?>
                                    <tr>
                                        <td style="width: 50px;">
                                            <img src="<?php echo htmlspecialchars($b_img); ?>" alt="Cover" class="rounded shadow-xs" style="width: 32px; height: 44px; object-fit: cover;">
                                        </td>
                                        <td>
                                            <div class="fw-bold text-main"><?php echo htmlspecialchars($r['book_title'] ?? 'Title Unavailable'); ?></div>
                                        </td>
                                        <td><span class="text-muted small"><?php echo htmlspecialchars($r['authorname'] ?? '-'); ?></span></td>
                                        <td><span class="badge-category"><?php echo htmlspecialchars($r['category'] ?? 'General'); ?></span></td>
                                        <td><span class="small"><?php echo date('d M, Y h:i A', strtotime($r['request_date'])); ?></span></td>
                                        <td>
                                            <?php if ($r['status'] === 'pending'): ?>
                                                <span class="badge-pill-warning">
                                                    <i class="fa-solid fa-hourglass-half me-1"></i> Under Review
                                                </span>
                                            <?php elseif ($r['status'] === 'approved'): ?>
                                                <span class="badge-pill-success">
                                                    <i class="fa-solid fa-circle-check me-1"></i> Approved & Issued
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-pill-danger">
                                                    <i class="fa-solid fa-circle-xmark me-1"></i> Rejected
                                                </span>
                                                <?php if (!empty($r['admin_remarks'])): ?>
                                                    <small class="d-block text-muted mt-1"><?php echo htmlspecialchars($r['admin_remarks']); ?></small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($r['status'] === 'pending'): ?>
                                                <a href="my_requests.php?cancel=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-danger py-1 px-3 rounded-pill" onclick="return confirm('Cancel this book request?');">
                                                    <i class="fa-solid fa-xmark me-1"></i> Cancel
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small fw-semibold">Processed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 px-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary mb-3" style="width: 70px; height: 70px;">
                            <i class="fa-solid fa-bookmark fs-2"></i>
                        </div>
                        <h5 class="fw-bold text-main mb-1">No Active Book Requests</h5>
                        <p class="text-muted small mb-4" style="max-width: 480px; margin: 0 auto;">You have not submitted any book reservation requests yet. Browse the library catalog to request your next read.</p>
                        <a href="books.php" class="btn btn-primary-custom px-4 py-2 fw-bold">
                            <i class="fa-solid fa-book-open me-1"></i> Explore Books Catalog
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
