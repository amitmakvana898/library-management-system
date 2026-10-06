<?php
// manage_categories.php - Book Category Management (Cyber Emerald Edition)
require_once __DIR__ . '/includes/config.php';
require_admin();

$error = "";

// Handle Add / Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = "Category name cannot be empty.";
    } elseif ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        if ($stmt->execute()) {
            set_flash('success', 'Category "' . htmlspecialchars($name) . '" created successfully.');
            header("Location: manage_categories.php");
            exit();
        } else {
            $error = "Category already exists or error: " . $conn->error;
        }
    } elseif ($action === 'edit') {
        $cat_id = intval($_POST['cat_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $description, $cat_id);
        if ($stmt->execute()) {
            set_flash('success', 'Category updated successfully.');
            header("Location: manage_categories.php");
            exit();
        } else {
            $error = "Failed to update category: " . $conn->error;
        }
    }
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        set_flash('success', 'Category removed successfully.');
    } else {
        set_flash('danger', 'Failed to delete category: ' . $conn->error);
    }
    header("Location: manage_categories.php");
    exit();
}

// Fetch all categories with counts
$categories = $conn->query("
    SELECT c.*, COUNT(b.id) as book_count 
    FROM categories c 
    LEFT JOIN books b ON c.name = b.category 
    GROUP BY c.id 
    ORDER BY c.name ASC
");

$is_dashboard = true;
$page_title = "Book Categories";
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/includes/admin_sidebar.php'; ?>

    <div class="dashboard-main">
        <div class="dashboard-topbar">
            <div>
                <h5 class="fw-bold mb-0 text-main font-space">Book Categories & Genres</h5>
                <small class="text-muted">Organize library catalog titles by academic disciplines and genres</small>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <a href="admin_dashboard.php" class="btn btn-secondary-custom px-3 py-2">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
                </a>
                <a href="viewbooks.php" class="btn btn-secondary-custom px-3 py-2">
                    <i class="fa-solid fa-book me-1"></i> View Books
                </a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php display_flash(); ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Add Category Form -->
                <div class="col-lg-4">
                    <div class="custom-card p-4 shadow-sm">
                        <h6 class="fw-bold text-main mb-3 font-space"><i class="fa-solid fa-folder-plus text-primary me-2"></i> Create Category</h6>
                        <form method="POST" action="manage_categories.php" class="d-flex flex-column gap-3">
                            <input type="hidden" name="action" value="add">
                            <div>
                                <label class="form-label fw-semibold small text-muted">Category Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Computer Science" required>
                            </div>
                            <div>
                                <label class="form-label fw-semibold small text-muted">Description (Optional)</label>
                                <textarea name="description" rows="3" class="form-control" placeholder="Brief overview of this genre..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary-custom w-100 py-2 mt-2 fw-bold">
                                <i class="fa-solid fa-plus me-1"></i> Save Category
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Categories Table -->
                <div class="col-lg-8">
                    <div class="custom-table-card">
                        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold text-main mb-0 font-space"><i class="fa-solid fa-layer-group text-primary me-2"></i> Active Categories Repository</h6>
                                <small class="text-muted">Total category volumes indexed</small>
                            </div>
                            <span class="badge bg-primary bg-opacity-20 text-primary border border-primary border-opacity-30 rounded-pill px-3 py-2 fw-bold">
                                <?php echo $categories ? $categories->num_rows : 0; ?> Categories
                            </span>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="custom-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Category Name</th>
                                        <th>Description</th>
                                        <th>Catalog Volumes</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($categories && $categories->num_rows > 0): ?>
                                        <?php while ($cat = $categories->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-main">
                                                        <i class="fa-regular fa-folder text-primary me-1"></i> <?php echo htmlspecialchars($cat['name']); ?>
                                                    </div>
                                                </td>
                                                <td><span class="small text-muted"><?php echo htmlspecialchars($cat['description'] ?? '-'); ?></span></td>
                                                <td>
                                                    <span class="badge bg-primary bg-opacity-20 text-primary border border-primary border-opacity-30 rounded-pill px-3 py-1 fw-bold">
                                                        <?php echo $cat['book_count']; ?> Titles
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="d-inline-flex gap-1">
                                                        <a href="viewbooks.php?category=<?php echo urlencode($cat['name']); ?>" class="btn btn-sm btn-secondary-custom py-1 px-2" title="View Books in Category">
                                                            <i class="fa-solid fa-eye text-primary"></i>
                                                        </a>
                                                        <a href="manage_categories.php?delete=<?php echo $cat['id']; ?>" class="btn btn-sm btn-secondary-custom py-1 px-2 text-danger" onclick="return confirm('Are you sure you want to delete category \'<?php echo htmlspecialchars($cat['name']); ?>\'?');" title="Delete Category">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-muted">
                                                <i class="fa-solid fa-folder-open fs-1 text-primary d-block mb-3"></i>
                                                <h6 class="fw-bold">No Categories Found</h6>
                                                <p class="small mb-0">Create your first book category using the form on the left.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
