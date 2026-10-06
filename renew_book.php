<?php
// renew_book.php - 1-Click Online Book Loan Extension (+7 Days)
require_once __DIR__ . '/includes/config.php';
require_user();

$user = get_logged_user($conn);
$user_id = $user['id'];
$loan_id = intval($_GET['id'] ?? 0);
$today_str = date('Y-m-d');

if ($loan_id > 0) {
    // Check if loan belongs to this user and is active
    $stmt = $conn->prepare("SELECT * FROM book_issued WHERE id = ? AND user_id = ? AND status = 'issued' LIMIT 1");
    $stmt->bind_param("ii", $loan_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $loan = $res->fetch_assoc();
        $curr_due = $loan['due_date'];

        // Overdue check
        if ($curr_due < $today_str) {
            set_flash('danger', 'Cannot renew overdue book online. Please visit the library circulation desk to return and settle pending late fine.');
        } elseif (intval($loan['renewal_count'] ?? 0) >= 2) {
            set_flash('warning', 'Maximum online renewal limit (2 times) reached for this book. Please visit the library desk.');
        } else {
            // Extend by 7 days
            $new_due = date('Y-m-d', strtotime($curr_due . ' + 7 days'));

            $up = $conn->prepare("UPDATE book_issued SET due_date = ?, renewal_count = renewal_count + 1 WHERE id = ?");
            $up->bind_param("si", $new_due, $loan_id);

            if ($up->execute()) {
                // Record notification
                $notif_title = "Loan Extended: " . $loan['book_title'];
                $notif_msg = "Your book loan for '{$loan['book_title']}' has been extended by +7 days. New due date is " . date('d M, Y', strtotime($new_due)) . ".";
                $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'success')");
                $notif_stmt->bind_param("iss", $user_id, $notif_title, $notif_msg);
                $notif_stmt->execute();

                set_flash('success', 'Book loan extended successfully! New return due date is ' . date('d M, Y', strtotime($new_due)));
            }
        }
    } else {
        set_flash('danger', 'Active loan record not found.');
    }
}

$ref = $_SERVER['HTTP_REFERER'] ?? 'my_issued_books.php';
header("Location: " . $ref);
exit();
?>
