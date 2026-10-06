<?php
// index.php - Clean Luxury Next-Gen Smart Library Experience
require_once __DIR__ . '/includes/config.php';
$site = get_site_settings($conn);

// Fetch quick statistics
$books_total = $conn->query("SELECT COUNT(*) as c FROM books")->fetch_assoc()['c'] ?? 0;
$books_avail = $conn->query("SELECT COUNT(*) as c FROM books WHERE status='Available'")->fetch_assoc()['c'] ?? 0;
$users_total = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'] ?? 0;
$issued_total = $conn->query("SELECT COUNT(*) as c FROM book_issued")->fetch_assoc()['c'] ?? 0;

// Fetch top categories
$categories_res = $conn->query("SELECT name, COUNT(b.id) as book_count FROM categories c LEFT JOIN books b ON c.name = b.category GROUP BY c.id ORDER BY book_count DESC LIMIT 6");

// Fetch featured books
$featured_books = $conn->query("SELECT * FROM books ORDER BY id DESC LIMIT 8");

// Fetch approved reviews
$reviews_res = $conn->query("SELECT r.*, u.username, u.profile_img FROM reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.is_approved = 1 ORDER BY r.id DESC LIMIT 4");

$page_title = "Smart Library Platform";
require_once __DIR__ . '/includes/header.php';
?>

<div class="ambient-glow-wrapper"></div>

<!-- 🚀 HARD LUXURY 3D HERO SECTION WITH COSMIC PARTICLE CANVAS -->
<section class="hero-section-clean position-relative overflow-hidden">
    <!-- Interactive Cosmic Neural Particle Canvas -->
    <canvas id="heroParticleCanvas" class="hero-particle-canvas"></canvas>

    <div class="container position-relative" style="z-index: 2;">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-primary bg-opacity-10 border border-primary border-opacity-25 mb-3 anim-fade-up">
                    <span class="badge bg-primary text-white rounded-pill px-2 py-1" style="font-size: 0.7rem;">ULTRA EDITION</span>
                    <span class="small fw-bold text-primary"><i class="fa-solid fa-sparkles me-1 text-cyan"></i> Next-Gen Smart Library Platform</span>
                </div>
                
                <h1 class="hero-title-clean anim-fade-up stagger-1">
                    Discover <span id="heroRotatingKeyword" class="animated-gradient-text">Infinite Knowledge</span><br>
                    <span class="text-main fw-bolder"><?php echo htmlspecialchars($site['library_name']); ?></span>
                </h1>
                
                <p class="fs-5 text-muted mb-4 pe-lg-4 lh-base anim-fade-up stagger-2">
                    Access thousands of academic books, reserve physical copies in one click, and read digital PDF resources with an effortless high-tech experience.
                </p>
                
                <!-- Clean Search Deck with Voice Search -->
                <form action="books.php" method="GET" class="search-deck-clean mb-4 anim-fade-up stagger-3" style="max-width: 620px;">
                    <i class="fa-solid fa-magnifying-glass text-primary fs-5 ms-3"></i>
                    <input type="text" name="q" id="heroSearchInput" class="search-input-clean" placeholder="Search by book title, author, category, or ISBN..." required autocomplete="off">
                    
                    <!-- Voice Search Button -->
                    <button type="button" class="voice-search-btn" data-target-input="heroSearchInput" title="Voice Search (Speak)">
                        <i class="fa-solid fa-microphone"></i>
                    </button>
                    
                    <button type="submit" class="btn btn-primary-custom px-4 py-2">
                        <span>Search</span>
                    </button>
                </form>

                <!-- Trending Topics Tags -->
                <div class="d-flex flex-wrap align-items-center gap-2 mb-4 anim-fade-up stagger-4">
                    <span class="small text-muted fw-bold">Popular Categories:</span>
                    <a href="books.php?category=computer+science" class="btn-pill-tag">Computer Science</a>
                    <a href="books.php?category=science" class="btn-pill-tag">Science</a>
                    <a href="books.php?category=History" class="btn-pill-tag">History</a>
                    <a href="books.php?category=Story" class="btn-pill-tag">Literature</a>
                </div>

                <div class="d-flex flex-wrap gap-3 align-items-center pt-2 anim-fade-up stagger-5">
                    <a href="books.php" class="btn btn-primary-custom px-4 py-2 fw-bold shadow-lg">
                        <i class="fa-solid fa-book-open me-1"></i> Browse Entire Catalog
                    </a>
                    <a href="scanner.php" class="btn btn-secondary-custom px-4 py-2">
                        <i class="fa-solid fa-qrcode me-1"></i> Scan QR / Barcode
                    </a>
                    <a href="admin_login.php" class="btn btn-outline-danger px-3 py-2 rounded-3">
                        <i class="fa-solid fa-shield-halved me-1"></i> Librarian Portal
                    </a>
                </div>
            </div>
            
            <!-- 3D HOLOGRAM HERO SHOWCASE WITH ORBITING GLASS BADGES -->
            <div class="col-lg-5 text-center">
                <div class="hero-hologram-card tilt-3d">
                    <div class="hologram-glow-ring"></div>
                    
                    <!-- Floating Orbit Badge 1 (Top Left) -->
                    <div class="floating-glass-badge badge-top-left">
                        <div class="stat-icon-wrap mb-0" style="width: 38px; height: 38px; background: rgba(6, 182, 212, 0.2); color: #06b6d4;">
                            <i class="fa-solid fa-qrcode"></i>
                        </div>
                        <div class="text-start">
                            <div class="small fw-bold">Live QR Scanner</div>
                            <span class="text-muted" style="font-size: 0.72rem;">Hardware Camera Instant Issue</span>
                        </div>
                    </div>

                    <!-- Floating Orbit Badge 2 (Mid Right) -->
                    <div class="floating-glass-badge badge-mid-right">
                        <div class="stat-icon-wrap mb-0" style="width: 38px; height: 38px; background: rgba(239, 68, 68, 0.2); color: #f87171;">
                            <i class="fa-solid fa-file-pdf"></i>
                        </div>
                        <div class="text-start">
                            <div class="small fw-bold">Digital E-Reader</div>
                            <span class="text-muted" style="font-size: 0.72rem;">24/7 Interactive PDF Reader</span>
                        </div>
                    </div>

                    <!-- Floating Orbit Badge 3 (Bottom Right) -->
                    <div class="floating-glass-badge badge-bottom-right">
                        <div class="stat-icon-wrap mb-0" style="width: 38px; height: 38px; background: rgba(16, 185, 129, 0.2); color: #34d399;">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <div class="text-start">
                            <div class="small fw-bold text-success"><?php echo $books_avail; ?> Books Ready</div>
                            <span class="text-muted" style="font-size: 0.72rem;">1-Click Instant Borrow</span>
                        </div>
                    </div>

                    <div class="hero-main-img-wrap">
                        <img src="img2.jpg" alt="Smart Library Experience" class="img-fluid" style="max-height: 440px; width: 100%; object-fit: cover;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CLEAN STATS GRID WITH LIVE COUNT-UP METERS -->
