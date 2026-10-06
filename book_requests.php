<?php
// book_requests.php - Manage Student Book Borrow Requests (Cyber Emerald Edition)
require_once __DIR__ . '/includes/config.php';
require_admin();

$site = get_site_settings($conn);
$loan_days = intval($site['loan_days'] ?? 14);

// Handle Approve Request
if (isset($_GET['approve'])) {
    $req_id = intval($_GET['approve']);
    $r_stmt = $conn->prepare("SELECT br.*, b.title, b.bookid, b.status as b_status FROM book_requests br JOIN books b ON br.book_id = b.id WHERE br.id = ? AND br.status = 'pending' LIMIT 1");
    $r_stmt->bind_param("i", $req_id);
    $r_stmt->execute();
    $res = $r_stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $req = $res->fetch_assoc();
        
        // Check max 3 active borrow limit for student
        $curr_borrow_cnt = $conn->query("SELECT COUNT(*) as c FROM book_issued WHERE user_id = {$req['user_id']} AND status = 'issued'")->fetch_assoc()['c'] ?? 0;
        if ($curr_borrow_cnt >= 3) {
            set_flash('danger', 'Cannot approve: Student has already reached the maximum borrow limit of 3 books.');
        } elseif (strtolower($req['b_status']) === 'issued') {
            set_flash('danger', 'Cannot approve: Book "' . htmlspecialchars($req['title']) . '" is currently out of stock.');
        } else {
            $user_id = $req['user_id'];
            $bookid = $req['bookid'];
            $title = $req['title'];
            $issue_date = date('Y-m-d');
            $due_date = date('Y-m-d', strtotime("+{$loan_days} days"));

            // Insert into book_issued
            $ins = $conn->prepare("INSERT INTO book_issued (user_id, bookid, issue_date, due_date, book_title, status, fine) VALUES (?, ?, ?, ?, ?, 'issued', 0)");
            $ins->bind_param("iisss", $user_id, $bookid, $issue_date, $due_date, $title);
            $ins->execute();

            // Decrement copies and update book status if empty
            $b_id = $req['book_id'];
            $conn->query("UPDATE books SET available_copies = GREATEST(0, available_copies - 1) WHERE id = $b_id");
            $rem_copies = $conn->query("SELECT available_copies FROM books WHERE id = $b_id")->fetch_assoc()['available_copies'] ?? 0;
            if ($rem_copies <= 0) {
                $conn->query("UPDATE books SET status = 'Issued' WHERE id = $b_id");
            }

            // Update request status
            $conn->query("UPDATE book_requests SET status = 'approved' WHERE id = {$req_id}");

            // Send notification to student
            $notif_title = "Book Request Approved";
            $notif_msg = "Your request for \"" . addslashes($title) . "\" has been approved! Due Date: " . date('d M, Y', strtotime($due_date));
            $conn->query("INSERT INTO notifications (user_id, title, message, type) VALUES ($user_id, '$notif_title', '$notif_msg', 'success')");

            set_flash('success', 'Request approved! Book "' . htmlspecialchars($title) . '" has been issued to student.');
        }
    } else {
        set_flash('danger', 'Request record not found or already processed.');
    }
    header("Location: book_requests.php");
    exit();
}

// Handle Reject Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject_request') {
    $req_id = intval($_POST['req_id'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? 'Request rejected by librarian');

    if ($req_id > 0) {
        $up = $conn->prepare("UPDATE book_requests SET status = 'rejected', admin_remarks = ? WHERE id = ?");
        $up->bind_param("si", $remarks, $req_id);
        if ($up->execute()) {
            set_flash('success', 'Book request rejected.');
        }
    }
    header("Location: book_requests.php");
    exit();
}

