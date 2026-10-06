<?php
// books.php - Clean Luxury Book Catalog, E-Book Reader, Reviews & Request System
require_once __DIR__ . '/includes/config.php';

// Handle Book Borrow Request from user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_book') {
    if (!is_user_logged_in()) {
        set_flash('danger', 'Please login to request or reserve this book.');
        header("Location: userlogin.php");
        exit();
    }

    $book_id = intval($_POST['book_id'] ?? 0);
    $user_id = $_SESSION['user_id'];

    if ($book_id > 0) {
        $chk_book = $conn->query("SELECT * FROM books WHERE id = $book_id LIMIT 1");
        if ($chk_book && $chk_book->num_rows > 0) {
            $b_data = $chk_book->fetch_assoc();
            $b_code = $conn->real_escape_string($b_data['bookid'] ?? $b_data['id']);
            
            // 1. Check if user already holds an active copy of this book
            $chk_already_holding = $conn->query("SELECT id FROM book_issued WHERE user_id = $user_id AND (bookid = '$b_code' OR bookid = '{$b_data['id']}') AND status = 'issued'");
            if ($chk_already_holding && $chk_already_holding->num_rows > 0) {
                set_flash('info', 'You already have an active borrowed copy of "' . htmlspecialchars($b_data['title']) . '". Please return it before requesting another copy.');
            }
            // 2. Check max borrowing limit (3 books)
            else {
                $chk_quota = $conn->query("SELECT COUNT(*) as c FROM book_issued WHERE user_id = $user_id AND status = 'issued'");
                $active_cnt = $chk_quota ? intval($chk_quota->fetch_assoc()['c']) : 0;
                
                if ($active_cnt >= 3) {
                    set_flash('warning', 'Quota reached: You already have 3 books currently issued. Please return previous books to request new ones.');
                }
                // 3. Check if already requested and pending
                else {
                    $chk_req = $conn->query("SELECT id FROM book_requests WHERE user_id = $user_id AND book_id = $book_id AND status = 'pending'");
                    if ($chk_req && $chk_req->num_rows > 0) {
                        set_flash('warning', 'You already have an active pending request for "' . htmlspecialchars($b_data['title']) . '".');
                    } elseif (intval($b_data['available_copies'] ?? 1) <= 0 || strtolower($b_data['status']) === 'issued') {
                        // Out of stock - prompt user to join waitlist
                        set_flash('warning', 'Book "' . htmlspecialchars($b_data['title']) . '" is currently on loan. You can click the Bell icon to join the Waitlist!');
                    } else {
                        $ins = $conn->prepare("INSERT INTO book_requests (user_id, book_id, status) VALUES (?, ?, 'pending')");
                        $ins->bind_param("ii", $user_id, $book_id);
                        if ($ins->execute()) {
                            set_flash('success', 'Request submitted successfully! The librarian will review and issue "' . htmlspecialchars($b_data['title']) . '".');
                        } else {
                            set_flash('danger', 'Failed to submit request: ' . $conn->error);
                        }
                    }
                }
            }
        }
    }
    header("Location: books.php");
    exit();
}

