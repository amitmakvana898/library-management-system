<?php
// pay_fine.php - Student Online Fine Payment Simulator (UPI / NetBanking / Cards)
require_once __DIR__ . '/includes/config.php';
require_user();

$user = get_logged_user($conn);
$user_id = $user['id'];

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' || isset($_POST['ajax']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $issue_id = intval($_POST['issue_id'] ?? 0);
    $pay_all = intval($_POST['pay_all'] ?? 0);
    $payment_method = trim($_POST['payment_method'] ?? 'UPI (Google Pay / PhonePe / Paytm)');
    $txn_id = 'LMS-TXN-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));

    $total_paid = 0.0;

    if ($pay_all === 1) {
        // Pay all outstanding fines for this student
        $unpaid_res = $conn->query("SELECT id, fine, book_title FROM book_issued WHERE user_id = '$user_id' AND fine_status = 'unpaid' AND fine > 0");
        if ($unpaid_res && $unpaid_res->num_rows > 0) {
            while ($row = $unpaid_res->fetch_assoc()) {
                $total_paid += floatval($row['fine']);
                $conn->query("UPDATE book_issued SET fine_status = 'paid', remarks = CONCAT(COALESCE(remarks,''), ' [Paid online via $payment_method Ref:$txn_id]') WHERE id = {$row['id']}");
            }
        }
    } elseif ($issue_id > 0) {
        // Pay single fine
        $stmt = $conn->prepare("SELECT id, fine, book_title FROM book_issued WHERE id = ? AND user_id = ? AND fine_status = 'unpaid' LIMIT 1");
        $stmt->bind_param("ii", $issue_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $issue = $res->fetch_assoc();
            $total_paid = floatval($issue['fine']);
            $conn->query("UPDATE book_issued SET fine_status = 'paid', remarks = CONCAT(COALESCE(remarks,''), ' [Paid online via $payment_method Ref:$txn_id]') WHERE id = $issue_id");
        }
    }

    if ($total_paid > 0) {
        // Create in-app student notification & receipt
        $notif_title = "Online Fine Paid: ₹" . number_format($total_paid, 2);
        $notif_msg = "Your fine payment of ₹" . number_format($total_paid, 2) . " via {$payment_method} was successful. Transaction ID: {$txn_id}";
        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'success')");
        $notif_stmt->bind_param("iss", $user_id, $notif_title, $notif_msg);
        $notif_stmt->execute();

        if ($is_ajax) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Payment successful! ₹' . number_format($total_paid, 2) . ' cleared.',
                'txn_id' => $txn_id,
                'amount' => $total_paid
            ]);
            exit();
        }

        set_flash('success', "Payment successful! ₹" . number_format($total_paid, 2) . " fine cleared. Ref ID: " . $txn_id);
    } else {
        if ($is_ajax) {
            echo json_encode(['status' => 'error', 'message' => 'No outstanding fines found to clear.']);
            exit();
        }
        set_flash('info', 'No outstanding fines found to clear.');
    }

    header("Location: user_dashboard.php");
    exit();
}

header("Location: user_dashboard.php");
exit();
