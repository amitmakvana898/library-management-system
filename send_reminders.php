<?php
// send_reminders.php - Automated Due Date Notification Sender
require_once __DIR__ . '/includes/config.php';
require_admin();

$today_str = date('Y-m-d');
$two_days_later = date('Y-m-d', strtotime('+2 days'));

// Find approaching and overdue loans
$loans = $conn->query("
    SELECT bi.*, u.username, u.email 
    FROM book_issued bi 
    LEFT JOIN users u ON bi.user_id = u.id 
    WHERE bi.status = 'issued' AND bi.due_date <= '$two_days_later'
");

$sent_count = 0;
if ($loans && $loans->num_rows > 0) {
    while ($row = $loans->fetch_assoc()) {
        $uid = $row['user_id'];
        $title = "Book Return Reminder: " . $row['book_title'];
        $is_overdue = ($row['due_date'] < $today_str);
        
        if ($is_overdue) {
            $msg = "Your borrowed book '{$row['book_title']}' is overdue since {$row['due_date']}. Please return it immediately to avoid increasing late fines.";
            $type = "danger";
        } else {
            $msg = "Friendly reminder: Your borrowed book '{$row['book_title']}' is due for return on {$row['due_date']}.";
            $type = "warning";
        }

        $ins = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $ins->bind_param("isss", $uid, $title, $msg, $type);
        if ($ins->execute()) {
            $sent_count++;
        }
    }
}

set_flash('success', "Due date reminder scan complete! {$sent_count} member notification(s) dispatched.");
header("Location: fines_management.php");
exit();
?>
