<?php
// delete_book.php - Safe deletion of book records
require_once __DIR__ . '/includes/config.php';
require_admin();

$book_id = intval($_GET['id'] ?? 0);

if ($book_id > 0) {
    // Check if book exists
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $book = $res->fetch_assoc();
        
        // Check if currently issued to any user
        $chk_issue = $conn->prepare("SELECT id FROM book_issued WHERE bookid = ? AND status = 'issued' LIMIT 1");
        $chk_issue->bind_param("i", $book['bookid']);
        $chk_issue->execute();
        $chk_issue->store_result();

        if ($chk_issue->num_rows > 0) {
            set_flash('danger', 'Cannot delete "' . htmlspecialchars($book['title']) . '" because it is currently issued to a student.');
        } else {
            $del = $conn->prepare("DELETE FROM books WHERE id = ?");
            $del->bind_param("i", $book_id);
            if ($del->execute()) {
                set_flash('success', 'Book "' . htmlspecialchars($book['title']) . '" was deleted successfully.');
            } else {
                set_flash('danger', 'Failed to delete book: ' . $conn->error);
            }
        }
    } else {
        set_flash('danger', 'Book record not found.');
    }
}

header("Location: viewbooks.php");
exit();
?>
