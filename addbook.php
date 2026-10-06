<?php
// addbook.php - Add New Book to Library with E-Book PDF & Multi-Copy Inventory
require_once __DIR__ . '/includes/config.php';
require_admin();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = "Security token mismatch (CSRF). Please refresh and resubmit.";
    } else {
        $title = trim($_POST['title'] ?? '');
        $authorname = trim($_POST['authorname'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $custom_category = trim($_POST['custom_category'] ?? '');
        $bookid = intval($_POST['bookid'] ?? 0);
        $rack_no = trim($_POST['rack_no'] ?? 'Rack 1');
        $publisher = trim($_POST['publisher'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = trim($_POST['status'] ?? 'Available');
        $quantity = max(1, intval($_POST['quantity'] ?? 1));
        $available_copies = max(0, intval($_POST['available_copies'] ?? $quantity));

        // If custom category typed
        if (!empty($custom_category)) {
            $category = $custom_category;
            $cat_stmt = $conn->prepare("INSERT IGNORE INTO categories (name, description) VALUES (?, 'Custom category')");
            $cat_stmt->bind_param("s", $category);
            $cat_stmt->execute();
        }

        if (empty($title) || empty($authorname) || empty($category)) {
            $error = "Title, Author, and Category are required.";
        } else {
            // Auto assign Book ID if empty
            if ($bookid <= 0) {
                $max_id_res = $conn->query("SELECT MAX(bookid) as m FROM books");
                $max_id = $max_id_res ? ($max_id_res->fetch_assoc()['m'] ?? 0) : 0;
                $bookid = $max_id + 1;
            }

            // Secure Image upload handling with MIME verification
            $book_img = "img1.jpg";
            if (isset($_FILES['book_img']) && $_FILES['book_img']['error'] === UPLOAD_ERR_OK) {
                $val = validate_uploaded_file($_FILES['book_img'], ['jpg', 'jpeg', 'png', 'webp'], ['image/jpeg', 'image/png', 'image/webp'], 5);
                if ($val['valid']) {
                    $newFileName = 'book_' . bin2hex(random_bytes(10)) . '.' . $val['extension'];
                    $uploadFileDir = 'assets/books/';
                    if (!is_dir($uploadFileDir)) {
                        mkdir($uploadFileDir, 0777, true);
                    }
                    $dest_path = $uploadFileDir . $newFileName;
                    if (move_uploaded_file($_FILES['book_img']['tmp_name'], $dest_path)) {
                        $book_img = $dest_path;
                    }
                } else {
                    $error = "Cover Image Error: " . $val['error'];
                }
            }

            // Secure PDF / E-Book Upload Handling with MIME verification
            $pdf_file = "";
            $is_ebook = 0;
            if (empty($error) && isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                $pdf_val = validate_uploaded_file($_FILES['pdf_file'], ['pdf'], ['application/pdf', 'application/x-pdf'], 25);
                if ($pdf_val['valid']) {
                    $newPdfName = 'ebook_' . bin2hex(random_bytes(10)) . '.pdf';
                    $pdfDir = 'uploads/ebooks/';
                    if (!is_dir($pdfDir)) {
                        mkdir($pdfDir, 0777, true);
                    }
                    $pdfDest = $pdfDir . $newPdfName;
                    if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $pdfDest)) {
                        $pdf_file = $pdfDest;
                        $is_ebook = 1;
                    }
                } else {
                    $error = "E-Book PDF Error: " . $pdf_val['error'];
                }
            }

            if (empty($error)) {
                $stmt = $conn->prepare("INSERT INTO books (title, authorname, category, bookid, rack_no, publisher, description, status, book_img, quantity, available_copies, pdf_file, is_ebook) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssisssssiisi", $title, $authorname, $category, $bookid, $rack_no, $publisher, $description, $status, $book_img, $quantity, $available_copies, $pdf_file, $is_ebook);

                if ($stmt->execute()) {
                    set_flash('success', 'Book "' . htmlspecialchars($title) . '" has been added successfully with ' . $available_copies . ' copies in stock!');
                    header("Location: viewbooks.php");
                    exit();
                } else {
                    error_log("Add Book Error: " . $conn->error);
                    $error = "Database error occurred while adding book.";
                }
            }
        }
    }
}

$categories = $conn->query("SELECT DISTINCT name FROM categories ORDER BY name ASC");

$is_dashboard = true;
$page_title = "Add New Book";
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/includes/admin_sidebar.php'; ?>

    <div class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="sidebar-toggle" class="btn btn-sm btn-outline-secondary d-lg-none">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Add New Book</h5>
                    <small class="text-muted">Register a new title to the library catalog with multi-copies & E-Book PDF</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="admin_dashboard.php" class="btn btn-sm btn-secondary-custom">
                    <i class="fa-solid fa-gauge me-1"></i> Dashboard
                </a>
                <a href="viewbooks.php" class="btn btn-sm btn-secondary-custom">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Inventory
                </a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="custom-card p-4">
                        <form method="POST" action="addbook.php" enctype="multipart/form-data">
                            <?php echo csrf_field(); ?>
                            <div class="row g-4">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small text-muted">Book Title *</label>
                                        <input type="text" name="title" class="form-control form-control-custom" placeholder="e.g. Introduction to Algorithms" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small text-muted">Author Name *</label>
                                            <input type="text" name="authorname" class="form-control form-control-custom" placeholder="e.g. Thomas H. Cormen" value="<?php echo htmlspecialchars($_POST['authorname'] ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small text-muted">Publisher</label>
                                            <input type="text" name="publisher" class="form-control form-control-custom" placeholder="e.g. MIT Press" value="<?php echo htmlspecialchars($_POST['publisher'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small text-muted">Category *</label>
                                            <select name="category" class="form-select form-select-custom" required>
                                                <option value="">Select Category</option>
                                                <?php if ($categories && $categories->num_rows > 0): ?>
                                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                                        <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                                    <?php endwhile; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small text-muted">Or Add New Category</label>
                                            <input type="text" name="custom_category" class="form-control form-control-custom" placeholder="Type new category name...">
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small text-muted">Total Quantity / Copies *</label>
                                            <input type="number" name="quantity" class="form-control form-control-custom" value="5" min="1" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small text-muted">Available Copies *</label>
                                            <input type="number" name="available_copies" class="form-control form-control-custom" value="5" min="0" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small text-muted">Description / Summary</label>
                                        <textarea name="description" rows="4" class="form-control form-control-custom" placeholder="Overview of the book contents, topics covered, edition notes..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded-4 border mb-3">
                                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-sliders text-primary me-2"></i> Placement & Status</h6>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small text-muted">Custom Book ID (Optional)</label>
                                            <input type="number" name="bookid" class="form-control form-control-custom" placeholder="Auto-generated if empty">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small text-muted">Shelf / Rack Number</label>
                                            <input type="text" name="rack_no" class="form-control form-control-custom" placeholder="e.g. Rack B-4, Shelf 2" value="Rack 1">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small text-muted">Initial Status</label>
                                            <select name="status" class="form-select form-select-custom">
                                                <option value="Available">Available</option>
                                                <option value="Reserved">Reserved</option>
                                                <option value="Issued">Issued</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="p-3 bg-light rounded-4 border mb-3">
                                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-file-pdf text-danger me-2"></i> Digital E-Book (PDF)</h6>
                                        <label class="form-label fw-semibold small text-muted">Upload PDF File (Optional)</label>
                                        <input type="file" name="pdf_file" accept=".pdf" class="form-control form-control-custom mb-1">
                                        <small class="text-muted" style="font-size: 0.75rem;">Allows students to read this book online.</small>
                                    </div>

                                    <div class="p-3 bg-light rounded-4 border">
                                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-image text-primary me-2"></i> Cover Image</h6>
                                        <input type="file" name="book_img" accept="image/*" class="form-control form-control-custom mb-2">
                                        <small class="text-muted" style="font-size: 0.75rem;">Supports JPG, PNG, WebP.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                                <a href="viewbooks.php" class="btn btn-secondary-custom">Cancel</a>
                                <button type="submit" class="btn btn-primary-custom px-4">
                                    <i class="fa-solid fa-plus-circle me-1"></i> Publish Book to Catalog
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
