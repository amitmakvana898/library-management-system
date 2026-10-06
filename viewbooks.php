<?php
// viewbooks.php - Admin Book Inventory Management with Server-Side Pagination & CSRF
require_once __DIR__ . '/includes/config.php';
require_admin();

$category_filter = trim($_GET['category'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$search_query = trim($_GET['q'] ?? '');

$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;

// Base WHERE conditions
$where = " WHERE 1=1";
$params = [];
$types = "";

if (!empty($search_query)) {
    $where .= " AND (title LIKE ? OR authorname LIKE ? OR category LIKE ? OR CAST(bookid AS CHAR) LIKE ?)";
    $like = "%{$search_query}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

if (!empty($category_filter)) {
    $where .= " AND category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if (!empty($status_filter)) {
    $where .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// 1. Total Count Query for Pagination
$count_sql = "SELECT COUNT(*) as total FROM books" . $where;
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = max(1, ceil($total_rows / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// 2. Fetch Paginated Records
$sql = "SELECT * FROM books" . $where . " ORDER BY id DESC LIMIT ? OFFSET ?";
$params_page = $params;
$params_page[] = $per_page;
$params_page[] = $offset;
$types_page = $types . "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types_page, ...$params_page);
$stmt->execute();
$books = $stmt->get_result();

$categories = $conn->query("SELECT DISTINCT name FROM categories ORDER BY name ASC");

// Helper to build pagination query URLs
function build_page_url($page_num) {
    $params = $_GET;
    $params['page'] = $page_num;
    return 'viewbooks.php?' . http_build_query($params);
}

$is_dashboard = true;
$page_title = "Book Inventory";
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/includes/admin_sidebar.php'; ?>

    <div class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="d-flex align-items-center gap-3">
                <a href="admin_dashboard.php" class="btn btn-sm btn-secondary-custom px-3 py-2 rounded-3" title="Back to Dashboard">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back
                </a>
                <div>
                    <h5 class="fw-bold mb-0 text-main font-space">Book Catalog & Inventory</h5>
                    <small class="text-muted">Manage, edit, and track physical copies and digital E-Book PDF assets</small>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <a href="export_reports.php?type=books" class="btn btn-secondary-custom px-3 py-2">
                    <i class="fa-solid fa-file-excel text-success me-1"></i> Export CSV
                </a>
                <a href="addbook.php" class="btn btn-primary-custom px-3 py-2 fw-bold">
                    <i class="fa-solid fa-circle-plus me-1"></i> Add New Book
                </a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php display_flash(); ?>

            <!-- Filter Controls -->
            <div class="custom-card p-3 mb-4">
                <form method="GET" action="viewbooks.php" class="row g-2 align-items-center">
                    <div class="col-md-5 position-relative">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" name="q" id="tableSearchInput" class="form-control live-search-input" placeholder="Search by title, author, book ID..." value="<?php echo htmlspecialchars($search_query); ?>" autocomplete="off">
                            <button type="submit" class="btn btn-primary-custom px-3">Search</button>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <select name="category" class="form-select" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php if ($categories && $categories->num_rows > 0): ?>
                                <?php while ($c = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($c['name']); ?>" <?php echo $category_filter === $c['name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="Available" <?php echo $status_filter === 'Available' ? 'selected' : ''; ?>>Available</option>
                            <option value="Issued" <?php echo $status_filter === 'Issued' ? 'selected' : ''; ?>>Issued</option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary-custom w-100">Filter</button>
                        <?php if (!empty($search_query) || !empty($category_filter) || !empty($status_filter)): ?>
                            <a href="viewbooks.php" class="btn btn-secondary-custom" title="Reset Filters"><i class="fa-solid fa-rotate-left"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Books Table -->
            <div class="custom-table-card">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-book-bookmark text-primary me-2"></i> Catalog Books (<?php echo $total_rows; ?> Total)</h6>
                    <small class="text-muted">Showing <?php echo min($total_rows, $offset + 1); ?> - <?php echo min($total_rows, $offset + $per_page); ?> of <?php echo $total_rows; ?></small>
                </div>

                <div class="table-responsive">
                    <table class="custom-table" id="booksInventoryTable">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Book Details</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Stock / Qty</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($books && $books->num_rows > 0): ?>
                                <?php while ($b = $books->fetch_assoc()): ?>
                                    <?php 
                                        $img = (!empty($b['book_img']) && file_exists($b['book_img'])) ? $b['book_img'] : 'img1.jpg';
                                        $is_avail = (strtolower($b['status']) === 'available');
                                        $total_c = intval($b['quantity'] ?? 1);
                                        $avail_c = intval($b['available_copies'] ?? 1);
                                        $has_pdf = (!empty($b['pdf_file']) && file_exists($b['pdf_file']));
                                    ?>
                                    <tr>
                                        <td class="fw-bold text-muted">#<?php echo htmlspecialchars($b['bookid'] ?? $b['id']); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?php echo htmlspecialchars($img); ?>" alt="Cover" class="rounded shadow-sm flex-shrink-0" style="width: 42px; height: 58px; object-fit: cover;">
                                                <div>
                                                    <a href="books.php?q=<?php echo urlencode($b['title']); ?>" target="_blank" class="fw-bold text-main d-block mb-1 text-decoration-none">
                                                        <?php echo htmlspecialchars($b['title']); ?>
                                                    </a>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <small class="text-muted"><i class="fa-solid fa-layer-group me-1"></i> Shelf: <?php echo htmlspecialchars($b['rack_no'] ?? 'Rack 1'); ?></small>
                                                        <?php if ($has_pdf): ?>
                                                            <a href="read_ebook.php?id=<?php echo $b['id']; ?>" target="_blank" class="badge bg-danger text-white text-decoration-none px-2 py-0" style="font-size: 0.65rem;">
                                                                <i class="fa-solid fa-file-pdf me-1"></i> Read PDF
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="text-muted small"><?php echo htmlspecialchars($b['authorname']); ?></span></td>
                                        <td><span class="badge-category"><?php echo htmlspecialchars($b['category']); ?></span></td>
                                        <td>
                                            <span class="fw-bold <?php echo $avail_c > 0 ? 'text-success' : 'text-danger'; ?>"><?php echo $avail_c; ?></span> <span class="text-muted">/ <?php echo $total_c; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($is_avail && $avail_c > 0): ?>
                                                <span class="badge-pill-success">Available</span>
                                            <?php else: ?>
                                                <span class="badge-pill-warning">Issued</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a href="issuebooks.php?book_id=<?php echo $b['bookid']; ?>" class="btn btn-sm btn-secondary-custom py-1 px-2" title="Issue this Book">
                                                    <i class="fa-solid fa-handshake text-primary"></i>
                                                </a>
                                                <a href="edit_book.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-secondary-custom py-1 px-2" title="Edit Book">
                                                    <i class="fa-solid fa-pen-to-square text-info"></i>
                                                </a>
                                                <a href="delete_book.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-secondary-custom py-1 px-2 text-danger" onclick="return confirm('Delete book \'<?php echo htmlspecialchars($b['title']); ?>\'?');" title="Delete Book">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-book-open fs-1 text-primary d-block mb-3"></i>
                                        <h6 class="fw-bold">No Books Found</h6>
                                        <p class="small mb-0">Try clearing your filters or add a new title to your library inventory.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Server-Side Pagination Bar -->
                <?php if ($total_pages > 1): ?>
                    <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Page <strong><?php echo $page; ?></strong> of <strong><?php echo $total_pages; ?></strong></small>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo build_page_url($page - 1); ?>"><i class="fa-solid fa-chevron-left"></i></a>
                                </li>
                                <?php for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++): ?>
                                    <li class="page-item <?php echo ($p == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo build_page_url($p); ?>"><?php echo $p; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo build_page_url($page + 1); ?>"><i class="fa-solid fa-chevron-right"></i></a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