<section class="py-4 border-top border-bottom" style="background-color: var(--card-bg) !important;">
    <div class="container">
        <div class="row g-4">
            <div class="col-6 col-lg-3">
                <div class="stat-card-clean tilt-3d">
                    <div class="stat-icon-wrap">
                        <i class="fa-solid fa-books"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0 text-main" data-counter="true"><?php echo $books_total; ?>+</h3>
                        <span class="text-muted small fw-semibold">Catalog Books</span>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-lg-3">
                <div class="stat-card-clean tilt-3d">
                    <div class="stat-icon-wrap" style="background: var(--success-subtle); color: var(--success);">
                        <i class="fa-solid fa-book-circle-check"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0 text-success" data-counter="true"><?php echo $books_avail; ?>+</h3>
                        <span class="text-muted small fw-semibold">Available Copies</span>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-lg-3">
                <div class="stat-card-clean tilt-3d">
                    <div class="stat-icon-wrap" style="background: var(--warning-subtle); color: var(--warning);">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0 text-warning" data-counter="true"><?php echo $users_total; ?>+</h3>
                        <span class="text-muted small fw-semibold">Active Members</span>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-lg-3">
                <div class="stat-card-clean tilt-3d">
                    <div class="stat-icon-wrap" style="background: var(--accent-subtle); color: var(--accent);">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0 text-main" data-counter="true"><?php echo $issued_total; ?>+</h3>
                        <span class="text-muted small fw-semibold">Circulations Done</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURED BOOKS SHOWCASE WITH 3D TILT CARDS -->
