<?php
// returnbook.php - Return Book with Multi-Copy Restock & Fine Calculation (Cyber Emerald Edition)
require_once __DIR__ . '/includes/config.php';
require_admin();

$site = get_site_settings($conn);
$fine_rate = floatval($site['fine_per_day'] ?? 5.0);

$selected_issue_id = intval($_GET['id'] ?? 0);
$selected_issue = null;

if ($selected_issue_id > 0) {
    $stmt = $conn->prepare("
        SELECT bi.*, u.username, u.email, u.mobileno, b.id as book_table_id, b.available_copies, b.quantity, b.book_img 
        FROM book_issued bi 
        LEFT JOIN users u ON bi.user_id = u.id 
        LEFT JOIN books b ON (bi.bookid = b.bookid OR bi.bookid = b.id) 
        WHERE bi.id = ? AND bi.status = 'issued' 
        LIMIT 1
    ");
    $stmt->bind_param("i", $selected_issue_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $selected_issue = $res->fetch_assoc();
    }
}

$error = "";

// Process Return Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $issue_id = intval($_POST['issue_id'] ?? 0);
    $return_date = trim($_POST['return_date'] ?? date('Y-m-d'));
    $fine_amount = max(0.0, floatval($_POST['fine_amount'] ?? 0.0)); // Never negative
    $fine_status = trim($_POST['fine_status'] ?? 'paid');
    $remarks = trim($_POST['remarks'] ?? '');

    if ($issue_id <= 0) {
        $error = "Please select a valid active book loan.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM book_issued WHERE id = ? AND status = 'issued' LIMIT 1");
        $stmt->bind_param("i", $issue_id);
        $stmt->execute();
        $issue_res = $stmt->get_result();

        if ($issue_res && $issue_res->num_rows > 0) {
            $issue = $issue_res->fetch_assoc();
            $bookid = $issue['bookid'];
            $user_id = $issue['user_id'];

            // Update book_issued record
            $up = $conn->prepare("UPDATE book_issued SET return_date = ?, fine = ?, status = 'returned', fine_status = ?, remarks = ? WHERE id = ?");
            $up->bind_param("sdssi", $return_date, $fine_amount, $fine_status, $remarks, $issue_id);

            if ($up->execute()) {
                // Restock inventory: increment available_copies and set status='Available'
                $conn->query("UPDATE books SET available_copies = available_copies + 1, status = 'Available' WHERE bookid = '$bookid' OR id = '$bookid'");

                // Notify waitlisted users that the book is back in stock
                $b_find = $conn->query("SELECT id FROM books WHERE bookid = '$bookid' OR id = '$bookid' LIMIT 1");
                if ($b_find && $b_find->num_rows > 0) {
                    $real_bid = $b_find->fetch_assoc()['id'];
                    $w_res = $conn->query("SELECT user_id FROM book_waitlist WHERE book_id = $real_bid AND notified = 0");
                    if ($w_res && $w_res->num_rows > 0) {
                        while ($w = $w_res->fetch_assoc()) {
                            $w_uid = $w['user_id'];
                            $w_title = "Book Restocked Alert: " . $issue['book_title'];
                            $w_msg = "Great news! '" . $issue['book_title'] . "' has just been returned and is now available in the library catalog.";
                            $n_ins = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'info')");
                            $n_ins->bind_param("iss", $w_uid, $w_title, $w_msg);
                            $n_ins->execute();
                        }
                        $conn->query("UPDATE book_waitlist SET notified = 1 WHERE book_id = $real_bid");
                    }
                }

                // Add in-app notification to student
                $notif_title = "Book Return Confirmed: " . $issue['book_title'];
                $notif_msg = "Your returned book '{$issue['book_title']}' has been checked in and restocked. Late fine: ₹" . number_format($fine_amount, 2);
                $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'success')");
                $notif_stmt->bind_param("iss", $user_id, $notif_title, $notif_msg);
                $notif_stmt->execute();

                set_flash('success', 'Book "' . htmlspecialchars($issue['book_title']) . '" returned and restocked! Fine settled: ₹' . number_format($fine_amount, 2));
                header("Location: print_receipt.php?id=" . $issue_id);
                exit();
            } else {
                $error = "Failed to process return: " . $conn->error;
            }
        } else {
            $error = "Active loan record not found or already returned.";
        }
    }
}

