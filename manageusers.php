<?php
// manageusers.php - User / Member Management (Cyber Emerald Edition)
require_once __DIR__ . '/includes/config.php';
require_admin();

$error = "";

// Handle Status Toggle (Active / Block)
if (isset($_GET['toggle_status'])) {
    $uid = intval($_GET['toggle_status']);
    $u_res = $conn->query("SELECT status FROM users WHERE id = $uid LIMIT 1");
    if ($u_res && $u_res->num_rows > 0) {
        $curr = $u_res->fetch_assoc()['status'] ?? 'active';
        $new_st = ($curr === 'active') ? 'blocked' : 'active';
        $conn->query("UPDATE users SET status = '$new_st' WHERE id = $uid");
        set_flash('success', "Member status updated to $new_st.");
    }
    header("Location: manageusers.php");
    exit();
}

// Handle Add User by Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $mobileno = trim($_POST['mobileno'] ?? '');
    $secret_answer = trim($_POST['secret_answer'] ?? 'library');

    if (empty($username) || empty($email) || empty($password)) {
        $error = "Name, email, and password are required.";
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $chk->bind_param("s", $email);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $error = "A user with this email already exists.";
        } else {
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO users (username, email, password, mobileno, profile_img, secret_answer, status) VALUES (?, ?, ?, ?, 'uploads/profile_1.jpg', ?, 'active')");
            $ins->bind_param("sssss", $username, $email, $hashed_pass, $mobileno, $secret_answer);
            if ($ins->execute()) {
                set_flash('success', 'New member "' . htmlspecialchars($username) . '" added successfully.');
                header("Location: manageusers.php");
                exit();
            } else {
                $error = "Failed to add member: " . $conn->error;
            }
        }
    }
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    // Check if user has active issued books
    $chk_act = $conn->query("SELECT id FROM book_issued WHERE user_id = $del_id AND status = 'issued' LIMIT 1");
    if ($chk_act && $chk_act->num_rows > 0) {
        set_flash('danger', 'Cannot delete this member: They currently have unreturned books on loan.');
    } else {
        $conn->query("DELETE FROM users WHERE id = $del_id");
        set_flash('success', 'Member deleted successfully.');
    }
    header("Location: manageusers.php");
    exit();
}