<section class="py-5">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3">
            <div>
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill fw-bold mb-2">CURATED CATALOG</span>
                <h2 class="fw-bold text-main mb-0 font-space">Featured Books & Academic Resources</h2>
            </div>
            <a href="books.php" class="btn btn-secondary-custom rounded-pill px-4">
                Explore Full Directory (<?php echo $books_total; ?>) <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>

        <!-- Books Grid -->
        <div class="row g-4" id="featuredBooksGrid">
            <?php if ($featured_books && $featured_books->num_rows > 0): ?>
                <?php while ($book = $featured_books->fetch_assoc()): ?>
                    <?php 
                        $img = (!empty($book['book_img']) && file_exists($book['book_img'])) ? $book['book_img'] : 'img1.jpg';
                        $is_avail = (strtolower($book['status']) === 'available');
                    ?>
                    <div class="col-xl-3 col-lg-4 col-sm-6">
                        <div class="book-card-clean tilt-3d h-100">
                            <div class="book-cover-wrap">
                                <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="book-cover-img">
                                <div class="position-absolute top-0 end-0 m-3">
                                    <span class="badge-status <?php echo $is_avail ? 'available' : 'issued'; ?>">
                                        <?php echo htmlspecialchars($book['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <span class="badge bg-primary bg-opacity-10 text-primary align-self-start rounded-pill px-2 py-1 small mb-2">
                                    <?php echo htmlspecialchars($book['category']); ?>
                                </span>
                                <h6 class="fw-bold text-main mb-1 text-truncate" title="<?php echo htmlspecialchars($book['title']); ?>">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </h6>
                                <p class="text-muted small mb-3">By <?php echo htmlspecialchars($book['authorname']); ?></p>
                                
                                <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center small">
                                    <span class="text-muted"><i class="fa-solid fa-boxes-stacked me-1"></i> <?php echo intval($book['available_copies'] ?? 1); ?> left</span>
                                    <a href="books.php?q=<?php echo urlencode($book['title']); ?>" class="btn btn-sm btn-primary-custom py-1 px-3">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ⚡ HOW IT WORKS PROCESS WITH GLOWING STEP CARDS -->
<section class="py-5 border-top">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill fw-bold mb-2">EFFORTLESS WORKFLOW</span>
            <h2 class="fw-bold text-main font-space">How It Works in 4 Steps</h2>
            <p class="text-muted mx-auto" style="max-width: 600px;">Streamlined library circulation engineered for students and faculty.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-3 col-sm-6">
                <div class="process-step-card tilt-3d h-100">
                    <div class="step-number-pill">1</div>
                    <div class="stat-icon-wrap mb-3">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <h5 class="fw-bold text-main mb-2">Register Account</h5>
                    <p class="text-muted small mb-0">Create your account in seconds with your student ID & email credentials.</p>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="process-step-card tilt-3d h-100">
                    <div class="step-number-pill" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">2</div>
                    <div class="stat-icon-wrap mb-3" style="background: var(--accent-subtle); color: var(--accent);">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <h5 class="fw-bold text-main mb-2">Search & Reserve</h5>
                    <p class="text-muted small mb-0">Search thousands of titles, filter categories, and submit 1-click borrow requests.</p>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="process-step-card tilt-3d h-100">
                    <div class="step-number-pill" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">3</div>
                    <div class="stat-icon-wrap mb-3" style="background: rgba(168, 85, 247, 0.1); color: #8b5cf6;">
                        <i class="fa-solid fa-qrcode"></i>
                    </div>
                    <h5 class="fw-bold text-main mb-2">QR Scan Issue</h5>
                    <p class="text-muted small mb-0">Collect your physical book from the circulation desk with camera verification.</p>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="process-step-card tilt-3d h-100">
                    <div class="step-number-pill" style="background: linear-gradient(135deg, #10b981, #059669);">4</div>
                    <div class="stat-icon-wrap mb-3" style="background: var(--success-subtle); color: var(--success);">
                        <i class="fa-solid fa-rotate-left"></i>
                    </div>
                    <h5 class="fw-bold text-main mb-2">1-Click Renew</h5>
                    <p class="text-muted small mb-0">Extend loan duration directly from your student portal whenever needed.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 🌌 CALL TO ACTION WITH COSMIC MESH & PARTICLES -->
<section class="py-5 text-white text-center cta-cosmic-banner">
    <div class="container py-4 position-relative" style="z-index: 2;">
        <span class="badge bg-primary bg-opacity-30 border border-primary border-opacity-40 text-white rounded-pill px-3 py-1 fw-bold mb-3">GET STARTED TODAY</span>
        <h2 class="fw-bold mb-3 font-space fs-1">Begin Your Academic Journey Today</h2>
        <p class="mb-4 text-white text-opacity-80 mx-auto fs-5" style="max-width: 620px;">
            Register free today to reserve books online, track reading history, read digital E-Books, and access university catalog materials.
        </p>
        <div class="d-flex justify-content-center flex-wrap gap-3">
            <a href="register.php" class="btn btn-emerald px-4 py-3 rounded-pill shadow-lg fw-bold fs-6">
                <i class="fa-solid fa-user-plus me-2"></i> Register Free Student Account
            </a>
            <a href="books.php" class="btn btn-secondary-custom px-4 py-3 rounded-pill fw-bold fs-6 text-white border-white border-opacity-25">
                <i class="fa-solid fa-book-open me-2"></i> Explore Entire Catalog
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
