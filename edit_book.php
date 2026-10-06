<?php
// edit_book.php - Edit Existing Book with Multi-Copies & E-Book PDF Support
require_once __DIR__ . '/includes/config.php';
require_admin();

$book_id = intval($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM books WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book_res = $stmt->get_result();

if (!$book_res || $book_res->num_rows === 0) {
    set_flash('danger', 'Book not found.');
    header("Location: viewbooks.php");
    exit();
}

$book = $book_res->fetch_assoc();
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $authorname = trim($_POST['authorname'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $custom_category = trim($_POST['custom_category'] ?? '');
    $bookid = intval($_POST['bookid'] ?? $book['bookid']);
    $rack_no = trim($_POST['rack_no'] ?? 'Rack 1');
    $publisher = trim($_POST['publisher'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = trim($_POST['status'] ?? 'Available');
    $quantity = max(1, intval($_POST['quantity'] ?? ($book['quantity'] ?? 1)));
    $available_copies = max(0, intval($_POST['available_copies'] ?? ($book['available_copies'] ?? 1)));

    if (!empty($custom_category)) {
        $category = $custom_category;
        $cat_stmt = $conn->prepare("INSERT IGNORE INTO categories (name, description) VALUES (?, 'Custom category')");
        $cat_stmt->bind_param("s", $category);
        $cat_stmt->execute();
    }

    if (empty($title) || empty($authorname) || empty($category)) {
        $error = "Title, Author, and Category are required.";
    } else {
        $book_img = $book['book_img'];
        if (isset($_FILES['book_img']) && $_FILES['book_img']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['book_img']['tmp_name'];
            $fileName = $_FILES['book_img']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = 'book_' . uniqid('', true) . '.' . $fileExtension;
                $uploadFileDir = 'assets/books/';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0777, true);
                }
                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $book_img = $dest_path;
                }
            }
        }

        $pdf_file = $book['pdf_file'];
        $is_ebook = $book['is_ebook'];
        if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
            $pdfTmpPath = $_FILES['pdf_file']['tmp_name'];
            $pdfName = $_FILES['pdf_file']['name'];
            $pdfExt = strtolower(pathinfo($pdfName, PATHINFO_EXTENSION));

            if ($pdfExt === 'pdf') {
                $newPdfName = 'ebook_' . uniqid('', true) . '.pdf';
                $pdfDir = 'uploads/ebooks/';
                if (!is_dir($pdfDir)) {
                    mkdir($pdfDir, 0777, true);
                }
                $pdfDest = $pdfDir . $newPdfName;
                if (move_uploaded_file($pdfTmpPath, $pdfDest)) {
                    $pdf_file = $pdfDest;
                    $is_ebook = 1;
                }
            }
        }

        $up_stmt = $conn->prepare("UPDATE books SET title=?, authorname=?, category=?, bookid=?, rack_no=?, publisher=?, description=?, status=?, book_img=?, quantity=?, available_copies=?, pdf_file=?, is_ebook=? WHERE id=?");
        $up_stmt->bind_param("sssisssssiisii", $title, $authorname, $category, $bookid, $rack_no, $publisher, $description, $status, $book_img, $quantity, $available_copies, $pdf_file, $is_ebook, $book_id);

        if ($up_stmt->execute()) {
            set_flash('success', 'Book details updated successfully!');
            header("Location: viewbooks.php");
            exit();
        } else {
            $error = "Failed to update book: " . $conn->error;
        }
    }
}

$categories = $conn->query("SELECT DISTINCT name FROM categories ORDER BY name ASC");

