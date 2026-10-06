<?php
// view_reviews.php - Manage User Reviews & Testimonials
require_once __DIR__ . '/includes/config.php';
require_admin();

// Handle Approval Toggle
if (isset($_GET['toggle_app'])) {
    $rid = intval($_GET['toggle_app']);
    $r_res = $conn->query("SELECT is_approved FROM reviews WHERE id = $rid LIMIT 1");
    if ($r_res && $r_res->num_rows > 0) {
        $curr = $r_res->fetch_assoc()['is_approved'];
        $new_st = ($curr == 1) ? 0 : 1;
        $conn->query("UPDATE reviews SET is_approved = $new_st WHERE id = $rid");
        set_flash('success', 'Review display status updated.');
    }
    header("Location: view_reviews.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $rid = intval($_GET['delete']);
    $conn->query("DELETE FROM reviews WHERE id = $rid");
    set_flash('success', 'Review deleted successfully.');
    header("Location: view_reviews.php");
    exit();
}

$reviews = $conn->query("
    SELECT r.*, u.username, u.email, u.profile_img 
    FROM reviews r 
    LEFT JOIN users u ON r.user_id = u.id 
    ORDER BY r.id DESC
");

$is_dashboard = true;
$page_title = "Member Reviews";
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
                    <h5 class="fw-bold mb-0 text-main font-space">Member Reviews & Feedback</h5>
                    <small class="text-muted">Manage testimonials shown on the public landing page</small>
                </div>
            </div>
            <a href="admin_dashboard.php" class="btn btn-sm btn-secondary-custom">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>

        <div class="dashboard-content">
            <?php display_flash(); ?>

            <div class="custom-table-card">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">All Reviews (<?php echo $reviews ? $reviews->num_rows : 0; ?>)</h6>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Rating</th>
                                <th>Review Text</th>
                                <th>Submitted Date</th>
                                <th>Landing Page Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($reviews && $reviews->num_rows > 0): ?>
                                <?php while ($r = $reviews->fetch_assoc()): ?>
                                    <?php 
                                        $u_img = (!empty($r['profile_img']) && file_exists($r['profile_img'])) ? $r['profile_img'] : 'uploads/profile_1.jpg';
                                        if (!file_exists($u_img)) $u_img = 'admin.png';
                                        $is_app = ($r['is_approved'] == 1);
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?php echo htmlspecialchars($u_img); ?>" alt="User" class="rounded-circle border" style="width: 36px; height: 36px; object-fit: cover;">
                                                <div>
                                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($r['username'] ?? 'User #'.$r['user_id']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($r['email'] ?? ''); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-warning small">
                                                <?php 
                                                    $rating = $r['rating'] ?? 5;
                                                    for ($i = 0; $i < 5; $i++) {
                                                        echo ($i < $rating) ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
                                                    }
                                                ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small text-main" style="max-width: 320px;"><?php echo nl2br(htmlspecialchars($r['review_text'])); ?></div>
                                        </td>
                                        <td><?php echo date('d M, Y', strtotime($r['created_at'])); ?></td>
                                        <td>
                                            <?php if ($is_app): ?>
                                                <span class="badge-pill-success"><i class="fa-solid fa-eye me-1"></i> Live on Homepage</span>
                                            <?php else: ?>
                                                <span class="badge-pill-secondary"><i class="fa-solid fa-eye-slash me-1"></i> Hidden</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a href="view_reviews.php?toggle_app=<?php echo $r['id']; ?>" class="btn btn-sm <?php echo $is_app ? 'btn-outline-warning' : 'btn-outline-success'; ?>" title="<?php echo $is_app ? 'Hide from Homepage' : 'Approve & Show on Homepage'; ?>">
                                                    <i class="fa-solid <?php echo $is_app ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                                </a>
                                                <a href="view_reviews.php?delete=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this review?');" title="Delete">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-star-half-stroke fs-2 d-block mb-2"></i>
                                        No reviews submitted yet.
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
