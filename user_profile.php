<?php
// user_profile.php - View & Edit Student Profile with Password Confirmation & Size Constraints
require_once __DIR__ . '/includes/config.php';
require_user();

$user = get_logged_user($conn);
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobileno = trim($_POST['mobileno'] ?? '');
    $secret_answer = trim($_POST['secret_answer'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($username) || empty($email)) {
        $error = "Name and email cannot be empty.";
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = "New Password and Confirm Password do not match.";
    } elseif (!empty($new_password) && strlen($new_password) < 4) {
        $error = "Password must be at least 4 characters long.";
    } else {
        $profile_img = $user['profile_img'];

        if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['profile_img']['tmp_name'];
            $fileSize = $_FILES['profile_img']['size'];
            $fileName = $_FILES['profile_img']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            // 5MB max size
            if ($fileSize > 5 * 1024 * 1024) {
                $error = "Profile image must be smaller than 5MB.";
            } elseif (!in_array($fileExtension, $allowedExtensions)) {
                $error = "Only JPG, PNG, and WebP images are permitted.";
            } else {
                $newFileName = 'user_' . time() . '_' . $user['id'] . '.' . $fileExtension;
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

        if (empty($error)) {
            $pass_to_save = !empty($new_password) ? password_hash($new_password, PASSWORD_DEFAULT) : $user['password'];

            $up = $conn->prepare("UPDATE users SET username = ?, email = ?, mobileno = ?, profile_img = ?, secret_answer = ?, password = ? WHERE id = ?");
            $up->bind_param("ssssssi", $username, $email, $mobileno, $profile_img, $secret_answer, $pass_to_save, $user['id']);

            if ($up->execute()) {
                $_SESSION['username'] = $username;
                $_SESSION['user_email'] = $email;
                set_flash('success', 'Profile details updated successfully!');
                header("Location: user_profile.php");
                exit();
            } else {
                $error = "Failed to update profile: " . $conn->error;
            }
        }
    }
}

$user = get_logged_user($conn);
$u_img = (!empty($user['profile_img']) && file_exists($user['profile_img'])) ? $user['profile_img'] : 'uploads/profile_1.jpg';
if (!file_exists($u_img)) $u_img = 'admin.png';

$is_dashboard = true;
$page_title = "My Profile";
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
                    <h5 class="fw-bold mb-0 text-main font-space">My Profile & Account Settings</h5>
                    <small class="text-muted">Manage your personal information, photo, and security</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="user_dashboard.php" class="btn btn-sm btn-secondary-custom">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
                </a>
                <a href="student_id_card.php" target="_blank" class="btn btn-sm btn-warning text-dark fw-bold">
                    <i class="fa-solid fa-id-card me-1"></i> View Library Card
                </a>
            </div>
        </div>

        <div class="dashboard-content">
            <?php display_flash(); ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="custom-card p-4">
                        <form method="POST" action="user_profile.php" enctype="multipart/form-data">
                            <div class="d-flex align-items-center gap-4 mb-4 pb-3 border-bottom">
                                <img id="userAvatarPreview" src="<?php echo htmlspecialchars($u_img); ?>" alt="Avatar" class="rounded-circle border shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                                <div>
                                    <label class="form-label fw-semibold small text-muted mb-1">Change Profile Photo (Max 5MB)</label>
                                    <input type="file" name="profile_img" accept="image/*" class="form-control form-control-custom image-preview-input" data-preview-target="userAvatarPreview">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Full Name *</label>
                                    <input type="text" name="username" class="form-control form-control-custom" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Email Address *</label>
                                    <input type="email" name="email" class="form-control form-control-custom" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Mobile Number</label>
                                    <input type="tel" name="mobileno" class="form-control form-control-custom" pattern="[0-9]{10}" value="<?php echo htmlspecialchars($user['mobileno'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Security Question Answer</label>
                                    <input type="text" name="secret_answer" class="form-control form-control-custom" value="<?php echo htmlspecialchars($user['secret_answer'] ?? ''); ?>" placeholder="Secret recovery answer">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">New Password (Optional)</label>
                                    <div class="input-group">
                                        <input type="password" name="new_password" id="userNewPass" class="form-control form-control-custom" placeholder="Leave blank to keep current">
                                        <button type="button" class="btn btn-outline-secondary toggle-password-visibility" data-target="#userNewPass" title="Show/Hide Password">
                                            <i class="fa-solid fa-eye text-muted"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-muted">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" name="confirm_password" id="userConfirmPass" class="form-control form-control-custom" placeholder="Re-type new password">
                                        <button type="button" class="btn btn-outline-secondary toggle-password-visibility" data-target="#userConfirmPass" title="Show/Hide Password">
                                            <i class="fa-solid fa-eye text-muted"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary-custom w-100 py-3">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
