<?php
// my_wishlist.php - Student's Saved & Favorite Books (Cyber Emerald Edition)
require_once __DIR__ . '/includes/config.php';
require_user();

$user = get_logged_user($conn);
$user_id = $user['id'];

$wishlist = $conn->query("
    SELECT w.id as wish_id, b.* 
    FROM wishlist w 
    JOIN books b ON w.book_id = b.id 
    WHERE w.user_id = $user_id 
    ORDER BY w.id DESC
");

$is_dashboard = true;
$page_title = "My Saved Wishlist";
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/includes/user_sidebar.php'; ?>

    <div class="dashboard-main">
        <div class="dashboard-topbar">
            <div>
                <h5 class="fw-bold mb-0 text-main font-space">My Saved Wishlist</h5>
                <small class="text-muted">Books you bookmarked to reserve, borrow, or read later</small>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <a href="user_dashboard.php" class="btn btn-secondary-custom px-3 py-2">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
                </a>
                <a href="books.php" class="btn btn-primary-custom px-3 py-2 fw-bold">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Explore More Books
                </a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php display_flash(); ?>

            <div class="row g-4">
                <?php if ($wishlist && $wishlist->num_rows > 0): ?>
                    <?php while ($b = $wishlist->fetch_assoc()): ?>
                        <?php 
                            $img = (!empty($b['book_img']) && file_exists($b['book_img'])) ? $b['book_img'] : 'img1.jpg';
                            $is_avail = (strtolower($b['status']) === 'available');
                            $has_pdf = (!empty($b['pdf_file']) && file_exists($b['pdf_file']));
                        ?>
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <div class="book-card-clean h-100 d-flex flex-column shadow-sm">
                                <div class="book-cover-wrap">
                                    <img src="<?php echo htmlspecialchars($img); ?>" alt="Cover" class="book-cover-img">
                                    
                                    <div class="position-absolute top-0 start-0 m-2">
                                        <a href="toggle_wishlist.php?book_id=<?php echo $b['id']; ?>" class="btn btn-sm btn-danger rounded-circle shadow-sm" style="width: 34px; height: 34px; display: flex; align-items: center; justify-content: center;" title="Remove from Wishlist">
                                            <i class="fa-solid fa-heart text-white"></i>
                                        </a>
                                    </div>

                                    <div class="position-absolute top-0 end-0 m-2 d-flex flex-column gap-1 align-items-end">
                                        <?php if ($is_avail): ?>
                                            <span class="badge-pill-success">Available</span>
                                        <?php else: ?>
                                            <span class="badge-pill-warning">Issued</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($has_pdf): ?>
                                            <span class="badge-pill-danger"><i class="fa-solid fa-file-pdf me-1"></i> E-Book</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="p-3 d-flex flex-column flex-grow-1" style="background: var(--card-bg);">
                                    <span class="badge-category mb-2 align-self-start"><?php echo htmlspecialchars($b['category']); ?></span>
                                    <h6 class="fw-bold text-main mb-1 text-truncate"><?php echo htmlspecialchars($b['title']); ?></h6>
                                    <p class="text-muted small mb-3">By <?php echo htmlspecialchars($b['authorname']); ?></p>
                                    
                                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Copies: <strong class="text-main"><?php echo intval($b['available_copies'] ?? 1); ?></strong></small>
                                        <a href="books.php?q=<?php echo urlencode($b['title']); ?>" class="btn btn-sm btn-primary-custom py-1 px-3 fw-semibold">
                                            Borrow / View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="fa-solid fa-heart-crack fs-1 text-primary d-block mb-3"></i>
                        <h4 class="fw-bold text-main">Your Wishlist is Empty</h4>
                        <p class="text-muted">Browse our catalog and click the heart icon on any book to bookmark it here.</p>
                        <a href="books.php" class="btn btn-primary-custom px-4 py-2 mt-2">Browse Catalog</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
