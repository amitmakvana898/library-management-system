<?php
// toggle_wishlist.php - 1-Click Save/Favorite Book for Later
require_once __DIR__ . '/includes/config.php';

if (!is_user_logged_in()) {
    set_flash('danger', 'Please login to save books to your wishlist.');
    header("Location: userlogin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$book_id = intval($_GET['book_id'] ?? 0);

if ($book_id > 0) {
    // Check if already in wishlist
    $chk = $conn->query("SELECT id FROM wishlist WHERE user_id = $user_id AND book_id = $book_id");
    if ($chk && $chk->num_rows > 0) {
        $conn->query("DELETE FROM wishlist WHERE user_id = $user_id AND book_id = $book_id");
        set_flash('info', 'Book removed from your Saved Wishlist.');
    } else {
        $conn->query("INSERT INTO wishlist (user_id, book_id) VALUES ($user_id, $book_id)");
        set_flash('success', 'Book saved to your Wishlist!');
    }
}

$ref = $_SERVER['HTTP_REFERER'] ?? 'books.php';
header("Location: " . $ref);
exit();
?>
