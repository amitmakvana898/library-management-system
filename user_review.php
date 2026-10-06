<?php
// user_review.php - Submit Rating & Feedback
require_once __DIR__ . '/includes/config.php';
require_user();

$user = get_logged_user($conn);
$user_id = $user['id'];
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 5);
    $review_text = trim($_POST['review_text'] ?? '');

    if (empty($review_text)) {
        $error = "Please write your review / feedback before submitting.";
    } else {
        $ins = $conn->prepare("INSERT INTO reviews (user_id, review_text, rating, is_approved, is_read) VALUES (?, ?, ?, 1, 0)");
        $ins->bind_param("isi", $user_id, $review_text, $rating);

        if ($ins->execute()) {
            set_flash('success', 'Thank you! Your review has been submitted.');
            header("Location: user_review.php");
            exit();
        } else {
            $error = "Failed to submit review: " . $conn->error;
        }
    }
}

// Fetch user's previous reviews
$my_reviews = $conn->query("SELECT * FROM reviews WHERE user_id = '$user_id' ORDER BY id DESC");

$is_dashboard = true;
$page_title = "Library Feedback";
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/includes/user_sidebar.php'; ?>

    <div class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="sidebar-toggle" class="btn btn-sm btn-outline-secondary d-lg-none">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h5 class="fw-bold mb-0 text-main font-space">Submit Feedback & Review</h5>
                    <small class="text-muted">Share your reading experience and library suggestions</small>
                </div>
            </div>
            <a href="user_dashboard.php" class="btn btn-sm btn-secondary-custom">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>

        <div class="dashboard-content">
            <?php display_flash(); ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4 justify-content-center">
                <div class="col-lg-6">
                    <div class="custom-card p-4">
                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-star text-warning me-2"></i> Write a Testimonial</h6>
                        
                        <form method="POST" action="user_review.php" class="d-flex flex-column gap-3">
                            <div>
                                <label class="form-label fw-semibold small text-muted">Overall Rating (Stars)</label>
                                <select name="rating" class="form-select form-select-custom">
                                    <option value="5">★★★★★ - Excellent (5 Stars)</option>
                                    <option value="4">★★★★☆ - Very Good (4 Stars)</option>
                                    <option value="3">★★★☆☆ - Average (3 Stars)</option>
                                    <option value="2">★★☆☆☆ - Fair (2 Stars)</option>
                                    <option value="1">★☆☆☆☆ - Poor (1 Star)</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label fw-semibold small text-muted">Your Review / Comments *</label>
                                <textarea name="review_text" rows="5" class="form-control form-control-custom" placeholder="Tell us what you love about the library or books you recommend..." required></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary-custom w-100 py-3 mt-2">
                                <i class="fa-solid fa-paper-plane me-1"></i> Submit Feedback
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="custom-table-card">
                        <div class="p-3 border-bottom">
                            <h6 class="fw-bold text-dark mb-0">My Submitted Reviews</h6>
                        </div>
                        <div class="p-3">
                            <?php if ($my_reviews && $my_reviews->num_rows > 0): ?>
                                <div class="d-flex flex-column gap-3">
                                    <?php while ($rev = $my_reviews->fetch_assoc()): ?>
                                        <div class="p-3 bg-light rounded-3 border">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div class="text-warning small">
                                                    <?php 
                                                        $rating = $rev['rating'] ?? 5;
                                                        for ($i = 0; $i < 5; $i++) {
                                                            echo ($i < $rating) ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
                                                        }
                                                    ?>
                                                </div>
                                                <small class="text-muted"><?php echo date('d M, Y', strtotime($rev['created_at'])); ?></small>
                                            </div>
                                            <p class="small text-dark mb-0">"<?php echo nl2br(htmlspecialchars($rev['review_text'])); ?>"</p>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted small text-center py-4 mb-0">You haven't submitted any reviews yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
