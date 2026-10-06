<?php
// export_reports.php - 1-Click CSV/Excel Report Exporter for Admin
require_once __DIR__ . '/includes/config.php';
require_admin();

$type = trim($_GET['type'] ?? 'books');
$filename = "lms_" . $type . "_report_" . date('Y-m-d_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
// Add UTF-8 BOM for Microsoft Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

if ($type === 'circulation') {
    fputcsv($output, ['Loan ID', 'Book Accession ID', 'Book Title', 'Borrower Name', 'Borrower Email', 'Borrower Phone', 'Issue Date', 'Due Date', 'Return Date', 'Fine Amount (INR)', 'Fine Status', 'Loan Status']);
    
    $res = $conn->query("
        SELECT bi.*, u.username, u.email, u.mobileno, b.title as catalog_title 
        FROM book_issued bi 
        LEFT JOIN users u ON bi.user_id = u.id 
        LEFT JOIN books b ON bi.bookid = b.id 
        ORDER BY bi.id DESC
    ");
    while ($row = $res->fetch_assoc()) {
        $title = !empty($row['book_title']) ? $row['book_title'] : (!empty($row['catalog_title']) ? $row['catalog_title'] : 'Book #'.$row['bookid']);
        fputcsv($output, [
            $row['id'],
            $row['bookid'],
            $title,
            $row['username'] ?? 'User #'.$row['user_id'],
            $row['email'] ?? '',
            $row['mobileno'] ?? '',
            $row['issue_date'],
            $row['due_date'],
            $row['return_date'],
            $row['fine'],
            $row['fine_status'] ?? 'paid',
            $row['status']
        ]);
    }
} elseif ($type === 'fines') {
    fputcsv($output, ['Loan ID', 'Book Title', 'Borrower Name', 'Borrower Email', 'Due Date', 'Return Date', 'Fine Amount (INR)', 'Settlement Status', 'Remarks']);
    
    $res = $conn->query("
        SELECT bi.*, u.username, u.email, b.title as catalog_title 
        FROM book_issued bi 
        LEFT JOIN users u ON bi.user_id = u.id 
        LEFT JOIN books b ON bi.bookid = b.id 
        WHERE bi.fine > 0 
        ORDER BY bi.id DESC
    ");
    while ($row = $res->fetch_assoc()) {
        $title = !empty($row['book_title']) ? $row['book_title'] : (!empty($row['catalog_title']) ? $row['catalog_title'] : 'Book #'.$row['bookid']);
        fputcsv($output, [
            $row['id'],
            $title,
            $row['username'] ?? 'User #'.$row['user_id'],
            $row['email'] ?? '',
            $row['due_date'],
            $row['return_date'],
            $row['fine'],
            $row['fine_status'] ?? 'paid',
            $row['remarks'] ?? ''
        ]);
    }
} elseif ($type === 'users') {
    fputcsv($output, ['Member ID', 'Full Name', 'Email Address', 'Mobile Number', 'Account Status', 'Active Loans', 'Total Borrow History', 'Joined Date']);
    
    $res = $conn->query("
        SELECT u.*, 
        (SELECT COUNT(*) FROM book_issued WHERE user_id = u.id AND status = 'issued') as active_loans_count,
        (SELECT COUNT(*) FROM book_issued WHERE user_id = u.id) as total_loans_count 
        FROM users u 
        ORDER BY u.id DESC
    ");
    while ($row = $res->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['username'],
            $row['email'],
            $row['mobileno'],
            $row['status'] ?? 'active',
            $row['active_loans_count'],
            $row['total_loans_count'],
            $row['created_at'] ?? ''
        ]);
    }
} else { // default: books inventory
    fputcsv($output, ['Database ID', 'Accession ID', 'Title', 'Author', 'Category', 'Shelf Location', 'Publisher', 'Status', 'Is E-Book']);
    
    $res = $conn->query("SELECT * FROM books ORDER BY id DESC");
    while ($row = $res->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['bookid'],
            $row['title'],
            $row['authorname'],
            $row['category'],
            $row['rack_no'] ?? 'Rack 1',
            $row['publisher'] ?? '',
            $row['status'],
            (!empty($row['pdf_file'])) ? 'Yes' : 'No'
        ]);
    }
}

fclose($output);
exit();
?>
