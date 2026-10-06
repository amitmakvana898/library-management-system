<?php
// toggle_waitlist.php - Handle "Notify Me When Available" Waitlist subscriptions
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

if (!is_user_logged_in()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please login to subscribe for book availability alerts.',
        'redirect' => 'userlogin.php'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$book_id = intval($_POST['book_id'] ?? 0);

if ($book_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid book specified.']);
    exit();
}

// Check if book exists
$b_chk = $conn->query("SELECT title FROM books WHERE id = $book_id LIMIT 1");
if (!$b_chk || $b_chk->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Book not found.']);
    exit();
}
$book_title = $b_chk->fetch_assoc()['title'];

// Check existing waitlist entry
$chk = $conn->query("SELECT id FROM book_waitlist WHERE user_id = $user_id AND book_id = $book_id LIMIT 1");

if ($chk && $chk->num_rows > 0) {
    // Remove from waitlist
    $del = $conn->query("DELETE FROM book_waitlist WHERE user_id = $user_id AND book_id = $book_id");
    echo json_encode([
        'status' => 'removed',
        'message' => 'You will no longer receive availability alerts for "' . htmlspecialchars($book_title) . '".'
    ]);
} else {
    // Insert into waitlist
    $ins = $conn->prepare("INSERT INTO book_waitlist (user_id, book_id, notified) VALUES (?, ?, 0)");
    $ins->bind_param("ii", $user_id, $book_id);
    if ($ins->execute()) {
        echo json_encode([
            'status' => 'added',
            'message' => 'Alert set! We will notify you as soon as "' . htmlspecialchars($book_title) . '" is returned.'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
}
exit();
