<?php
// add_db_indexes.php - Apply High-Performance Indexes for Scalable Querying
require_once __DIR__ . '/includes/config.php';

function add_index_safely($conn, $table, $index_name, $columns) {
    $chk = $conn->query("SHOW INDEX FROM `$table` WHERE Key_name = '$index_name'");
    if ($chk && $chk->num_rows > 0) {
        echo "Index `$index_name` already exists on `$table`." . PHP_EOL;
        return;
    }
    $sql = "ALTER TABLE `$table` ADD INDEX `$index_name` ($columns)";
    if ($conn->query($sql)) {
        echo "Successfully added index `$index_name` on `$table` ($columns)." . PHP_EOL;
    } else {
        echo "Failed to add index on `$table`: " . $conn->error . PHP_EOL;
    }
}

echo "=== APPLYING ENTERPRISE DATABASE PERFORMANCE INDEXES ===" . PHP_EOL;

add_index_safely($conn, 'books', 'idx_books_category', '`category`');
add_index_safely($conn, 'books', 'idx_books_status', '`status`');
add_index_safely($conn, 'books', 'idx_books_title', '`title`(100)');
add_index_safely($conn, 'books', 'idx_books_isbn', '`isbn`');

add_index_safely($conn, 'book_issued', 'idx_issued_user_status', '`user_id`, `status`');
add_index_safely($conn, 'book_issued', 'idx_issued_due_date', '`due_date`');
add_index_safely($conn, 'book_issued', 'idx_issued_bookid', '`bookid`');

add_index_safely($conn, 'notifications', 'idx_notif_user_read', '`user_id`, `is_read`');
add_index_safely($conn, 'book_requests', 'idx_requests_user_status', '`user_id`, `status`');
add_index_safely($conn, 'wishlist', 'idx_wishlist_user_book', '`user_id`, `book_id`');

echo "All performance indexes configured successfully!" . PHP_EOL;