// Handle Book Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_book_review') {
    if (!is_user_logged_in()) {
        set_flash('danger', 'Please login to leave a review.');
        header("Location: userlogin.php");
        exit();
    }

    $b_id = intval($_POST['book_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 5);
    $review_text = trim($_POST['review_text'] ?? '');
    $u_id = $_SESSION['user_id'];

    if (!empty($review_text) && $b_id > 0) {
        $rev_stmt = $conn->prepare("INSERT INTO book_reviews (book_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)");
        $rev_stmt->bind_param("iiis", $b_id, $u_id, $rating, $review_text);
        if ($rev_stmt->execute()) {
            set_flash('success', 'Thank you! Your book review has been submitted.');
        }
    }
    header("Location: books.php");
    exit();
}

// User Wishlist & Waitlist Ids
$user_wishlist = [];
$user_waitlist = [];
if (is_user_logged_in()) {
    $uid = $_SESSION['user_id'];
    $w_res = $conn->query("SELECT book_id FROM wishlist WHERE user_id = $uid");
    while ($w = $w_res->fetch_assoc()) {
        $user_wishlist[] = $w['book_id'];
    }
    $wait_res = $conn->query("SELECT book_id FROM book_waitlist WHERE user_id = $uid AND notified = 0");
    if ($wait_res) {
        while ($wt = $wait_res->fetch_assoc()) {
            $user_waitlist[] = $wt['book_id'];
        }
    }
}

// Filters & Query Parameters
$search_query = trim($_GET['q'] ?? '');
$category_filter = trim($_GET['category'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$ebook_filter = trim($_GET['ebook'] ?? '');
$sort_by = trim($_GET['sort'] ?? 'newest');

// Build SQL Query
$sql = "SELECT * FROM books WHERE 1=1";
$params = [];
$types = "";

if (!empty($search_query)) {
    $sql .= " AND (title LIKE ? OR authorname LIKE ? OR category LIKE ? OR isbn LIKE ? OR CAST(bookid AS CHAR) LIKE ?)";
    $like_q = "%{$search_query}%";
    $params[] = $like_q;
    $params[] = $like_q;
    $params[] = $like_q;
    $params[] = $like_q;
    $params[] = $like_q;
    $types .= "sssss";
}

if (!empty($category_filter)) {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if (!empty($status_filter)) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($ebook_filter === '1') {
    $sql .= " AND pdf_file IS NOT NULL AND pdf_file != ''";
}

// Sorting
switch ($sort_by) {
    case 'title_asc':
        $sql .= " ORDER BY title ASC";
        break;
    case 'author':
        $sql .= " ORDER BY authorname ASC";
        break;
    default:
        $sql .= " ORDER BY id DESC";
        break;
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$books_res = $stmt->get_result();

$all_categories = $conn->query("SELECT DISTINCT name FROM categories ORDER BY name ASC");

$page_title = "Explore Books Catalog";
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5 border-bottom">
    <div class="container">
        <div class="text-center mb-4">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill fw-bold mb-2">DIGITAL & PHYSICAL CATALOG</span>
            <h1 class="fw-bold text-main">Library Catalog & E-Books</h1>
            <p class="text-muted">Search, reserve physical copies, or read digital PDF resources online</p>
        </div>

        <?php display_flash(); ?>

        <!-- Search & Filter Controls with Voice Input -->
        <div class="custom-card p-4 shadow-sm mb-4">
            <form method="GET" action="books.php" class="row g-3">
                <div class="col-lg-4 col-md-6 position-relative">
                    <label class="form-label fw-semibold small text-muted">Search Keyword / Voice</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="fa-solid fa-magnifying-glass text-primary"></i></span>
                        <input type="text" name="q" id="catalogSearchInput" class="form-control border-start-0 border-end-0 live-search-input" placeholder="Title, author, ISBN..." value="<?php echo htmlspecialchars($search_query); ?>" autocomplete="off">
                        <button type="button" class="btn btn-outline-secondary border-start-0 border-end-0 voice-search-btn" data-target-input="catalogSearchInput" title="Voice Search (Speak)">
                            <i class="fa-solid fa-microphone text-primary"></i>
                        </button>
                        <button type="submit" class="btn btn-primary-custom px-3 fw-bold" title="Search Books">
                            Search
                        </button>
                    </div>
                    <div id="liveSearchResults" class="live-search-dropdown shadow-lg rounded-3 border d-none"></div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="form-label fw-semibold small text-muted">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php if ($all_categories && $all_categories->num_rows > 0): ?>
                            <?php while ($c = $all_categories->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($c['name']); ?>" <?php echo $category_filter === $c['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="form-label fw-semibold small text-muted">Format</label>
                    <select name="ebook" class="form-select">
                        <option value="">All Formats</option>
                        <option value="1" <?php echo $ebook_filter === '1' ? 'selected' : ''; ?>>E-Books (PDF)</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4">
                    <label class="form-label fw-semibold small text-muted">Sort Order</label>
                    <select name="sort" class="form-select">
                        <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest Added</option>
                        <option value="title_asc" <?php echo $sort_by === 'title_asc' ? 'selected' : ''; ?>>Title (A-Z)</option>
                        <option value="author" <?php echo $sort_by === 'author' ? 'selected' : ''; ?>>Author (A-Z)</option>
                    </select>
                </div>

                <div class="col-lg-1 col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary-custom w-100 py-2">
                        Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Books Grid -->
<div class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold text-main mb-0">Showing <?php echo $books_res ? $books_res->num_rows : 0; ?> Titles</h5>
            <?php if (!empty($search_query) || !empty($category_filter) || !empty($status_filter) || !empty($ebook_filter)): ?>
                <a href="books.php" class="btn btn-sm btn-secondary-custom rounded-pill">
                    <i class="fa-solid fa-xmark me-1"></i> Clear Filters
                </a>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <?php if ($books_res && $books_res->num_rows > 0): ?>
                <?php while ($b = $books_res->fetch_assoc()): ?>
                    <?php 
                        $img = (!empty($b['book_img']) && file_exists($b['book_img'])) ? $b['book_img'] : 'img1.jpg';
                        $is_avail = (strtolower($b['status']) === 'available' && intval($b['available_copies'] ?? 1) > 0);
                        $has_pdf = (!empty($b['pdf_file']) && file_exists($b['pdf_file']));
                        $in_wish = in_array($b['id'], $user_wishlist);
                        $in_wait = in_array($b['id'], $user_waitlist);
                        
                        $book_qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=" . urlencode("BOOK:" . $b['id']);
                        
                        // Expected return date if book is currently checked out
                        $exp_return_date = null;
                        if (!$is_avail) {
                            $bid_val = $b['bookid'] ?? $b['id'];
                            $exp_q = $conn->query("SELECT MIN(due_date) as exp FROM book_issued WHERE (bookid = '$bid_val' OR bookid = '{$b['id']}') AND status = 'issued'");
                            if ($exp_q && $row_exp = $exp_q->fetch_assoc()) {
                                $exp_return_date = $row_exp['exp'];
                            }
                        }
                    ?>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="book-card-clean h-100 d-flex flex-column">
                            <div class="book-cover-wrap">
                                <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($b['title']); ?>" class="book-cover-img">
                                
                                <!-- Top Wishlist Button -->
                                <div class="position-absolute top-0 start-0 m-2">
                                    <?php if (is_user_logged_in()): ?>
                                        <a href="toggle_wishlist.php?book_id=<?php echo $b['id']; ?>" class="btn btn-sm <?php echo $in_wish ? 'btn-danger' : 'btn-light'; ?> rounded-circle shadow-sm" style="width: 34px; height: 34px; display: flex; align-items: center; justify-content: center;" title="<?php echo $in_wish ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
                                            <i class="fa-solid fa-heart <?php echo $in_wish ? 'text-white' : 'text-danger'; ?>"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div class="position-absolute top-0 end-0 m-2 d-flex flex-column gap-1 align-items-end">
                                    <span class="badge-status <?php echo $is_avail ? 'available' : 'issued'; ?>">
                                        <?php echo $is_avail ? 'Available' : 'Issued'; ?>
                                    </span>
                                    <?php if ($has_pdf): ?>
                                        <span class="badge bg-danger text-white rounded-pill px-2 py-1 small"><i class="fa-solid fa-file-pdf me-1"></i> PDF</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <span class="badge bg-primary bg-opacity-10 text-primary align-self-start rounded-pill px-2 py-1 small mb-2">
                                    <?php echo htmlspecialchars($b['category']); ?>
                                </span>
                                <h6 class="fw-bold text-main mb-1 text-truncate" title="<?php echo htmlspecialchars($b['title']); ?>">
                                    <?php echo htmlspecialchars($b['title']); ?>
                                </h6>
                                <p class="text-muted small mb-2 text-truncate">By <?php echo htmlspecialchars($b['authorname']); ?></p>
                                
                                <?php if (!$is_avail && $exp_return_date): ?>
                                    <div class="mb-2">
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-2 py-1 small d-inline-flex align-items-center gap-1">
                                            <i class="fa-regular fa-clock"></i> Return: <?php echo date('d M', strtotime($exp_return_date)); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                                    <small class="text-muted"><i class="fa-solid fa-boxes-stacked me-1"></i> <?php echo intval($b['available_copies'] ?? 0); ?> left</small>
                                    <div class="d-flex gap-1 align-items-center">
                                        <?php if ($has_pdf): ?>
                                            <a href="read_ebook.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-danger py-1 px-2" title="Read E-Book Online">
                                                <i class="fa-solid fa-book-open"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-secondary-custom py-1 px-2" data-bs-toggle="modal" data-bs-target="#bookModal<?php echo $b['id']; ?>" title="Quick View & Details">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Book Details & Modal -->
                    <div class="modal fade" id="bookModal<?php echo $b['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                <div class="modal-header border-bottom px-4 py-3">
                                    <h5 class="modal-title fw-bold text-main">Book Details & Availability</h5>
                                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="row g-4 mb-4">
                                        <div class="col-md-4 text-center">
                                            <img src="<?php echo htmlspecialchars($img); ?>" alt="Cover" class="img-fluid rounded-3 shadow-md mb-3" style="max-height: 240px; object-fit: contain;">
                                            <div class="p-3 bg-light rounded-3 border d-inline-block">
                                                <img src="<?php echo $book_qr_url; ?>" alt="Book QR" style="width: 90px; height: 90px;">
                                                <small class="text-muted d-block mt-1" style="font-size:0.7rem;">Scan at Desk</small>
                                            </div>
                                        </div>

                                        <div class="col-md-8">
                                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-1 small mb-2 d-inline-block"><?php echo htmlspecialchars($b['category']); ?></span>
                                            <h4 class="fw-bold text-main mb-1"><?php echo htmlspecialchars($b['title']); ?></h4>
                                            <p class="text-muted mb-3"><i class="fa-solid fa-pen-nib text-primary me-1"></i> Author: <strong><?php echo htmlspecialchars($b['authorname']); ?></strong></p>
                                            
                                            <div class="p-3 bg-light rounded-3 mb-3 small">
                                                <div class="row g-2">
                                                    <div class="col-6"><strong>Accession ID:</strong> #<?php echo htmlspecialchars($b['bookid'] ?? $b['id']); ?></div>
                                                    <div class="col-6"><strong>Status:</strong> <span class="<?php echo $is_avail ? 'text-success fw-bold' : 'text-danger fw-bold'; ?>"><?php echo $is_avail ? 'Available' : 'Issued'; ?></span></div>
                                                    <div class="col-6"><strong>Shelf / Rack:</strong> <?php echo htmlspecialchars($b['rack_no'] ?? 'Rack 1'); ?></div>
                                                    <div class="col-6"><strong>Available Copies:</strong> <?php echo intval($b['available_copies'] ?? 0); ?> / <?php echo intval($b['quantity'] ?? 1); ?></div>
                                                </div>
                                            </div>

                                            <?php if (!$is_avail): ?>
                                                <div class="p-3 rounded-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 text-warning mb-3 d-flex align-items-center gap-2">
                                                    <i class="fa-regular fa-calendar-check fs-5"></i>
                                                    <div>
                                                        <strong>Expected Return:</strong> <?php echo $exp_return_date ? date('d M, Y', strtotime($exp_return_date)) : 'On loan (Check-in expected soon)'; ?>
                                                        <div class="small opacity-75">All physical copies are currently borrowed. You can read the E-Book or set an alert.</div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($b['description'])): ?>
                                                <p class="text-muted small mb-3"><?php echo nl2br(htmlspecialchars($b['description'])); ?></p>
                                            <?php endif; ?>

                                            <div class="d-flex flex-wrap gap-2 align-items-center pt-2">
                                                <?php if ($has_pdf): ?>
                                                    <a href="read_ebook.php?id=<?php echo $b['id']; ?>" class="btn btn-danger py-2 px-3">
                                                        <i class="fa-solid fa-file-pdf me-1"></i> Read E-Book Online
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($is_avail && is_user_logged_in()): ?>
                                                    <form method="POST" action="books.php" class="d-inline">
                                                        <input type="hidden" name="action" value="request_book">
                                                        <input type="hidden" name="book_id" value="<?php echo $b['id']; ?>">
                                                        <button type="submit" class="btn btn-primary-custom py-2 px-3">
                                                            <i class="fa-solid fa-bookmark me-1"></i> Request to Borrow
                                                        </button>
                                                    </form>
                                                <?php elseif (!$is_avail): ?>
                                                    <?php if (is_user_logged_in()): ?>
                                                        <button type="button" class="btn <?php echo $in_wait ? 'btn-success' : 'btn-outline-warning'; ?> py-2 px-3 btn-waitlist-toggle" data-book-id="<?php echo $b['id']; ?>">
                                                            <i class="fa-solid fa-bell me-1"></i> <?php echo $in_wait ? 'Alert Active (Notifying you)' : 'Notify Me When Available'; ?>
                                                        </button>
                                                    <?php else: ?>
                                                        <a href="userlogin.php" class="btn btn-outline-warning py-2 px-3">
                                                            <i class="fa-solid fa-bell me-1"></i> Login for Return Alert
                                                        </a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <a href="userlogin.php" class="btn btn-primary-custom py-2 px-3">
                                                        <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Login to Request
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="p-5 custom-card">
                        <i class="fa-solid fa-book-skull text-muted fs-1 mb-3"></i>
                        <h4 class="fw-bold text-main">No Books Found</h4>
                        <p class="text-muted">No books matching your search filters were located in the catalog.</p>
                        <a href="books.php" class="btn btn-primary-custom mt-2">Reset Filters</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
