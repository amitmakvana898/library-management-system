<?php
// update_index.php - Site Configuration & Hero Content Editor
require_once __DIR__ . '/includes/config.php';
require_admin();

$site = get_site_settings($conn);
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $library_name = trim($_POST['library_name'] ?? 'Genious Library');
    $hero_title = trim($_POST['hero_title'] ?? '');
    $hero_subtitle = trim($_POST['hero_subtitle'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $fine_per_day = floatval($_POST['fine_per_day'] ?? 5.0);
    $loan_days = intval($_POST['loan_days'] ?? 14);

    if (empty($hero_title) || empty($hero_subtitle) || empty($library_name)) {
        $error = "Library Name, Hero Title, and Subtitle cannot be empty.";
    } else {
        $up = $conn->prepare("
            UPDATE site_settings 
            SET library_name = ?, hero_title = ?, hero_subtitle = ?, contact_email = ?, contact_phone = ?, address = ?, fine_per_day = ?, loan_days = ? 
            WHERE id = 1
        ");
        $up->bind_param("ssssssdi", $library_name, $hero_title, $hero_subtitle, $contact_email, $contact_phone, $address, $fine_per_day, $loan_days);

        if ($up->execute()) {
            set_flash('success', 'Library and website settings updated successfully!');
            header("Location: update_index.php");
            exit();
        } else {
            $error = "Failed to update settings: " . $conn->error;
        }
    }
}

$site = get_site_settings($conn);
$is_dashboard = true;
$page_title = "Site Settings";
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
                    <h5 class="fw-bold mb-0 text-dark">System & Landing Page Settings</h5>
                    <small class="text-muted">Customize website banners, circulation policies, and contact information</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="admin_dashboard.php" class="btn btn-sm btn-secondary-custom">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
                </a>
                <a href="index.php" target="_blank" class="btn btn-sm btn-secondary-custom">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Preview Website
                </a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php display_flash(); ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="custom-card p-4">
                        <form method="POST" action="update_index.php">
                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                                <i class="fa-solid fa-paintbrush text-primary me-2"></i> Homepage Hero Banner
                            </h6>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-muted">Library Brand Name *</label>
                                <input type="text" name="library_name" class="form-control form-control-custom" value="<?php echo htmlspecialchars($site['library_name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-muted">Hero Main Headline *</label>
                                <input type="text" name="hero_title" class="form-control form-control-custom" value="<?php echo htmlspecialchars($site['hero_title']); ?>" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold small text-muted">Hero Subtitle / Description *</label>
                                <textarea name="hero_subtitle" rows="3" class="form-control form-control-custom" required><?php echo htmlspecialchars($site['hero_subtitle']); ?></textarea>
                            </div>

                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 mt-4">
                                <i class="fa-solid fa-scale-balanced text-primary me-2"></i> Circulation & Fine Rules
                            </h6>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Default Borrowing Period (Days)</label>
                                    <div class="input-group">
                                        <input type="number" name="loan_days" class="form-control form-control-custom" value="<?php echo intval($site['loan_days'] ?? 14); ?>" min="1" max="90" required>
                                        <span class="input-group-text bg-light">Days</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Late Return Fine Rate (Per Day)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">₹</span>
                                        <input type="number" step="0.50" name="fine_per_day" class="form-control form-control-custom" value="<?php echo floatval($site['fine_per_day'] ?? 5.0); ?>" required>
                                        <span class="input-group-text bg-light">/ day</span>
                                    </div>
                                </div>
                            </div>

                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 mt-4">
                                <i class="fa-solid fa-address-book text-primary me-2"></i> Contact & Address Details
                            </h6>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Official Email Address</label>
                                    <input type="email" name="contact_email" class="form-control form-control-custom" value="<?php echo htmlspecialchars($site['contact_email']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Official Phone Number</label>
                                    <input type="text" name="contact_phone" class="form-control form-control-custom" value="<?php echo htmlspecialchars($site['contact_phone']); ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold small text-muted">Physical Campus Address</label>
                                <input type="text" name="address" class="form-control form-control-custom" value="<?php echo htmlspecialchars($site['address']); ?>" required>
                            </div>

                            <button type="submit" class="btn btn-primary-custom w-100 py-3">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Save System Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
