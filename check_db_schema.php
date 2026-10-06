<?php
require_once __DIR__ . '/includes/config.php';

$tables = ['admins', 'books', 'book_issued', 'book_requests', 'book_reviews', 'categories', 'messages', 'notifications', 'reviews', 'site_settings', 'users', 'wishlist'];

foreach ($tables as $t) {
    echo "=== TABLE: $t ===" . PHP_EOL;
    $res = $conn->query("DESCRIBE $t");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            echo "  - {$r['Field']} ({$r['Type']})" . PHP_EOL;
        }
    } else {
        echo "  [NOT FOUND OR ERROR: " . $conn->error . "]" . PHP_EOL;
    }
}
