<?php
// forgot_password.php - Next-Gen Self-Service Account Recovery
require_once __DIR__ . '/includes/config.php';

$type = isset($_GET['type']) && $_GET['type'] === 'admin' ? 'admin' : 'user';
$error = "";

// Allow explicit step navigation or session reset
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    unset($_SESSION['reset_account'], $_SESSION['reset_verified']);
    header("Location: forgot_password.php?type=" . urlencode($type));
    exit();
}

$step = 1; // Default
if (isset($_SESSION['reset_account'])) {
    if (isset($_SESSION['reset_verified']) && $_SESSION['reset_verified'] === true) {
        $step = isset($_GET['step']) && $_GET['step'] === '2' ? 2 : 3;
    } else {
        $step = 2;
    }
}
if (isset($_GET['step']) && $_GET['step'] === '1') {
    $step = 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'verify_email') {
        $email = trim($_POST['email'] ?? '');
        $type = $_POST['account_type'] ?? 'user';
        
        $table = ($type === 'admin') ? 'admins' : 'users';
        $stmt = $conn->prepare("SELECT * FROM {$table} WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $account = $res->fetch_assoc();
            $_SESSION['reset_account'] = [
                'id' => $account['id'],
                'email' => $account['email'],
                'type' => $type
            ];
            unset($_SESSION['reset_verified']);
            $step = 2;
        } else {
            $error = "No registered " . ($type === 'admin' ? 'admin' : 'member') . " account found with that email.";
            $step = 1;
        }
    } elseif ($action === 'verify_answer') {
        if (!isset($_SESSION['reset_account'])) {
            header("Location: forgot_password.php?reset=1");
            exit();
        }
        $answer = trim($_POST['secret_answer'] ?? '');
        $acc = $_SESSION['reset_account'];
        $table = ($acc['type'] === 'admin') ? 'admins' : 'users';
        $col = ($acc['type'] === 'admin') ? 'admin_secret_answer' : 'secret_answer';

        $stmt = $conn->prepare("SELECT id FROM {$table} WHERE id = ? AND LOWER({$col}) = LOWER(?) LIMIT 1");
        $stmt->bind_param("is", $acc['id'], $answer);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $_SESSION['reset_verified'] = true;
            $step = 3;
        } else {
            $error = "Incorrect security answer provided. Please verify.";
            $step = 2;
        }
    } elseif ($action === 'update_password') {
        if (!isset($_SESSION['reset_account']) || !isset($_SESSION['reset_verified'])) {
            header("Location: forgot_password.php?reset=1");
            exit();
        }
        $new_pass = trim($_POST['new_password'] ?? '');
        $confirm_pass = trim($_POST['confirm_password'] ?? '');

        if (strlen($new_pass) < 4) {
            $error = "Password must be at least 4 characters long.";
            $step = 3;
        } elseif ($new_pass !== $confirm_pass) {
            $error = "Passwords do not match.";
            $step = 3;
        } else {
            $acc = $_SESSION['reset_account'];
            $table = ($acc['type'] === 'admin') ? 'admins' : 'users';

            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE {$table} SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_pass, $acc['id']);
            
            if ($stmt->execute()) {
                unset($_SESSION['reset_account'], $_SESSION['reset_verified']);
                $redirect = ($acc['type'] === 'admin') ? 'admin_login.php' : 'userlogin.php';
                set_flash('success', 'Your password has been reset securely! Please login with your new password.');
                header("Location: {$redirect}");
                exit();
            } else {
                $error = "Failed to update password: " . $conn->error;
                $step = 3;
            }
        }
    }
}