$is_dashboard = true;
$page_title = "Edit Book";
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
                    <h5 class="fw-bold mb-0 text-dark">Edit Book: <?php echo htmlspecialchars($book['title']); ?></h5>
                    <small class="text-muted">Update catalog details, copies, or digital E-Book PDF</small>
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
                        <form method="POST" action="edit_book.php?id=<?php echo $book['id']; ?>" enctype="multipart/form-data">
                            <div class="row g-4">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small text-muted">Book Title *</label>
                                        <input type="text" name="title" class="form-control form-control-custom" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small text-muted">Author Name *</label>
                                            <input type="text" name="authorname" class="form-control form-control-custom" value="<?php echo htmlspecialchars($book['authorname']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small text-muted">Publisher</label>
                                            <input type="text" name="publisher" class="form-control form-control-custom" value="<?php echo htmlspecialchars($book['publisher'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small text-muted">Category *</label>
                                            <select name="category" class="form-select form-select-custom" required>
                                                <?php if ($categories && $categories->num_rows > 0): ?>
                                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                                        <option value="<?php echo htmlspecialchars($cat['name']); ?>" <?php echo ($book['category'] === $cat['name']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                                                    <?php endwhile; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small text-muted">Change to New Category</label>
                                            <input type="text" name="custom_category" class="form-control form-control-custom" placeholder="Type new category name...">
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small text-muted">Total Quantity / Copies *</label>
                                            <input type="number" name="quantity" class="form-control form-control-custom" value="<?php echo intval($book['quantity'] ?? 1); ?>" min="1" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold small text-muted">Available Copies *</label>
                                            <input type="number" name="available_copies" class="form-control form-control-custom" value="<?php echo intval($book['available_copies'] ?? 1); ?>" min="0" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small text-muted">Description / Summary</label>
                                        <textarea name="description" rows="4" class="form-control form-control-custom"><?php echo htmlspecialchars($book['description'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded-4 border mb-3">
                                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-sliders text-primary me-2"></i> Placement & Status</h6>
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small text-muted">Book Accession ID</label>
                                            <input type="number" name="bookid" class="form-control form-control-custom" value="<?php echo htmlspecialchars($book['bookid']); ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small text-muted">Shelf / Rack Number</label>
                                            <input type="text" name="rack_no" class="form-control form-control-custom" value="<?php echo htmlspecialchars($book['rack_no'] ?? 'Rack 1'); ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small text-muted">Catalog Status</label>
                                            <select name="status" class="form-select form-select-custom">
                                                <option value="Available" <?php echo ($book['status'] === 'Available') ? 'selected' : ''; ?>>Available</option>
                                                <option value="Reserved" <?php echo ($book['status'] === 'Reserved') ? 'selected' : ''; ?>>Reserved</option>
                                                <option value="Issued" <?php echo ($book['status'] === 'Issued') ? 'selected' : ''; ?>>Issued</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="p-3 bg-light rounded-4 border mb-3">
                                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-file-pdf text-danger me-2"></i> Digital E-Book (PDF)</h6>
                                        <?php if (!empty($book['pdf_file']) && file_exists($book['pdf_file'])): ?>
                                            <div class="mb-2 small text-success fw-semibold"><i class="fa-solid fa-circle-check me-1"></i> E-Book PDF is Attached</div>
                                        <?php endif; ?>
                                        <label class="form-label fw-semibold small text-muted">Upload / Replace PDF</label>
                                        <input type="file" name="pdf_file" accept=".pdf" class="form-control form-control-custom mb-1">
                                    </div>

                                    <div class="p-3 bg-light rounded-4 border">
                                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-image text-primary me-2"></i> Cover Image</h6>
                                        <?php if (!empty($book['book_img']) && file_exists($book['book_img'])): ?>
                                            <div class="mb-2 text-center">
                                                <img src="<?php echo htmlspecialchars($book['book_img']); ?>" alt="Current Cover" class="rounded-3 shadow-sm" style="height: 90px; object-fit: cover;">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" name="book_img" accept="image/*" class="form-control form-control-custom mb-2">
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                                <a href="viewbooks.php" class="btn btn-secondary-custom">Cancel</a>
                                <button type="submit" class="btn btn-primary-custom px-4">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Book Changes
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
