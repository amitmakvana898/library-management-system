<?php
// includes/header.php - Top Universal Navigation Bar (Cyber Emerald & Neo-Mint Edition)
require_once __DIR__ . '/config.php';
$site = get_site_settings($conn);
$page_title = $page_title ?? $site['library_name'];
$current_page = basename($_SERVER['PHP_SELF']);

// Preload notification counts
$admin_pending_req = 0;
$admin_unread_msg = 0;
if (is_admin_logged_in()) {
    $admin = get_logged_admin($conn);
    $req_r = $conn->query("SELECT COUNT(*) as c FROM book_requests WHERE status='pending'");
    $admin_pending_req = $req_r ? intval($req_r->fetch_assoc()['c']) : 0;
    
    $msg_r = $conn->query("SELECT COUNT(*) as c FROM messages WHERE status='unread'");
    $admin_unread_msg = $msg_r ? intval($msg_r->fetch_assoc()['c']) : 0;
} elseif (is_user_logged_in()) {
    $user = get_logged_user($conn);
    $u_id = intval($user['id'] ?? 0);
    $u_iss_r = $conn->query("SELECT COUNT(*) as c FROM book_issued WHERE user_id='$u_id' AND status='issued'");
    $user_issued_count = $u_iss_r ? intval($u_iss_r->fetch_assoc()['c']) : 0;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($_COOKIE['lms_theme'] ?? 'light'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo htmlspecialchars($site['library_name']); ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6.5 Pro Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Bootstrap 5.3 Framework -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom Cyber Emerald & Neo-Mint Design Engine -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo file_exists(__DIR__ . '/../assets/css/style.css') ? filemtime(__DIR__ . '/../assets/css/style.css') : time(); ?>">
    
    <!-- Dynamic Theme Head Injection -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('lms_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>
<body class="<?php echo isset($is_dashboard) && $is_dashboard ? 'dashboard-body' : ''; ?>">

<!-- Universal Modern Top Header Navigation Bar -->
<nav class="navbar navbar-expand-xl navbar-custom sticky-top">
    <div class="container-fluid px-lg-4">
        <!-- Brand Logo -->
        <a class="brand-logo" href="<?php echo is_admin_logged_in() ? 'admin_dashboard.php' : (is_user_logged_in() ? 'user_dashboard.php' : 'index.php'); ?>">
            <div class="brand-icon-wrapper">
                <i class="fa-solid fa-book-bookmark text-primary"></i>
            </div>
            <span class="brand-text"><?php echo htmlspecialchars($site['library_name']); ?></span>
            <?php if (is_admin_logged_in()): ?>
                <span class="badge-role-pill badge-role-admin">ADMIN</span>
            <?php elseif (is_user_logged_in()): ?>
                <span class="badge-role-pill badge-role-student">STUDENT</span>
            <?php endif; ?>
        </a>
        
        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- Center Top Navigation Menus -->
            <ul class="navbar-nav mx-auto mb-2 mb-xl-0 align-items-xl-center nav-pill-container">
                <?php if (is_admin_logged_in()): ?>
                    <!-- ADMIN TOP MENU -->
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom <?php echo $current_page == 'admin_dashboard.php' ? 'active' : ''; ?>" href="admin_dashboard.php">
                            <i class="fa-solid fa-gauge-high"></i> <span>Dashboard</span>
                        </a>
                    </li>

                    <!-- Circulation Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-custom dropdown-toggle <?php echo in_array($current_page, ['issuebooks.php', 'returnbook.php', 'issuedhistory.php', 'book_requests.php', 'scanner.php']) ? 'active' : ''; ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-arrow-right-arrow-left"></i> <span>Circulation</span>
                            <?php if ($admin_pending_req > 0): ?>
                                <span class="nav-counter-pill bg-danger text-white"><?php echo $admin_pending_req; ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu shadow-xl border-0 rounded-4 mt-2">
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="issuebooks.php"><i class="fa-solid fa-arrow-right-from-bracket text-primary"></i> Issue Desk</a></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="returnbook.php"><i class="fa-solid fa-arrow-rotate-left text-info"></i> Return & Fines</a></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="issuedhistory.php"><i class="fa-solid fa-clock-rotate-left text-muted"></i> Circulation Logs</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="scanner.php"><i class="fa-solid fa-qrcode text-warning"></i> QR / Barcode Scanner</a></li>
                            <li>
                                <a class="dropdown-item py-2 d-flex justify-content-between align-items-center" href="book_requests.php">
                                    <span><i class="fa-solid fa-envelope-open-text text-success me-2"></i> Borrow Requests</span>
                                    <?php if ($admin_pending_req > 0): ?>
                                        <span class="badge bg-danger rounded-pill"><?php echo $admin_pending_req; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Catalog Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-custom dropdown-toggle <?php echo in_array($current_page, ['viewbooks.php', 'addbook.php', 'edit_book.php', 'manage_categories.php']) ? 'active' : ''; ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-book"></i> <span>Catalog</span>
                        </a>
                        <ul class="dropdown-menu shadow-xl border-0 rounded-4 mt-2">
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="viewbooks.php"><i class="fa-solid fa-books text-primary"></i> All Books & E-Books</a></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="addbook.php"><i class="fa-solid fa-circle-plus text-success"></i> + Add New Book</a></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="manage_categories.php"><i class="fa-solid fa-layer-group text-warning"></i> Manage Categories</a></li>
                        </ul>
                    </li>

                    <!-- Patrons & Finances Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-custom dropdown-toggle <?php echo in_array($current_page, ['manageusers.php', 'fines_management.php', 'view_reviews.php', 'admin_messages.php']) ? 'active' : ''; ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-users"></i> <span>Patrons</span>
                            <?php if ($admin_unread_msg > 0): ?>
                                <span class="nav-counter-pill bg-warning text-dark"><?php echo $admin_unread_msg; ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu shadow-xl border-0 rounded-4 mt-2">
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="manageusers.php"><i class="fa-solid fa-user-group text-primary"></i> Member Management</a></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="fines_management.php"><i class="fa-solid fa-receipt text-danger"></i> Fines & Overdue Desk</a></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="view_reviews.php"><i class="fa-solid fa-star text-warning"></i> Member Reviews</a></li>
                            <li>
                                <a class="dropdown-item py-2 d-flex justify-content-between align-items-center" href="admin_messages.php">
                                    <span><i class="fa-solid fa-envelope text-info me-2"></i> Messages</span>
                                    <?php if ($admin_unread_msg > 0): ?>
                                        <span class="badge bg-warning text-dark rounded-pill"><?php echo $admin_unread_msg; ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Reports & Settings -->
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-custom dropdown-toggle <?php echo in_array($current_page, ['export_reports.php', 'update_index.php']) ? 'active' : ''; ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-sliders"></i> <span>Reports & Tools</span>
                        </a>
                        <ul class="dropdown-menu shadow-xl border-0 rounded-4 mt-2">
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="export_reports.php"><i class="fa-solid fa-file-excel text-success"></i> 1-Click Excel Reports</a></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="update_index.php"><i class="fa-solid fa-gear text-primary"></i> System Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="index.php" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square text-muted"></i> Public Website</a></li>
                        </ul>
                    </li>

                <?php elseif (is_user_logged_in()): ?>
                    <!-- USER / STUDENT TOP MENU (Sleek 4-Pill Modern Design) -->
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom <?php echo $current_page == 'user_dashboard.php' ? 'active' : ''; ?>" href="user_dashboard.php">
                            <i class="fa-solid fa-house"></i> <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom <?php echo $current_page == 'books.php' ? 'active' : ''; ?>" href="books.php">
                            <i class="fa-solid fa-book-open"></i> <span>Browse Books</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom <?php echo $current_page == 'my_issued_books.php' ? 'active' : ''; ?>" href="my_issued_books.php">
                            <i class="fa-solid fa-book-open-reader"></i> <span>My Loans</span>
                            <?php if (!empty($user_issued_count) && $user_issued_count > 0): ?>
                                <span class="nav-counter-pill bg-primary text-white"><?php echo $user_issued_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <!-- My Library Activity Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-custom dropdown-toggle <?php echo in_array($current_page, ['user_history.php', 'my_requests.php', 'my_wishlist.php', 'scanner.php', 'student_id_card.php', 'user_review.php']) ? 'active' : ''; ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-layer-group"></i> <span>My Library</span>
                        </a>
                        <ul class="dropdown-menu shadow-xl border-0 rounded-4 mt-2">
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="user_history.php"><i class="fa-solid fa-clock-rotate-left text-primary"></i> Borrowing History</a></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="my_requests.php"><i class="fa-solid fa-file-signature text-info"></i> Borrow Requests</a></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="my_wishlist.php"><i class="fa-solid fa-heart text-danger"></i> Saved Wishlist</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="student_id_card.php" target="_blank"><i class="fa-solid fa-id-card text-warning"></i> Digital ID Pass</a></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="scanner.php"><i class="fa-solid fa-qrcode text-success"></i> Book QR Scanner</a></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="user_review.php"><i class="fa-solid fa-star text-warning"></i> Submit Feedback</a></li>
                        </ul>
                    </li>

                <?php else: ?>
                    <!-- GUEST PUBLIC TOP MENU -->
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <i class="fa-solid fa-house"></i> <span>Home</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom <?php echo $current_page == 'books.php' ? 'active' : ''; ?>" href="books.php">
                            <i class="fa-solid fa-book-open"></i> <span>Books Catalog</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom <?php echo $current_page == 'scanner.php' ? 'active' : ''; ?>" href="scanner.php">
                            <i class="fa-solid fa-qrcode text-warning"></i> <span>Scan QR</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom <?php echo $current_page == 'aboutus.php' ? 'active' : ''; ?>" href="aboutus.php">
                            <i class="fa-solid fa-circle-info"></i> <span>About</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom <?php echo $current_page == 'contact.php' ? 'active' : ''; ?>" href="contact.php">
                            <i class="fa-solid fa-envelope"></i> <span>Contact</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <!-- Right Action Deck -->
            <div class="d-flex align-items-center gap-2 mt-3 mt-xl-0 flex-wrap">
                <!-- Search Everything (Ctrl + K) -->
                <button type="button" class="btn btn-secondary-custom py-2 px-3 trigger-command-palette" title="Search Books & Quick Actions (Ctrl + K)">
                    <i class="fa-solid fa-magnifying-glass text-primary me-1"></i> <span class="d-none d-lg-inline small fw-semibold">Search <span class="badge bg-primary bg-opacity-10 text-primary ms-1">Ctrl+K</span></span>
                </button>

                <!-- Dark / Light Theme Toggle -->
                <button type="button" id="theme-toggle" class="theme-toggle btn btn-secondary-custom p-2 px-3" title="Toggle Day/Night Theme">
                    <i class="fa-solid fa-moon text-primary"></i>
                </button>
                
                <?php if (is_admin_logged_in()): ?>
                    <!-- Admin Profile Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-secondary-custom d-flex align-items-center gap-2 py-1 px-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php 
                                $adm_img = (!empty($admin['profile_img']) && file_exists($admin['profile_img'])) ? $admin['profile_img'] : 'admin.png';
                            ?>
                            <img src="<?php echo htmlspecialchars($adm_img); ?>" alt="Admin" class="rounded-circle border" style="width: 32px; height: 32px; object-fit: cover;">
                            <span class="d-none d-md-inline fw-semibold small"><?php echo htmlspecialchars($admin['name'] ?? 'Admin'); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2">
                            <li class="px-3 py-2 border-bottom">
                                <div class="fw-bold small"><?php echo htmlspecialchars($admin['name'] ?? 'Administrator'); ?></div>
                                <div class="text-muted small" style="font-size: 0.75rem;"><?php echo htmlspecialchars($admin['email'] ?? 'admin@library.com'); ?></div>
                            </li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="admin_profile.php"><i class="fa-solid fa-user-gear text-primary"></i> Admin Profile</a></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="edit_admin_profile.php"><i class="fa-solid fa-pen-to-square text-info"></i> Edit Profile & Password</a></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="update_index.php"><i class="fa-solid fa-sliders text-muted"></i> System Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" href="adminlogout.php"><i class="fa-solid fa-right-from-bracket text-danger"></i> Sign Out</a></li>
                        </ul>
                    </div>

                <?php elseif (is_user_logged_in()): ?>
                    <!-- User Profile Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-secondary-custom d-flex align-items-center gap-2 py-1 px-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php 
                                $u_img = (!empty($user['profile_img']) && file_exists($user['profile_img'])) ? $user['profile_img'] : 'uploads/profile_1.jpg';
                                if (!file_exists($u_img)) $u_img = 'admin.png';
                            ?>
                            <img src="<?php echo htmlspecialchars($u_img); ?>" alt="User" class="rounded-circle border" style="width: 32px; height: 32px; object-fit: cover;">
                            <span class="d-none d-md-inline fw-semibold small"><?php echo htmlspecialchars($user['username'] ?? 'Student'); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2">
                            <li class="px-3 py-2 border-bottom">
                                <div class="fw-bold small"><?php echo htmlspecialchars($user['username'] ?? 'Member'); ?></div>
                                <div class="text-muted small" style="font-size: 0.75rem;"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
                            </li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="user_profile.php"><i class="fa-solid fa-user-gear text-primary"></i> My Profile</a></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="edit_user_profile.php"><i class="fa-solid fa-pen-to-square text-info"></i> Edit Account</a></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2" href="student_id_card.php" target="_blank"><i class="fa-solid fa-id-card text-warning"></i> Digital ID Card</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket text-danger"></i> Sign Out</a></li>
                        </ul>
                    </div>

                <?php else: ?>
                    <!-- Guest Login / Register -->
                    <a href="userlogin.php" class="btn btn-secondary-custom">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Login
                    </a>

                    <a href="admin_login.php" class="btn btn-outline-danger py-2 px-3 rounded-3" title="Staff & Librarian Portal">
                        <i class="fa-solid fa-shield-halved"></i> Admin
                    </a>

                    <a href="register.php" class="btn btn-primary-custom">
                        <i class="fa-solid fa-user-plus"></i> Join
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