$search_q = trim($_GET['q'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;

$where = " WHERE 1=1";
$params = [];
$types = "";

if (!empty($search_q)) {
    $where .= " AND (u.username LIKE ? OR u.email LIKE ? OR CAST(u.mobileno AS CHAR) LIKE ?)";
    $like = "%{$search_q}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

// 1. Total Count Query
$cnt_stmt = $conn->prepare("SELECT COUNT(*) as total FROM users u" . $where);
if (!empty($params)) {
    $cnt_stmt->bind_param($types, ...$params);
}
$cnt_stmt->execute();
$total_rows = $cnt_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$total_pages = max(1, ceil($total_rows / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// 2. Fetch paginated users
$sql = "
    SELECT u.*, 
    (SELECT COUNT(*) FROM book_issued WHERE user_id = u.id AND status = 'issued') as active_loans_count,
    (SELECT COUNT(*) FROM book_issued WHERE user_id = u.id) as total_loans_count 
    FROM users u 
    $where
    ORDER BY u.id DESC 
    LIMIT ? OFFSET ?
";
$params_page = $params;
$params_page[] = $per_page;
$params_page[] = $offset;
$types_page = $types . "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types_page, ...$params_page);
$stmt->execute();
$users = $stmt->get_result();

function build_user_page_url($page_num) {
    $p = $_GET;
    $p['page'] = $page_num;
    return 'manageusers.php?' . http_build_query($p);
}

$is_dashboard = true;
$page_title = "Member Management";
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/includes/admin_sidebar.php'; ?>

    <div class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="d-flex align-items-center gap-3">
                <a href="admin_dashboard.php" class="btn btn-sm btn-secondary-custom px-3 py-2 rounded-3" title="Back to Dashboard">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back
                </a>
                <div>
                    <h5 class="fw-bold mb-0 text-main font-space">Member / Patron Management</h5>
                    <small class="text-muted">Manage member accounts, borrowing privileges, and digital ID cards</small>
                </div>
            </div>
            
            <button type="button" class="btn btn-primary-custom px-3 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fa-solid fa-user-plus me-1"></i> Add New Member
            </button>
        </div>

        <div class="dashboard-content">
            <?php display_flash(); ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filter & Search Controls -->
            <div class="custom-card p-3 mb-4">
                <form method="GET" action="manageusers.php" class="row g-2 align-items-center">
                    <div class="col-md-9">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" name="q" id="tableSearchInput" class="form-control" placeholder="Search members by name, email, phone number..." value="<?php echo htmlspecialchars($search_q); ?>">
                        </div>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary-custom w-100 py-2">Search</button>
                        <?php if (!empty($search_q)): ?>
                            <a href="manageusers.php" class="btn btn-secondary-custom py-2 px-3" title="Reset Search"><i class="fa-solid fa-rotate-left"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Users Table Card -->
            <div class="custom-table-card">
                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-main mb-0 font-space"><i class="fa-solid fa-users text-primary me-2"></i> Registered Patrons Directory</h6>
                        <small class="text-muted">List of all active, suspended, and enrolled student members</small>
                    </div>
                    <span class="badge bg-primary bg-opacity-20 text-primary border border-primary border-opacity-30 rounded-pill px-3 py-2 fw-bold">
                        <?php echo $users ? $users->num_rows : 0; ?> Members
                    </span>
                </div>
                
                <div class="table-responsive">
                    <table class="custom-table mb-0">
                        <thead>
                            <tr>
                                <th>Avatar</th>
                                <th>Member Details</th>
                                <th>Email Address</th>
                                <th>Mobile</th>
                                <th>Active Loans</th>
                                <th>Total Borrowed</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users && $users->num_rows > 0): ?>
                                <?php while ($u = $users->fetch_assoc()): ?>
                                    <?php 
                                        $u_img = (!empty($u['profile_img']) && file_exists($u['profile_img'])) ? $u['profile_img'] : 'uploads/profile_1.jpg';
                                        if (!file_exists($u_img)) $u_img = 'admin.png';
                                        $is_active = (($u['status'] ?? 'active') === 'active');
                                    ?>
                                    <tr>
                                        <td style="width: 50px;">
                                            <img src="<?php echo htmlspecialchars($u_img); ?>" alt="Avatar" class="rounded-circle border shadow-xs" style="width: 42px; height: 42px; object-fit: cover;">
                                        </td>
                                        <td>
                                            <div class="fw-bold text-main"><?php echo htmlspecialchars($u['username']); ?></div>
                                            <small class="text-muted font-monospace">ID: #LMS-<?php echo str_pad($u['id'], 4, '0', STR_PAD_LEFT); ?></small>
                                        </td>
                                        <td>
                                            <span class="small"><?php echo htmlspecialchars($u['email']); ?></span>
                                        </td>
                                        <td>
                                            <span class="small font-monospace"><?php echo htmlspecialchars($u['mobileno'] ?? '-'); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($u['active_loans_count'] > 0): ?>
                                                <span class="badge-pill-cyan px-2 py-1 fw-bold">
                                                    <?php echo $u['active_loans_count']; ?> active
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border rounded-pill px-2 py-1">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="text-muted small fw-semibold"><?php echo $u['total_loans_count']; ?> books</span>
                                        </td>
                                        <td>
                                            <?php if ($is_active): ?>
                                                <span class="badge-pill-success">
                                                    <i class="fa-solid fa-circle-check me-1"></i> Active
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-pill-danger">
                                                    <i class="fa-solid fa-ban me-1"></i> Suspended
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a href="issuebooks.php?user_id=<?php echo $u['id']; ?>" class="btn btn-sm btn-secondary-custom py-1 px-2" title="Issue Book to Member">
                                                    <i class="fa-solid fa-book-medical text-primary"></i>
                                                </a>
                                                <a href="manageusers.php?toggle_status=<?php echo $u['id']; ?>" class="btn btn-sm btn-secondary-custom py-1 px-2" title="<?php echo $is_active ? 'Suspend Account' : 'Activate Account'; ?>">
                                                    <i class="fa-solid <?php echo $is_active ? 'fa-user-slash text-warning' : 'fa-user-check text-success'; ?>"></i>
                                                </a>
                                                <a href="manageusers.php?delete=<?php echo $u['id']; ?>" class="btn btn-sm btn-secondary-custom py-1 px-2 text-danger" onclick="return confirm('Delete member \'<?php echo htmlspecialchars($u['username']); ?>\'?');" title="Delete Member">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-users-slash fs-1 text-primary d-block mb-3"></i>
                                        <h6 class="fw-bold">No Members Found</h6>
                                        <p class="small mb-0">Try a different search query or add a new member.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Server-Side Pagination Bar -->
                <?php if ($total_pages > 1): ?>
                    <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <small class="text-muted">Page <strong><?php echo $page; ?></strong> of <strong><?php echo $total_pages; ?></strong> (<?php echo $total_rows; ?> Members)</small>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo build_user_page_url($page - 1); ?>"><i class="fa-solid fa-chevron-left"></i></a>
                                </li>
                                <?php for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++): ?>
                                    <li class="page-item <?php echo ($p == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo build_user_page_url($p); ?>"><?php echo $p; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo build_user_page_url($page + 1); ?>"><i class="fa-solid fa-chevron-right"></i></a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-xl rounded-4">
            <form method="POST" action="manageusers.php">
                <input type="hidden" name="action" value="add_user">
                <div class="modal-header border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-main">Register New Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 d-flex flex-column gap-3">
                    <div>
                        <label class="form-label fw-semibold small text-muted">Full Name *</label>
                        <input type="text" name="username" class="form-control" placeholder="e.g. John Doe" required>
                    </div>
                    <div>
                        <label class="form-label fw-semibold small text-muted">Email Address *</label>
                        <input type="email" name="email" class="form-control" placeholder="member@example.com" required>
                    </div>
                    <div>
                        <label class="form-label fw-semibold small text-muted">Mobile Number (10 Digits)</label>
                        <input type="tel" name="mobileno" class="form-control" placeholder="9876543210" pattern="[0-9]{10}">
                    </div>
                    <div>
                        <label class="form-label fw-semibold small text-muted">Initial Password *</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom">Create Member Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
