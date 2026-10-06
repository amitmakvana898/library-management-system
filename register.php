<?php
// register.php - Next-Gen Student Registration Suite
require_once __DIR__ . '/includes/config.php';

if (is_user_logged_in()) {
    header("Location: user_dashboard.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $mobileno = trim($_POST['mobileno'] ?? '');
    $secret_answer = trim($_POST['secret_answer'] ?? '');

    if (empty($username) || empty($email) || empty($password) || empty($mobileno) || empty($secret_answer)) {
        $error = "All fields marked with * are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 4) {
        $error = "Password must be at least 4 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!preg_match('/^[0-9]{10}$/', $mobileno)) {
        $error = "Please enter a valid 10-digit mobile number.";
    } else {
        // Check if email already registered
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "An account with this email address already exists.";
        } else {
            // Handle optional profile image upload
            $profile_img = "uploads/profile_1.jpg";
            if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['profile_img']['tmp_name'];
                $fileName = $_FILES['profile_img']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($fileExtension, $allowedExtensions)) {
                    $newFileName = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $username) . '.' . $fileExtension;
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

            // Secure Hash Password (Modern Cryptographic Standard)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password, mobileno, profile_img, secret_answer, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $insert_stmt->bind_param("ssssss", $username, $email, $hashed_password, $mobileno, $profile_img, $secret_answer);

            if ($insert_stmt->execute()) {
                set_flash('success', 'Registration successful! You can now log in to your account with your credentials.');
                header("Location: userlogin.php");
                exit();
            } else {
                $error = "Registration failed: " . $conn->error;
            }
        }
    }
}

$page_title = "Member Registration";
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container py-5">
    <div class="container">
        <div class="row g-0 auth-split-wrapper mx-auto shadow-2xl rounded-4 overflow-hidden" style="max-width: 960px; background: var(--card-bg); border: 1px solid var(--border-color);">
            
            <!-- Left Banner Side -->
            <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-between p-5 text-white position-relative" style="background: linear-gradient(145deg, #1e1b4b 0%, #312e81 40%, #0f172a 100%); border-right: 1px solid rgba(99, 102, 241, 0.2);">
                <div class="position-relative z-2">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-4" style="background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.25); color: #ffffff; font-size: 0.78rem; font-weight: 700;">
                        <i class="fa-solid fa-sparkles text-cyan"></i>
                        <span>Instant Access Pass</span>
                    </div>
                    <h2 class="display-6 fw-bold mb-3 font-space text-white">Join our Modern Library Community.</h2>
                    <p class="text-white text-opacity-85 leading-relaxed">
                        Borrow physical books, read digital E-Books, track your reading history, and interact with fellow readers seamlessly.
                    </p>
                    
                    <div class="mt-4 pt-3 d-flex flex-column gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-white text-primary fw-bold" style="width: 36px; height: 36px;">
                                <i class="fa-solid fa-book-open"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">Unlimited Catalog Browsing</h6>
                                <small class="text-white-50">Search thousands of titles instantly</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-white text-primary fw-bold" style="width: 36px; height: 36px;">
                                <i class="fa-solid fa-rotate"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">1-Click Online Renewals</h6>
                                <small class="text-white-50">Extend borrow periods in seconds</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-white text-primary fw-bold" style="width: 36px; height: 36px;">
                                <i class="fa-solid fa-id-card"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">Digital Student ID Card</h6>
                                <small class="text-white-50">Auto-generated QR ID pass</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="position-relative z-2 pt-4 border-top border-white border-opacity-10 d-flex align-items-center justify-content-between">
                    <small class="text-white-50">Already registered?</small>
                    <a href="userlogin.php" class="btn btn-sm btn-light fw-bold px-3 rounded-pill">Sign In</a>
                </div>
            </div>

            <!-- Right Form Side -->
            <div class="col-lg-7 p-4 p-md-5 d-flex flex-column justify-content-center">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <div class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary bg-opacity-10 text-primary p-3 mb-2">
                            <i class="fa-solid fa-user-plus fa-xl"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-1">Create Member Account</h3>
                        <p class="text-muted small mb-0">Fill in the details below to activate your digital library pass</p>
                    </div>
                    <a href="userlogin.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="Back to Login">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to Login
                    </a>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-sm mb-4" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php" enctype="multipart/form-data" class="d-flex flex-column gap-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Full Name *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-user"></i></span>
                                <input type="text" name="username" class="form-control form-control-custom border-start-0 ps-0" placeholder="e.g. John Doe" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Email Address *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control form-control-custom border-start-0 ps-0" placeholder="john@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Password *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" name="password" id="regPassword" class="form-control form-control-custom border-start-0 border-end-0 ps-0" placeholder="Min. 4 characters" required>
                                <button type="button" class="input-group-text bg-light text-muted border-start-0 toggle-password-visibility" data-target="#regPassword" title="Show/Hide Password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Confirm Password *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock-check"></i></span>
                                <input type="password" name="confirm_password" id="regConfirmPassword" class="form-control form-control-custom border-start-0 border-end-0 ps-0" placeholder="Re-type password" required>
                                <button type="button" class="input-group-text bg-light text-muted border-start-0 toggle-password-visibility" data-target="#regConfirmPassword" title="Show/Hide Password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Mobile Number (10 Digits) *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-phone"></i></span>
                                <input type="tel" name="mobileno" class="form-control form-control-custom border-start-0 ps-0" placeholder="9876543210" pattern="[0-9]{10}" value="<?php echo htmlspecialchars($_POST['mobileno'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Profile Avatar (Optional)</label>
                            <input type="file" name="profile_img" accept="image/*" class="form-control form-control-custom">
                        </div>
                    </div>

                    <div>
                        <label class="form-label fw-semibold small text-muted">Security Recovery Answer *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light small text-muted"><i class="fa-solid fa-shield-keyhole me-1"></i> Favorite color/pet:</span>
                            <input type="text" name="secret_answer" class="form-control form-control-custom" placeholder="e.g. Blue or Shadow" value="<?php echo htmlspecialchars($_POST['secret_answer'] ?? ''); ?>" required>
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">Used to securely recover your account if you forget your password.</small>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-3 mt-2 shadow-sm fw-bold">
                        <i class="fa-solid fa-user-check me-2"></i> Complete Account Registration
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <p class="small text-muted mb-0">
                        Already have an account? <a href="userlogin.php" class="text-primary fw-bold text-decoration-none">Sign In to Dashboard</a>
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