// Fetch all active loans
$active_loans = $conn->query("
    SELECT bi.*, u.username 
    FROM book_issued bi 
    LEFT JOIN users u ON bi.user_id = u.id 
    WHERE bi.status = 'issued' 
    ORDER BY bi.due_date ASC
");

$is_dashboard = true;
$page_title = "Return Book Desk";
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
                    <h5 class="fw-bold mb-0 text-main font-space">Circulation Desk: Process Return</h5>
                    <small class="text-muted">Multi-copy inventory restock and late fine penalty calculation</small>
                </div>
            </div>
            
            <a href="scanner.php" class="btn btn-sm btn-amber-custom px-3 py-2 rounded-3">
                <i class="fa-solid fa-qrcode me-1"></i> QR Scanner
            </a>
        </div>

        <div class="dashboard-content">
            <?php display_flash(); ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4 justify-content-center">
                <div class="col-lg-8">
                    <div class="custom-card p-4 shadow-lg">
                        <form method="POST" action="returnbook.php">
                            <div class="mb-4">
                                <label class="form-label fw-semibold small text-muted">Select Active Loan Record to Check-In *</label>
                                <select name="issue_id" class="form-select" onchange="if(this.value) window.location.href='returnbook.php?id=' + this.value;" required>
                                    <option value="">-- Select Active Borrowed Book --</option>
                                    <?php if ($active_loans && $active_loans->num_rows > 0): ?>
                                        <?php while ($al = $active_loans->fetch_assoc()): ?>
                                            <option value="<?php echo $al['id']; ?>" <?php echo ($selected_issue && $selected_issue['id'] == $al['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($al['book_title']); ?> &bull; Borrower: <?php echo htmlspecialchars($al['username'] ?? 'Patron #'.$al['user_id']); ?> (Due: <?php echo date('d M, Y', strtotime($al['due_date'])); ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <?php if ($selected_issue): ?>
                                <?php
                                    $today = date('Y-m-d');
                                    $due = $selected_issue['due_date'];
                                    $calc_fine = max(0.0, calculate_fine($due, $today, $fine_rate));
                                    $is_overdue = ($today > $due);
                                ?>
                                
                                <div class="p-4 rounded-4 border mb-4" style="background: var(--light);">
                                    <h6 class="fw-bold text-main border-bottom pb-2 mb-3"><i class="fa-solid fa-circle-info text-primary me-2"></i> Active Loan Assessment Details</h6>
                                    <div class="row g-3 small">
                                        <div class="col-sm-6">
                                            <span class="text-muted d-block">Book Title:</span>
                                            <strong class="text-main fs-6"><?php echo htmlspecialchars($selected_issue['book_title']); ?></strong>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted d-block">Borrower:</span>
                                            <strong class="text-main"><?php echo htmlspecialchars($selected_issue['username'] ?? 'Member #'.$selected_issue['user_id']); ?></strong>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted d-block">Issue Date:</span>
                                            <span class="text-main"><?php echo date('d M, Y', strtotime($selected_issue['issue_date'])); ?></span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted d-block">Due Return Date:</span>
                                            <strong class="<?php echo $is_overdue ? 'text-danger' : 'text-success'; ?>">
                                                <?php echo date('d M, Y', strtotime($due)); ?>
                                                <?php if ($is_overdue): ?>
                                                    <span class="badge bg-danger rounded-pill ms-1">OVERDUE</span>
                                                <?php endif; ?>
                                            </strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small text-muted">Actual Return Date *</label>
                                        <input type="date" name="return_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small text-muted">Calculated Late Fine (₹<?php echo $fine_rate; ?>/day)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" step="0.01" min="0" name="fine_amount" class="form-control" value="<?php echo number_format($calc_fine, 2, '.', ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold small text-muted">Fine Settlement Status</label>
                                    <select name="fine_status" class="form-select">
                                        <option value="paid" <?php echo $calc_fine == 0 ? 'selected' : ''; ?>>Paid / Cleared</option>
                                        <option value="unpaid" <?php echo $calc_fine > 0 ? 'selected' : ''; ?>>Unpaid (Collect Later)</option>
                                        <option value="waived">Waived / Exempted</option>
                                    </select>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold small text-muted">Librarian Check-In Remarks (Optional)</label>
                                    <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Good condition, returned on time."></textarea>
                                </div>

                                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                    <a href="admin_dashboard.php" class="btn btn-secondary-custom px-4 py-2">Cancel</a>
                                    <button type="submit" class="btn btn-primary-custom px-4 py-2 fw-bold">
                                        <i class="fa-solid fa-circle-check me-1"></i> Confirm Return & Print Receipt
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="p-4 bg-light rounded-3 text-center text-muted">
                                    <i class="fa-solid fa-arrow-pointer fs-2 text-primary d-block mb-2"></i>
                                    Please select an active borrowed book from the dropdown above to calculate fine and process check-in.
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