$page_title = "Password Recovery";
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-container py-5">
    <div class="container">
        <div class="row g-0 auth-split-wrapper mx-auto shadow-2xl rounded-4 overflow-hidden" style="max-width: 900px; background: var(--card-bg); border: 1px solid var(--border-color);">
            
            <!-- Left Info Panel -->
            <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-between p-5 text-white position-relative" style="background: linear-gradient(145deg, #d97706 0%, #b45309 100%);">
                <div class="position-relative z-2">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-4" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                        <i class="fa-solid fa-shield-halved text-white"></i>
                        <span class="small fw-semibold">Self-Service Recovery</span>
                    </div>
                    <h2 class="display-6 fw-bold mb-3">Reset Your Access Key.</h2>
                    <p class="text-white-50 leading-relaxed">
                        Follow the 3-step secure verification process to set a new password for your student or administrative account.
                    </p>

                    <div class="mt-4 pt-2 d-flex flex-column gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-circle <?php echo $step >= 1 ? 'bg-white text-warning fw-bold' : 'bg-white bg-opacity-20 text-white'; ?>" style="width: 32px; height: 32px;">
                                1
                            </div>
                            <span class="<?php echo $step == 1 ? 'fw-bold text-white' : 'text-white-50'; ?>">Identify Account Email</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-circle <?php echo $step >= 2 ? 'bg-white text-warning fw-bold' : 'bg-white bg-opacity-20 text-white'; ?>" style="width: 32px; height: 32px;">
                                2
                            </div>
                            <span class="<?php echo $step == 2 ? 'fw-bold text-white' : 'text-white-50'; ?>">Verify Security Answer</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center justify-content-center rounded-circle <?php echo $step >= 3 ? 'bg-white text-warning fw-bold' : 'bg-white bg-opacity-20 text-white'; ?>" style="width: 32px; height: 32px;">
                                3
                            </div>
                            <span class="<?php echo $step == 3 ? 'fw-bold text-white' : 'text-white-50'; ?>">Create New Password</span>
                        </div>
                    </div>
                </div>

                <div class="position-relative z-2 pt-4 border-top border-white border-opacity-10 d-flex align-items-center justify-content-between">
                    <small class="text-white-50">Remembered password?</small>
                    <a href="<?php echo $type === 'admin' ? 'admin_login.php' : 'userlogin.php'; ?>" class="btn btn-sm btn-light fw-bold px-3 rounded-pill">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to Login
                    </a>
                </div>
            </div>

            <!-- Right Form Panel -->
            <div class="col-lg-7 p-4 p-md-5 d-flex flex-column justify-content-center">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <div class="d-inline-flex align-items-center justify-content-center rounded-3 bg-warning bg-opacity-10 text-warning p-3 mb-2">
                            <i class="fa-solid fa-key-skeleton fa-xl"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-1">Account Recovery</h3>
                        <p class="text-muted small mb-0">Step <?php echo $step; ?> of 3: <?php echo $step === 1 ? 'Enter your registered email' : ($step === 2 ? 'Security verification challenge' : 'Set a new secure password'); ?></p>
                    </div>

                    <!-- Direct Back to Login Button -->
                    <a href="<?php echo $type === 'admin' ? 'admin_login.php' : 'userlogin.php'; ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="Cancel and return to login">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to Login
                    </a>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0 rounded-3 shadow-sm mb-4" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($step === 1): ?>
                    <!-- STEP 1: Enter Email -->
                    <form method="POST" action="forgot_password.php" class="d-flex flex-column gap-3">
                        <input type="hidden" name="action" value="verify_email">
                        
                        <div>
                            <label class="form-label fw-semibold small text-muted">Account Type</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="account_type" id="typeUser" value="user" <?php echo $type !== 'admin' ? 'checked' : ''; ?>>
                                    <label class="form-check-label small fw-semibold" for="typeUser">Student / Member</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="account_type" id="typeAdmin" value="admin" <?php echo $type === 'admin' ? 'checked' : ''; ?>>
                                    <label class="form-check-label small fw-semibold" for="typeAdmin">Staff / Admin</label>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="form-label fw-semibold small text-muted">Registered Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control form-control-custom border-start-0 ps-0" placeholder="name@example.com" required autofocus>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-2">
                            <a href="<?php echo $type === 'admin' ? 'admin_login.php' : 'userlogin.php'; ?>" class="btn btn-outline-secondary px-4 py-3">
                                <i class="fa-solid fa-arrow-left me-1"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary-custom flex-grow-1 py-3 fw-bold">
                                Next: Security Challenge <i class="fa-solid fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </form>

                <?php elseif ($step === 2): ?>
                    <!-- STEP 2: Security Question -->
                    <form method="POST" action="forgot_password.php" class="d-flex flex-column gap-3">
                        <input type="hidden" name="action" value="verify_answer">

                        <div class="p-3 bg-light rounded-3 border">
                            <div class="small text-muted mb-1"><i class="fa-solid fa-user-check me-1"></i> Identified Account:</div>
                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($_SESSION['reset_account']['email'] ?? ''); ?> (<?php echo ucfirst($_SESSION['reset_account']['type'] ?? 'user'); ?>)</div>
                        </div>

                        <div>
                            <label class="form-label fw-semibold small text-muted">Security Question Challenge</label>
                            <div class="text-secondary small mb-2"><i class="fa-solid fa-circle-question text-warning me-1"></i> What is your favorite color or pet's name?</div>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-shield-keyhole"></i></span>
                                <input type="text" name="secret_answer" class="form-control form-control-custom border-start-0 ps-0" placeholder="Enter your secret answer" required autofocus>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-2">
                            <a href="forgot_password.php?reset=1" class="btn btn-outline-secondary px-4 py-3">
                                <i class="fa-solid fa-arrow-left me-1"></i> Back to Step 1
                            </a>
                            <button type="submit" class="btn btn-primary-custom flex-grow-1 py-3 fw-bold">
                                <i class="fa-solid fa-check-circle me-2"></i> Verify Answer
                            </button>
                        </div>
                    </form>

                <?php elseif ($step === 3): ?>
                    <!-- STEP 3: Enter New Password -->
                    <form method="POST" action="forgot_password.php" class="d-flex flex-column gap-3">
                        <input type="hidden" name="action" value="update_password">

                        <div class="p-3 bg-light rounded-3 border">
                            <div class="small text-success mb-0"><i class="fa-solid fa-shield-check me-1"></i> Verified Account: <strong><?php echo htmlspecialchars($_SESSION['reset_account']['email'] ?? ''); ?></strong></div>
                        </div>

                        <div>
                            <label class="form-label fw-semibold small text-muted">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" name="new_password" id="newPass" class="form-control form-control-custom border-start-0 border-end-0 ps-0" placeholder="Minimum 4 characters" required autofocus>
                                <button type="button" class="input-group-text bg-light text-muted border-start-0 toggle-password-visibility" data-target="#newPass" title="Toggle Password Visibility">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="form-label fw-semibold small text-muted">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock-check"></i></span>
                                <input type="password" name="confirm_password" id="confirmPass" class="form-control form-control-custom border-start-0 border-end-0 ps-0" placeholder="Repeat new password" required>
                                <button type="button" class="input-group-text bg-light text-muted border-start-0 toggle-password-visibility" data-target="#confirmPass" title="Toggle Password Visibility">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-2">
                            <a href="forgot_password.php?step=2" class="btn btn-outline-secondary px-4 py-3">
                                <i class="fa-solid fa-arrow-left me-1"></i> Back to Step 2
                            </a>
                            <button type="submit" class="btn btn-success flex-grow-1 py-3 fw-bold text-white shadow-sm">
                                <i class="fa-solid fa-save me-2"></i> Save New Password
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="text-center mt-4 pt-3 border-top">
                    <p class="small text-muted mb-0">
                        <a href="userlogin.php" class="text-decoration-none me-3"><i class="fa-solid fa-user me-1"></i> Student Login</a>
                        <a href="admin_login.php" class="text-decoration-none text-secondary me-3"><i class="fa-solid fa-shield-halved me-1"></i> Admin Portal</a>
                        <a href="index.php" class="text-decoration-none text-muted"><i class="fa-solid fa-house me-1"></i> Home</a>
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
