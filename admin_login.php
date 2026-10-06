<?php
// admin_login.php - Next-Gen Librarian Auth Suite
require_once __DIR__ . '/includes/config.php';

if (is_admin_logged_in()) {
    header("Location: admin_dashboard.php");
    exit();
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            $is_valid = ($password === $admin['password']) || password_verify($password, $admin['password']);

            if ($is_valid) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                set_flash('success', 'Welcome back, ' . htmlspecialchars($admin['name']) . '!');
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error = "Invalid password provided. Please try again.";
            }
        } else {
            $error = "No admin account found with this email.";
        }
    }
}

$page_title = "Admin Login";
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="card border-0 shadow-xl rounded-4 overflow-hidden" style="max-width: 900px; width: 100%; background: var(--card-bg);">
        <div class="row g-0">
            <!-- Left Brand Hero Banner -->
            <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-between p-5 text-white position-relative" style="background: linear-gradient(145deg, #090d16 0%, #1e1b4b 50%, #312e81 100%); border-right: 1px solid rgba(99, 102, 241, 0.2);">
                <div>
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-4" style="background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.25); color: #ffffff; font-size: 0.78rem; font-weight: 700;">
                        <i class="fa-solid fa-shield-halved text-warning"></i> <span>Librarian Control Center</span>
                    </div>
                    <h2 class="fw-bold mb-3 font-space text-white"><?php echo htmlspecialchars($site['library_name']); ?></h2>
                    <p class="text-white text-opacity-85 small lh-base">
                        Secure administrative portal for real-time inventory management, circulation logs, and member oversight.
                    </p>
                </div>
                
                <div class="p-3 rounded-3" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.18); backdrop-filter: blur(10px);">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="fa-solid fa-key text-warning"></i>
                        <span class="small fw-bold text-white">Demo Librarian Access:</span>
                    </div>
                    <code class="text-cyan small d-block fw-bold font-monospace">admin@gmail.com / admin123</code>
                </div>
                
                <small class="text-white text-opacity-60">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site['library_name']); ?></small>
            </div>

            <!-- Right Login Form -->
            <div class="col-lg-7 p-4 p-md-5">
                <div class="mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <h3 class="fw-bold text-main mb-0">Librarian Access</h3>
                            <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-1 rounded-pill small">Staff Only</span>
                        </div>
                        <a href="index.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="Back to Homepage">
                            <i class="fa-solid fa-arrow-left me-1"></i> Home
                        </a>
                    </div>
                    <p class="text-muted small">Enter your administrative credentials to manage circulation</p>
                </div>

                <?php display_flash(); ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Quick 1-Click Demo Fill Button -->
                <button type="button" id="quickFillAdmin" class="btn btn-outline-dark btn-sm w-100 mb-3 py-2" style="border-style: dashed;">
                    <i class="fa-solid fa-bolt me-1 text-warning"></i> Quick Auto-Fill Admin Credentials (admin@gmail.com)
                </button>

                <form method="POST" action="admin_login.php" class="d-flex flex-column gap-3">
                    <div>
                        <label class="form-label fw-semibold small text-muted">Admin Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-envelope text-muted"></i></span>
                            <input type="email" name="email" id="adminEmailInput" class="form-control" placeholder="admin@gmail.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autofocus>
                        </div>
                    </div>

                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold small text-muted mb-0">Password</label>
                            <a href="forgot_password.php?type=admin" class="small text-primary fw-semibold">Forgot Password?</a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock text-muted"></i></span>
                            <input type="password" name="password" id="adminPasswordInput" class="form-control" placeholder="••••••••" required>
                            <button type="button" class="btn btn-outline-secondary toggle-password-visibility" data-target="adminPasswordInput" title="Show/Hide Password">
                                <i class="fa-solid fa-eye text-muted"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-2 mt-2">
                        <i class="fa-solid fa-shield-halved me-1"></i> Sign In to Control Panel
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <p class="small text-muted mb-1">
                        Student / Reader member? <a href="userlogin.php" class="text-primary fw-bold">Student Portal Login</a>
                    </p>
                    <p class="small text-muted mb-0">
                        <a href="index.php" class="text-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back to Homepage</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
