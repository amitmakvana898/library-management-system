<?php
// admin_profile.php - View & Edit Admin Profile
require_once __DIR__ . '/includes/config.php';
require_admin();

$admin = get_logged_admin($conn);
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobileno = trim($_POST['mobileno'] ?? '');
    $admin_secret_answer = trim($_POST['admin_secret_answer'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');

    if (empty($name) || empty($email)) {
        $error = "Name and email cannot be empty.";
    } else {
        $profile_img = $admin['profile_img'];

        // Profile Image Upload
        if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['profile_img']['tmp_name'];
            $fileName = $_FILES['profile_img']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = 'admin_' . time() . '.' . $fileExtension;
                $uploadFileDir = 'uploads/profiles/';
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0777, true);
                }
                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $profile_img = $dest_path;
                }
            }
        }

        // If new password provided
        $pass_to_save = !empty($new_password) ? password_hash($new_password, PASSWORD_DEFAULT) : $admin['password'];

        $up = $conn->prepare("UPDATE admins SET name = ?, email = ?, mobileno = ?, profile_img = ?, admin_secret_answer = ?, password = ? WHERE id = ?");
        $up->bind_param("ssssssi", $name, $email, $mobileno, $profile_img, $admin_secret_answer, $pass_to_save, $admin['id']);

        if ($up->execute()) {
            $_SESSION['admin_name'] = $name;
            $_SESSION['admin_email'] = $email;
            set_flash('success', 'Admin profile updated successfully!');
            header("Location: admin_profile.php");
            exit();
        } else {
            $error = "Failed to update profile: " . $conn->error;
        }
    }
}

$admin = get_logged_admin($conn);
$admin_img = (!empty($admin['profile_img']) && file_exists($admin['profile_img'])) ? $admin['profile_img'] : 'admin.png';

$is_dashboard = true;
$page_title = "Admin Profile";
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
                    <h5 class="fw-bold mb-0 text-main font-space">Administrator Profile</h5>
                    <small class="text-muted">Manage your credentials, photo, and security details</small>
                </div>
            </div>
            <a href="admin_dashboard.php" class="btn btn-sm btn-secondary-custom">
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

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="custom-card p-4">
                        <form method="POST" action="admin_profile.php" enctype="multipart/form-data">
                            <div class="d-flex align-items-center gap-4 mb-4 pb-3 border-bottom">
                                <img id="adminAvatarPreview" src="<?php echo htmlspecialchars($admin_img); ?>" alt="Admin Profile" class="rounded-circle border shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                                <div>
                                    <label class="form-label fw-semibold small text-muted mb-1">Change Profile Avatar</label>
                                    <input type="file" name="profile_img" accept="image/*" class="form-control form-control-custom image-preview-input" data-preview-target="adminAvatarPreview">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Admin Full Name *</label>
                                    <input type="text" name="name" class="form-control form-control-custom" value="<?php echo htmlspecialchars($admin['name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Email Address *</label>
                                    <input type="email" name="email" class="form-control form-control-custom" value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Mobile Number</label>
                                    <input type="text" name="mobileno" class="form-control form-control-custom" value="<?php echo htmlspecialchars($admin['mobileno'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Security Question Answer</label>
                                    <input type="text" name="admin_secret_answer" class="form-control form-control-custom" value="<?php echo htmlspecialchars($admin['admin_secret_answer'] ?? ''); ?>" placeholder="Secret recovery answer">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold small text-muted">New Password (Leave blank to keep current)</label>
                                <div class="input-group">
                                    <input type="password" name="new_password" id="adminNewPass" class="form-control form-control-custom" placeholder="••••••••">
                                    <button type="button" class="btn btn-outline-secondary toggle-password-visibility" data-target="#adminNewPass" title="Show/Hide Password">
                                        <i class="fa-solid fa-eye text-muted"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary-custom w-100 py-3">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Update Profile Information
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
