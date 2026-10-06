<?php
// aboutus.php
require_once __DIR__ . '/includes/config.php';
$site = get_site_settings($conn);

$page_title = "About Us";
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5 bg-light border-bottom">
    <div class="container text-center">
        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold mb-3">Our Legacy & Mission</span>
        <h1 class="fw-bold text-dark">About <?php echo htmlspecialchars($site['library_name']); ?></h1>
        <p class="text-muted mx-auto" style="max-width: 650px;">
            Empowering students, researchers, and book lovers with seamless access to world-class literature, academic textbooks, and digital learning assets.
        </p>
    </div>
</div>

<div class="py-5">
    <div class="container">
        <div class="row align-items-center g-5 mb-5">
            <div class="col-lg-6">
                <img src="img3.jpg" alt="About Library" class="img-fluid rounded-4 shadow-xl border border-white" style="max-height: 400px; width: 100%; object-fit: cover;">
            </div>
            <div class="col-lg-6">
                <h6 class="text-primary fw-bold text-uppercase small">Knowledge for Everyone</h6>
                <h2 class="fw-bold text-dark mb-3">Empowering Minds Through Reading</h2>
                <p class="text-muted mb-4">
                    Founded with a passion for learning, <?php echo htmlspecialchars($site['library_name']); ?> serves as a hub of knowledge, collaboration, and inspiration. Our automated library management software streamlines cataloging, borrowing, and inventory control.
                </p>
                
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3">
                            <i class="fa-solid fa-check-circle text-success fs-4"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Vast Collection</h6>
                                <small class="text-muted">Thousands of titles</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3">
                            <i class="fa-solid fa-check-circle text-success fs-4"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Smart Circulation</h6>
                                <small class="text-muted">Instant issue & return</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Library Rules -->
        <div class="custom-card p-5 bg-white border mb-5">
            <h3 class="fw-bold text-dark mb-4 text-center">Library Rules & Member Guidelines</h3>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="p-3 border rounded-3 h-100">
                        <div class="stat-icon primary mb-3">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Membership Policy</h5>
                        <p class="text-muted small mb-0">Every registered student must maintain an active membership account. A maximum of 3 books can be borrowed at any one time.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded-3 h-100">
                        <div class="stat-icon warning mb-3">
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Borrowing Duration</h5>
                        <p class="text-muted small mb-0">Books are issued for a standard duration of <strong><?php echo intval($site['loan_days'] ?? 14); ?> days</strong>. Renewals can be requested prior to the due date.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded-3 h-100">
                        <div class="stat-icon danger mb-3">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Late Return Fines</h5>
                        <p class="text-muted small mb-0">A nominal late fee of <strong>₹<?php echo number_format($site['fine_per_day'] ?? 5, 2); ?> per day</strong> is charged on overdue returns to encourage fair sharing.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
