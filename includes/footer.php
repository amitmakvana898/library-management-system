<?php
// includes/footer.php
$site = get_site_settings($conn);
?>

<!-- Universal Command Palette (Ctrl + K) -->
<div class="command-palette-backdrop" id="command-palette-backdrop">
    <div class="command-palette-modal">
        <div class="command-search-header">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="command-search-input" class="command-search-input" placeholder="Search book, action, or navigate... (ESC to close)" autocomplete="off">
            <span class="command-badge">ESC</span>
        </div>
        <div class="command-results-list" id="command-results-list">
            <a href="books.php" class="command-item">
                <div class="d-flex align-items-center gap-3">
                    <i class="fa-solid fa-book-sparkles text-primary"></i>
                    <span>Browse Full Book Catalog</span>
                </div>
                <span class="command-badge">Catalog</span>
            </a>
            <a href="scanner.php" class="command-item">
                <div class="d-flex align-items-center gap-3">
                    <i class="fa-solid fa-qrcode text-primary"></i>
                    <span>Open Camera QR / Barcode Scanner</span>
                </div>
                <span class="command-badge">Scanner</span>
            </a>
            <?php if (is_user_logged_in()): ?>
                <a href="user_dashboard.php" class="command-item">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fa-solid fa-gauge-high text-primary"></i>
                        <span>Student Dashboard & Active Loans</span>
                    </div>
                    <span class="command-badge">Student</span>
                </a>
                <a href="my_wishlist.php" class="command-item">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fa-solid fa-heart text-danger"></i>
                        <span>My Wishlist & Saved Books</span>
                    </div>
                    <span class="command-badge">Wishlist</span>
                </a>
                <a href="student_id_card.php" class="command-item">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fa-solid fa-id-card text-primary"></i>
                        <span>Digital Student ID Card</span>
                    </div>
                    <span class="command-badge">Card</span>
                </a>
                <a href="logout.php" class="command-item">
                    <div class="d-flex align-items-center gap-3 text-danger">
                        <i class="fa-solid fa-power-off text-danger"></i>
                        <span>Logout Session</span>
                    </div>
                    <span class="command-badge text-danger">Auth</span>
                </a>
            <?php elseif (is_admin_logged_in()): ?>
                <a href="admin_dashboard.php" class="command-item">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fa-solid fa-gauge-high text-primary"></i>
                        <span>Librarian Admin Control Center</span>
                    </div>
                    <span class="command-badge">Admin</span>
                </a>
                <a href="issuebooks.php" class="command-item">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fa-solid fa-handshake text-primary"></i>
                        <span>Issue Book to Member</span>
                    </div>
                    <span class="command-badge">Circulation</span>
                </a>
                <a href="returnbook.php" class="command-item">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fa-solid fa-rotate-left text-primary"></i>
                        <span>Process Book Return & Fines</span>
                    </div>
                    <span class="command-badge">Circulation</span>
                </a>
                <a href="export_reports.php" class="command-item">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fa-solid fa-file-excel text-success"></i>
                        <span>1-Click CSV / Excel Reports Export</span>
                    </div>
                    <span class="command-badge">Reports</span>
                </a>
            <?php else: ?>
                <a href="userlogin.php" class="command-item">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fa-solid fa-user text-primary"></i>
                        <span>Student Login Portal</span>
                    </div>
                    <span class="command-badge">Login</span>
                </a>
                <a href="register.php" class="command-item">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fa-solid fa-user-plus text-primary"></i>
                        <span>New Member Registration</span>
                    </div>
                    <span class="command-badge">Register</span>
                </a>
                <a href="admin_login.php" class="command-item">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fa-solid fa-shield-halved text-primary"></i>
                        <span>Librarian Admin Portal</span>
                    </div>
                    <span class="command-badge">Staff</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!isset($is_dashboard) || !$is_dashboard): ?>
