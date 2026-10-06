<?php
// issuebooks.php - Issue Book with Quota & Inventory Validation (Cyber Emerald Edition)
require_once __DIR__ . '/includes/config.php';
require_admin();

$site = get_site_settings($conn);
$loan_days = intval($site['loan_days'] ?? 14);

$default_issue_date = date('Y-m-d');
$default_due_date = date('Y-m-d', strtotime("+{$loan_days} days"));

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id'] ?? 0);
    $book_id = intval($_POST['book_id'] ?? 0);
    $issue_date = trim($_POST['issue_date'] ?? $default_issue_date);
    $due_date = trim($_POST['due_date'] ?? $default_due_date);

    // Date Validation
    if (strtotime($due_date) < strtotime($issue_date)) {
        $error = "Due Return Date cannot be before Issue Date.";
    } elseif ($user_id <= 0 || $book_id <= 0) {
        $error = "Please select both a registered member and an available book.";
    } else {
        // Quota Check: Max 3 active loans per member
        $chk_active = $conn->query("SELECT COUNT(*) as c FROM book_issued WHERE user_id = $user_id AND status = 'issued'");
        $active_count = $chk_active ? $chk_active->fetch_assoc()['c'] : 0;

        if ($active_count >= 3) {
            $error = "Borrowing Limit Exceeded: This student already has 3 active books on loan. Please return previous books first.";
        } else {
            // Fetch book details and check availability
            $b_stmt = $conn->prepare("SELECT * FROM books WHERE id = ? LIMIT 1");
            $b_stmt->bind_param("i", $book_id);
            $b_stmt->execute();
            $b_res = $b_stmt->get_result();

            if ($b_res && $b_res->num_rows > 0) {
                $book = $b_res->fetch_assoc();
                
                if (strtolower($book['status']) !== 'available' && ($book['available_copies'] ?? 0) <= 0) {
                    $error = "Selected book is currently out of stock / on loan.";
                } else {
                    $book_title = $book['title'];
                    $bookid_val = $book['bookid'] ?? $book['id'];

                    // Insert into book_issued
                    $ins = $conn->prepare("INSERT INTO book_issued (user_id, bookid, issue_date, due_date, book_title, status, fine) VALUES (?, ?, ?, ?, ?, 'issued', 0)");
                    $ins->bind_param("iisss", $user_id, $bookid_val, $issue_date, $due_date, $book_title);

                    if ($ins->execute()) {
                        // Multi-copies management: Decrement available copies
                        $avail_copies = max(0, intval($book['available_copies'] ?? 1) - 1);
                        $new_status = ($avail_copies <= 0) ? 'Issued' : 'Available';

                        $conn->query("UPDATE books SET available_copies = $avail_copies, status = '$new_status' WHERE id = $book_id");

                        // If user had a pending request for this book, approve it
                        $conn->query("UPDATE book_requests SET status = 'approved' WHERE user_id = $user_id AND book_id = $book_id AND status = 'pending'");

                        // Add in-app notification to student
                        $notif_title = "Book Issued: " . $book_title;
                        $notif_msg = "Book '{$book_title}' has been issued to you. Due date for return is " . date('d M, Y', strtotime($due_date)) . ".";
                        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'info')");
                        $notif_stmt->bind_param("iss", $user_id, $notif_title, $notif_msg);
                        $notif_stmt->execute();

                        set_flash('success', 'Book "' . htmlspecialchars($book_title) . '" successfully issued! Remaining copies: ' . $avail_copies);
                        header("Location: issuedhistory.php");
                        exit();
                    } else {
                        $error = "Failed to record issue: " . $conn->error;
                    }
                }
            } else {
                $error = "Selected book does not exist.";
            }
        }
    }
}

// Fetch all active users with active loan counts
$users = $conn->query("
    SELECT u.id, u.username, u.email, u.mobileno,
    (SELECT COUNT(*) FROM book_issued WHERE user_id = u.id AND status = 'issued') as active_count 
    FROM users u 
    WHERE u.status = 'active'
    ORDER BY u.username ASC
");

// Fetch available books
$available_books = $conn->query("SELECT id, bookid, title, authorname, category, available_copies FROM books WHERE status = 'Available' OR available_copies > 0 ORDER BY title ASC");

$is_dashboard = true;
$page_title = "Issue Book Desk";
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
                    <h5 class="fw-bold mb-0 text-main font-space">Circulation Desk: Issue Book</h5>
                    <small class="text-muted">Quota validation & real-time inventory management</small>
                </div>
            </div>
            
            <a href="scanner.php" class="btn btn-sm btn-amber-custom px-3 py-2 rounded-3">
                <i class="fa-solid fa-qrcode me-1"></i> Open QR Scanner
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

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="custom-card p-4 shadow-lg">
                        <form method="POST" action="issuebooks.php">
                            <div class="mb-4">
                                <label class="form-label fw-semibold small text-muted">Select Member / Borrower *</label>
                                <select name="user_id" class="form-select" required>
                                    <option value="">-- Choose Member --</option>
                                    <?php if ($users && $users->num_rows > 0): ?>
                                        <?php while ($u = $users->fetch_assoc()): ?>
                                            <?php $is_maxed = ($u['active_count'] >= 3); ?>
                                            <option value="<?php echo $u['id']; ?>" <?php echo $is_maxed ? 'disabled' : ''; ?> <?php echo (isset($_GET['user_id']) && $_GET['user_id'] == $u['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($u['username']); ?> &bull; (Active Loans: <?php echo $u['active_count']; ?>/3) <?php echo $is_maxed ? ' [LIMIT REACHED]' : ''; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="text-muted d-block mt-1">Policy: Maximum 3 active books allowed per student.</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold small text-muted">Select Available Book Title *</label>
                                <select name="book_id" class="form-select" required>
                                    <option value="">-- Choose Book from Available Catalog --</option>
                                    <?php if ($available_books && $available_books->num_rows > 0): ?>
                                        <?php while ($b = $available_books->fetch_assoc()): ?>
                                            <?php 
                                                $match_sel = (
                                                    (isset($_GET['book_id']) && ($_GET['book_id'] == $b['id'] || $_GET['book_id'] == $b['bookid']))
                                                );
                                            ?>
                                            <option value="<?php echo $b['id']; ?>" <?php echo $match_sel ? 'selected' : ''; ?>>
                                                #<?php echo htmlspecialchars($b['bookid'] ?? $b['id']); ?> - <?php echo htmlspecialchars($b['title']); ?> (Available: <?php echo $b['available_copies'] ?? 1; ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Checkout / Issue Date *</label>
                                    <input type="date" name="issue_date" class="form-control" value="<?php echo $default_issue_date; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Expected Due Date (Standard: <?php echo $loan_days; ?> Days) *</label>
                                    <input type="date" name="due_date" class="form-control" value="<?php echo $default_due_date; ?>" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                <a href="admin_dashboard.php" class="btn btn-secondary-custom px-4 py-2">Cancel</a>
                                <button type="submit" class="btn btn-primary-custom px-4 py-2 fw-bold">
                                    <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Authorize & Issue Book
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