// Fetch all requests
$requests = $conn->query("
    SELECT br.*, u.username, u.email, u.mobileno, u.profile_img, b.title as book_title, b.authorname, b.book_img, b.status as book_status 
    FROM book_requests br 
    LEFT JOIN users u ON br.user_id = u.id 
    LEFT JOIN books b ON br.book_id = b.id 
    ORDER BY br.id DESC
");

$is_dashboard = true;
$page_title = "Book Borrow Requests";
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/includes/admin_sidebar.php'; ?>

    <div class="dashboard-main">
        <div class="dashboard-topbar">
            <div>
                <h5 class="fw-bold mb-0 text-main font-space">Member Borrow Requests</h5>
                <small class="text-muted">Review, approve, and process book reservations requested by students</small>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <a href="admin_dashboard.php" class="btn btn-secondary-custom px-3 py-2">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
                </a>
                <a href="issuebooks.php" class="btn btn-primary-custom px-3 py-2 fw-bold">
                    <i class="fa-solid fa-plus me-1"></i> Direct Issue Desk
                </a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php display_flash(); ?>

            <div class="custom-table-card">
                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-main mb-0 font-space"><i class="fa-solid fa-envelope-open-text text-primary me-2"></i> Book Reservation Queue</h6>
                        <small class="text-muted">Review student reservations in real-time</small>
                    </div>
                    <span class="badge bg-primary bg-opacity-20 text-primary border border-primary border-opacity-30 rounded-pill px-3 py-2 fw-bold">
                        <?php echo $requests ? $requests->num_rows : 0; ?> Requests
                    </span>
                </div>
                
                <div class="table-responsive">
                    <table class="custom-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Requested Book</th>
                                <th>Student / Member</th>
                                <th>Date Requested</th>
                                <th>Current Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($requests && $requests->num_rows > 0): ?>
                                <?php $i = 1; while ($r = $requests->fetch_assoc()): ?>
                                    <?php 
                                        $b_img = (!empty($r['book_img']) && file_exists($r['book_img'])) ? $r['book_img'] : 'img1.jpg';
                                        $u_img = (!empty($r['profile_img']) && file_exists($r['profile_img'])) ? $r['profile_img'] : 'uploads/profile_1.jpg';
                                        if (!file_exists($u_img)) $u_img = 'admin.png';
                                    ?>
                                    <tr>
                                        <td><span class="text-muted small"><?php echo $i++; ?></span></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?php echo htmlspecialchars($b_img); ?>" alt="Cover" class="rounded shadow-xs" style="width: 32px; height: 44px; object-fit: cover;">
                                                <div>
                                                    <div class="fw-bold text-main"><?php echo htmlspecialchars($r['book_title'] ?? 'Book #'.$r['book_id']); ?></div>
                                                    <small class="text-muted">Author: <?php echo htmlspecialchars($r['authorname'] ?? '-'); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?php echo htmlspecialchars($u_img); ?>" alt="User" class="rounded-circle border" style="width: 28px; height: 28px; object-fit: cover;">
                                                <div>
                                                    <div class="fw-semibold text-main small"><?php echo htmlspecialchars($r['username'] ?? 'Patron #'.$r['user_id']); ?></div>
                                                    <small class="text-muted" style="font-size: 0.72rem;"><?php echo htmlspecialchars($r['email'] ?? ''); ?> &bull; <?php echo htmlspecialchars($r['mobileno'] ?? ''); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="small"><?php echo date('d M, Y h:i A', strtotime($r['request_date'])); ?></span></td>
                                        <td>
                                            <?php if ($r['status'] === 'pending'): ?>
                                                <span class="badge-pill-warning">
                                                    <i class="fa-solid fa-hourglass-half me-1"></i> Pending
                                                </span>
                                            <?php elseif ($r['status'] === 'approved'): ?>
                                                <span class="badge-pill-success">
                                                    <i class="fa-solid fa-check me-1"></i> Approved & Issued
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-pill-danger">
                                                    <i class="fa-solid fa-xmark me-1"></i> Rejected
                                                </span>
                                                <?php if (!empty($r['admin_remarks'])): ?>
                                                    <small class="d-block text-muted mt-1"><?php echo htmlspecialchars($r['admin_remarks']); ?></small>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($r['status'] === 'pending'): ?>
                                                <div class="d-inline-flex gap-1">
                                                    <a href="book_requests.php?approve=<?php echo $r['id']; ?>" class="btn btn-sm btn-success py-1 px-3 rounded-2 fw-bold" onclick="return confirm('Approve this request and issue book to student?');">
                                                        <i class="fa-solid fa-check me-1"></i> Approve
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 rounded-2" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $r['id']; ?>">
                                                        <i class="fa-solid fa-xmark"></i> Reject
                                                    </button>
                                                </div>

                                                <!-- Reject Remarks Modal -->
                                                <div class="modal fade" id="rejectModal<?php echo $r['id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content border-0 shadow-xl rounded-4">
                                                            <form method="POST" action="book_requests.php">
                                                                <input type="hidden" name="action" value="reject_request">
                                                                <input type="hidden" name="req_id" value="<?php echo $r['id']; ?>">
                                                                <div class="modal-header border-bottom px-4 py-3">
                                                                    <h5 class="modal-title fw-bold text-main">Reject Book Request</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body p-4 text-start">
                                                                    <p class="small text-muted mb-3">Provide an optional reason why the request for "<strong><?php echo htmlspecialchars($r['book_title'] ?? 'Book'); ?></strong>" is being rejected:</p>
                                                                    <textarea name="remarks" class="form-control" rows="3" placeholder="e.g. Currently undergoing physical maintenance or reserved for reference room."></textarea>
                                                                </div>
                                                                <div class="modal-footer border-top px-4 py-3">
                                                                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-danger">Confirm Reject</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small fw-semibold">Completed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-envelope-circle-check fs-1 text-primary d-block mb-3"></i>
                                        <h6 class="fw-bold">No Borrow Requests in Queue</h6>
                                        <p class="small mb-0">When student members request books online, they will appear here for 1-click approval.</p>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