<!-- Clean Luxury Footer -->
<footer class="footer-clean">
    <div class="container">
        <div class="row g-4 mb-5">
            <div class="col-lg-4 col-md-6">
                <a class="brand-logo mb-3 d-inline-block" href="index.php">
                    <i class="fa-solid fa-book-bookmark text-primary"></i>
                    <span><?php echo htmlspecialchars($site['library_name']); ?></span>
                </a>
                <p class="text-muted pe-lg-4 small">
                    A modern, intelligent library management platform offering seamless book discovery, instant reservations, and automated circulation.
                </p>
                <div class="d-flex gap-2 mt-4">
                    <a href="#" class="btn btn-sm btn-secondary-custom rounded-circle"><i class="fa-brands fa-x-twitter"></i></a>
                    <a href="#" class="btn btn-sm btn-secondary-custom rounded-circle"><i class="fa-brands fa-github"></i></a>
                    <a href="#" class="btn btn-sm btn-secondary-custom rounded-circle"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-6">
                <h6 class="fw-bold mb-3 small text-uppercase">Navigation</h6>
                <ul class="list-unstyled d-flex flex-column gap-2">
                    <li><a href="index.php" class="footer-link">Home</a></li>
                    <li><a href="books.php" class="footer-link">Browse Catalog</a></li>
                    <li><a href="scanner.php" class="footer-link">QR Scanner</a></li>
                    <li><a href="aboutus.php" class="footer-link">About Us</a></li>
                </ul>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold mb-3 small text-uppercase">Member Access</h6>
                <ul class="list-unstyled d-flex flex-column gap-2">
                    <li><a href="userlogin.php" class="footer-link">Student Login</a></li>
                    <li><a href="register.php" class="footer-link">Create Account</a></li>
                    <li><a href="admin_login.php" class="footer-link">Librarian Portal</a></li>
                    <li><a href="forgot_password.php" class="footer-link">Forgot Password</a></li>
                </ul>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold mb-3 small text-uppercase">Contact</h6>
                <ul class="list-unstyled d-flex flex-column gap-2 small text-muted">
                    <li><i class="fa-solid fa-location-dot text-primary me-2"></i> <?php echo htmlspecialchars($site['address']); ?></li>
                    <li><i class="fa-solid fa-envelope text-primary me-2"></i> <?php echo htmlspecialchars($site['contact_email']); ?></li>
                    <li><i class="fa-solid fa-phone text-primary me-2"></i> <?php echo htmlspecialchars($site['contact_phone']); ?></li>
                    <li><i class="fa-solid fa-clock text-primary me-2"></i> Mon - Sat: 8:00 AM - 8:00 PM</li>
                </ul>
            </div>
        </div>
        
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 pt-4 border-top small text-muted">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site['library_name']); ?>. All rights reserved.</p>
            <p class="mb-0">Engineered with Clean Core PHP & MySQL.</p>
        </div>
    </div>
</footer>
<?php endif; ?>

<?php
$cur_script = basename($_SERVER['PHP_SELF']);
$dash_url = is_user_logged_in() ? 'user_dashboard.php' : (is_admin_logged_in() ? 'admin_dashboard.php' : 'userlogin.php');
$is_home_active = ($cur_script === 'index.php');
$is_books_active = ($cur_script === 'books.php');
$is_dash_active = in_array($cur_script, ['user_dashboard.php', 'admin_dashboard.php', 'my_issued_books.php']);
$is_wish_active = ($cur_script === 'my_wishlist.php');
?>
<!-- Mobile Native App-Like Bottom Dock -->
<nav class="mobile-bottom-dock d-md-none">
    <a href="index.php" class="mobile-dock-item <?php echo $is_home_active ? 'active' : ''; ?>">
        <i class="fa-solid fa-house"></i>
        <span>Home</span>
    </a>
    <a href="books.php" class="mobile-dock-item <?php echo $is_books_active ? 'active' : ''; ?>">
        <i class="fa-solid fa-book-bookmark"></i>
        <span>Catalog</span>
    </a>
    <a href="<?php echo $dash_url; ?>" class="mobile-dock-item <?php echo $is_dash_active ? 'active' : ''; ?>">
        <i class="fa-solid fa-gauge-high"></i>
        <span>Portal</span>
    </a>
    <?php if (is_user_logged_in()): ?>
        <a href="my_wishlist.php" class="mobile-dock-item <?php echo $is_wish_active ? 'active' : ''; ?>">
            <i class="fa-solid fa-heart"></i>
            <span>Wishlist</span>
        </a>
    <?php else: ?>
        <a href="scanner.php" class="mobile-dock-item <?php echo ($cur_script === 'scanner.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-qrcode"></i>
            <span>Scan QR</span>
        </a>
    <?php endif; ?>
    <a href="#" class="mobile-dock-item trigger-command-palette" data-trigger="command-palette">
        <i class="fa-solid fa-magnifying-glass"></i>
        <span>Search</span>
    </a>
</nav>

<!-- Floating Librarian Help Button -->
<button class="floating-help-btn shadow-lg" data-bs-toggle="modal" data-bs-target="#quickHelpModal" title="Direct Librarian Support & Queries">
    <i class="fa-solid fa-headset"></i>
    <span class="d-none d-md-inline ms-1">Ask Librarian</span>
</button>

<!-- Quick Help Modal -->
<div class="modal fade" id="quickHelpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header px-4 py-3 border-bottom" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(56, 189, 248, 0.1) 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-headset fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-main mb-0 font-space">Librarian Help Desk</h5>
                        <small class="text-muted">Instant direct inquiry & support ticket</small>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="quickHelpForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">What do you need help with?</label>
                        <select name="topic" class="form-select">
                            <option value="Book Not Found / Request">📖 Book Not Found / Request Book</option>
                            <option value="Loan Renewal Query">🔄 Loan Renewal Assistance</option>
                            <option value="Fine & Penalty Query">💳 Fine Settlement & Dues</option>
                            <option value="E-Book Access Problem">📱 E-Book / PDF Access Issue</option>
                            <option value="General Inquiry">💬 General Question</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">Your Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Full name" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">Your Email</label>
                            <input type="email" name="email" class="form-control" placeholder="name@college.edu" value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Message / Question</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="Describe your question or issue in detail..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary-custom w-100 py-2 fw-bold shadow-md">
                        <i class="fa-solid fa-paper-plane me-1"></i> Send to Librarian Desk
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom Luxury Engine JS -->
<script src="assets/js/main.js?v=<?php echo file_exists(__DIR__ . '/../assets/js/main.js') ? filemtime(__DIR__ . '/../assets/js/main.js') : time(); ?>"></script>
</body>
</html>
