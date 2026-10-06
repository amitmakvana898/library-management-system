<?php
require_once __DIR__ . '/includes/config.php';

// 1. Add renewal_count to book_issued
$chk = $conn->query("SHOW COLUMNS FROM book_issued LIKE 'renewal_count'");
if ($chk && $chk->num_rows === 0) {
    $conn->query("ALTER TABLE book_issued ADD COLUMN renewal_count INT DEFAULT 0");
    echo "Added renewal_count to book_issued" . PHP_EOL;
}

// 2. Modify mobileno in users and admins to VARCHAR(20)
$conn->query("ALTER TABLE users MODIFY mobileno VARCHAR(20)");
$conn->query("ALTER TABLE admins MODIFY mobileno VARCHAR(20)");
echo "Enhanced mobileno to VARCHAR(20)" . PHP_EOL;

// 3. Modify password in admins and users to VARCHAR(255)
$conn->query("ALTER TABLE admins MODIFY password VARCHAR(255)");
$conn->query("ALTER TABLE users MODIFY password VARCHAR(255)");
echo "Enhanced password to VARCHAR(255)" . PHP_EOL;

// 4. Enhance books text and image fields
$conn->query("ALTER TABLE books MODIFY title VARCHAR(255)");
$conn->query("ALTER TABLE books MODIFY authorname VARCHAR(255)");
$conn->query("ALTER TABLE books MODIFY category VARCHAR(100)");
$conn->query("ALTER TABLE books MODIFY book_img VARCHAR(255)");
echo "Enhanced books table field lengths" . PHP_EOL;

// 5. Enhance users table field lengths
$conn->query("ALTER TABLE users MODIFY email VARCHAR(255)");
$conn->query("ALTER TABLE users MODIFY profile_img VARCHAR(255)");
echo "Enhanced users table field lengths" . PHP_EOL;

// 6. Enhance book_issued table fine decimal precision
$conn->query("ALTER TABLE book_issued MODIFY fine DECIMAL(10,2) DEFAULT 0.00");
// 7. Create book_waitlist table for Notify Me / Waitlist alerts
$conn->query("CREATE TABLE IF NOT EXISTS book_waitlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    book_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notified TINYINT(1) DEFAULT 0,
    UNIQUE KEY user_book_waitlist (user_id, book_id)
)");
echo "Enhanced book_waitlist table initialized" . PHP_EOL;

echo "All database schema enhancements applied successfully!" . PHP_EOL;

