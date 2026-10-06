<?php
// api_search_books.php - Instant Live Auto-Suggest Book Search API
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');

if (mb_strlen($q) < 1) {
    echo json_encode(['status' => 'success', 'results' => []]);
    exit();
}

$like = "%{$q}%";
$sql = "SELECT id, bookid, title, authorname, category, status, available_copies, book_img, pdf_file 
        FROM books 
        WHERE title LIKE ? OR authorname LIKE ? OR category LIKE ? OR isbn LIKE ? OR CAST(bookid AS CHAR) LIKE ? 
        ORDER BY 
          CASE 
            WHEN title LIKE ? THEN 1 
            WHEN authorname LIKE ? THEN 2 
            ELSE 3 
          END, 
          id DESC 
        LIMIT 7";

$stmt = $conn->prepare($sql);
$prefix_like = "{$q}%";
$stmt->bind_param("sssssss", $like, $like, $like, $like, $like, $prefix_like, $prefix_like);
$stmt->execute();
$res = $stmt->get_result();

$results = [];
while ($row = $res->fetch_assoc()) {
    $img = !empty($row['book_img']) && file_exists(__DIR__ . '/' . $row['book_img']) ? $row['book_img'] : 'assets/images/default_book.svg';
    $avail = intval($row['available_copies'] ?? 0);
    $has_pdf = !empty($row['pdf_file']) && file_exists(__DIR__ . '/' . $row['pdf_file']);

    $results[] = [
        'id' => intval($row['id']),
        'title' => $row['title'],
        'author' => $row['authorname'],
        'category' => $row['category'],
        'status' => $row['status'],
        'available_copies' => $avail,
        'image' => $img,
        'has_ebook' => $has_pdf,
        'url' => 'books.php?q=' . urlencode($row['title']),
        'reader_url' => $has_pdf ? 'read_ebook.php?id=' . $row['id'] : null
    ];
}

echo json_encode([
    'status' => 'success',
    'query' => $q,
    'total' => count($results),
    'results' => $results
]);
exit();
