<?php
// userlogin.php - Next-Gen Student Auth Suite
require_once __DIR__ . '/includes/config.php';

if (is_user_logged_in()) {
    header("Location: user_dashboard.php");
    exit();
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember_me']);

    if (empty($email) || empty($password)) {
        $error = "Please provide both email and password.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check status
            if (isset($user['status']) && $user['status'] === 'blocked') {
                $error = "Your account is temporarily suspended. Please contact the librarian.";
            } else {
                $is_valid = ($password === $user['password']) || password_verify($password, $user['password']);

                if ($is_valid) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    if ($remember) {
                        setcookie('lms_user_email', $email, time() + (86400 * 30), "/");
                    }
                    
                    set_flash('success', 'Welcome back, ' . htmlspecialchars($user['username']) . '!');
                    header("Location: user_dashboard.php");
                    exit();
                } else {
                    $error = "Invalid password entered. Please try again.";
                }
            }
        } else {
            $error = "No user account registered with this email.";
        }
    }
}

$saved_email = $_COOKIE['lms_user_email'] ?? ($_POST['email'] ?? '');
$page_title = "Member Login";
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container">
    <div class="card border-0 shadow-xl rounded-4 overflow-hidden" style="max-width: 900px; width: 100%; background: var(--card-bg);">
        <div class="row g-0">
            <!-- Left Brand Hero Banner -->
            <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-between p-5 text-white position-relative" style="background: linear-gradient(145deg, #1e1b4b 0%, #312e81 40%, #0f172a 100%); border-right: 1px solid rgba(99, 102, 241, 0.2);">
                <div>
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-4" style="background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.25); color: #ffffff; font-size: 0.78rem; font-weight: 700;">
                        <i class="fa-solid fa-graduation-cap text-cyan"></i> <span>Student Member Portal</span>
                    </div>
                    <h2 class="fw-bold mb-3 font-space text-white"><?php echo htmlspecialchars($site['library_name']); ?></h2>
                    <p class="text-white text-opacity-85 small lh-base">
                        Access over 10,000+ academic textbooks, borrow in 1-click, and track loans from your smart device.
                    </p>
                </div>
                
                <div class="p-3 rounded-3" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.18); backdrop-filter: blur(10px);">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="fa-solid fa-sparkles text-warning"></i>
                        <span class="small fw-bold text-white">Demo Student Account:</span>
                    </div>
                    <code class="text-cyan small d-block fw-bold font-monospace">amit@gmail.com / 111111</code>
                </div>
                
                <small class="text-white text-opacity-60">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site['library_name']); ?></small>
            </div>

            <!-- Right Login Form -->
            <div class="col-lg-7 p-4 p-md-5">
                <div class="mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <h3 class="fw-bold text-main mb-0">Sign In</h3>
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill small">Student</span>
                        </div>
                        <a href="index.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="Back to Homepage">
                            <i class="fa-solid fa-arrow-left me-1"></i> Home
                        </a>
                    </div>
                    <p class="text-muted small">Enter your student credentials to access your library pass</p>
                </div>

                <?php display_flash(); ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Quick 1-Click Demo Fill Button -->
                <button type="button" id="quickFillStudent" class="btn btn-outline-primary btn-sm w-100 mb-3 py-2" style="border-style: dashed;">
                    <i class="fa-solid fa-bolt me-1 text-warning"></i> Quick Auto-Fill Demo Credentials (amit@gmail.com)
                </button>

                <form method="POST" action="userlogin.php" class="d-flex flex-column gap-3">
                    <div>
                        <label class="form-label fw-semibold small text-muted">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-envelope text-muted"></i></span>
                            <input type="email" name="email" id="studentEmailInput" class="form-control" placeholder="student@example.com" value="<?php echo htmlspecialchars($saved_email); ?>" required autofocus>
                        </div>
                    </div>

                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold small text-muted mb-0">Password</label>
                            <a href="forgot_password.php?type=user" class="small text-primary fw-semibold">Forgot Password?</a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock text-muted"></i></span>
                            <input type="password" name="password" id="studentPasswordInput" class="form-control" placeholder="••••••••" required>
                            <button type="button" class="btn btn-outline-secondary toggle-password-visibility" data-target="studentPasswordInput" title="Show/Hide Password">
                                <i class="fa-solid fa-eye text-muted"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember_me" id="rememberMe" checked>
                            <label class="form-check-label small text-muted" for="rememberMe">
                                Remember me for 30 days
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 py-2 mt-1">
                        <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Sign In to Portal
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <p class="small text-muted mb-1">
                        Don't have an account? <a href="register.php" class="text-primary fw-bold">Register as a New Member</a>
                    </p>
                    <p class="small text-muted mb-0">
                        Library staff member? <a href="admin_login.php" class="text-secondary fw-semibold">Librarian Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>