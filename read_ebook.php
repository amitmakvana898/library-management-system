<?php
// read_ebook.php - Digital E-Book / PDF Reader with Smart Bookmarking & Resume Reading
require_once __DIR__ . '/includes/config.php';

$book_id = intval($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM books WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    die("Book not found.");
}

$book = $res->fetch_assoc();
$pdf_file = $book['pdf_file'];

if (empty($pdf_file) || !file_exists($pdf_file)) {
    $is_dashboard = false;
    $page_title = "E-Book Not Available";
    require_once __DIR__ . '/includes/header.php';
    echo "<div class='container py-5 text-center'>
            <div class='custom-card p-5 max-w-lg mx-auto'>
                <i class='fa-solid fa-file-pdf fs-1 text-danger mb-3'></i>
                <h4 class='fw-bold text-main'>Digital PDF Copy Pending</h4>
                <p class='text-muted'>The digital e-book version for <strong>" . htmlspecialchars($book['title']) . "</strong> has not been uploaded yet. Please borrow the physical copy from the library desk.</p>
                <a href='books.php' class='btn btn-primary-custom mt-3'>Back to Books Catalog</a>
            </div>
          </div>";
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

$page_title = "Read: " . $book['title'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Book Reader: <?php echo htmlspecialchars($book['title']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0b0f19;
            color: #f1f5f9;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .reader-topbar {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(99, 102, 241, 0.25);
            padding: 0.65rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 20;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .reader-frame {
            flex-grow: 1;
            width: 100%;
            border: none;
            background: #0b0f19;
        }
        .btn-indigo {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #ffffff;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            padding: 6px 14px;
            transition: all 0.2s ease;
        }
        .btn-indigo:hover {
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.45);
            color: #ffffff;
            transform: translateY(-1px);
        }
        .bookmark-input {
            background: #1e293b;
            border: 1px solid rgba(99, 102, 241, 0.3);
            color: #f8fafc;
            width: 65px;
            text-align: center;
            border-radius: 6px;
            padding: 4px 6px;
            font-size: 0.85rem;
        }
        .bookmark-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.3);
        }
        .resume-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid #6366f1;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border-radius: 12px;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideUp 0.3s ease-out;
        }
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>

<div class="reader-topbar">
    <div class="d-flex align-items-center gap-3">
        <a href="books.php" class="btn btn-sm btn-outline-secondary text-white border-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Catalog
        </a>
        <div>
            <h6 class="fw-bold mb-0 text-white font-space"><?php echo htmlspecialchars($book['title']); ?></h6>
            <small style="color: #a5b4fc !important;">By <?php echo htmlspecialchars($book['authorname']); ?> &bull; <?php echo htmlspecialchars($book['category']); ?></small>
        </div>
    </div>

    <!-- Bookmark & Jump Page Controls -->
    <div class="d-flex align-items-center gap-2">
        <div class="d-none d-md-flex align-items-center gap-1 bg-dark bg-opacity-50 px-2 py-1 rounded-3 border border-secondary border-opacity-25">
            <label for="pageInput" class="small text-muted mb-0 me-1"><i class="fa-solid fa-bookmark text-warning me-1"></i>Page:</label>
            <input type="number" id="pageInput" min="1" class="bookmark-input" placeholder="1">
            <button type="button" id="btnJumpPage" class="btn btn-sm btn-secondary px-2 py-1" title="Jump & Save Bookmark">
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>

        <button type="button" id="btnFullscreen" class="btn btn-sm btn-outline-light" title="Toggle Fullscreen">
            <i class="fa-solid fa-expand"></i>
        </button>

        <a href="<?php echo htmlspecialchars($pdf_file); ?>" download class="btn btn-sm btn-indigo">
            <i class="fa-solid fa-download me-1"></i> Download PDF
        </a>
    </div>
</div>

<iframe id="pdfFrame" src="<?php echo htmlspecialchars($pdf_file); ?>#toolbar=1" class="reader-frame" title="E-Book PDF Viewer"></iframe>

<!-- Toast container for Auto-Resume -->
<div id="resumeToast" class="resume-toast d-none">
    <i class="fa-solid fa-bookmark text-warning fs-5"></i>
    <div>
        <div class="small fw-bold text-white">Saved Reading Progress</div>
        <div class="text-muted small">You were last on <span id="toastPageNum" class="text-info fw-bold">Page 1</span></div>
    </div>
    <button type="button" id="btnResumeJump" class="btn btn-sm btn-indigo px-3 py-1">Resume</button>
    <button type="button" class="btn btn-sm text-white-50" onclick="document.getElementById('resumeToast').classList.add('d-none')">&times;</button>
</div>

<script>
    const bookId = <?php echo $book_id; ?>;
    const basePdfUrl = "<?php echo htmlspecialchars($pdf_file); ?>";
    const storageKey = `lms_read_page_book_${bookId}`;
    const frame = document.getElementById('pdfFrame');
    const pageInput = document.getElementById('pageInput');
    const toast = document.getElementById('resumeToast');
    const toastPageNum = document.getElementById('toastPageNum');

    // Check last read page
    const lastPage = localStorage.getItem(storageKey);
    if (lastPage && parseInt(lastPage) > 1) {
        pageInput.value = lastPage;
        toastPageNum.textContent = `Page ${lastPage}`;
        toast.classList.remove('d-none');
    }

    function jumpToPage(pageNum) {
        if (!pageNum || pageNum < 1) return;
        localStorage.setItem(storageKey, pageNum);
        pageInput.value = pageNum;
        frame.src = `${basePdfUrl}#page=${pageNum}&toolbar=1`;
        toast.classList.add('d-none');
    }

    document.getElementById('btnJumpPage').addEventListener('click', () => {
        jumpToPage(parseInt(pageInput.value));
    });

    pageInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            jumpToPage(parseInt(pageInput.value));
        }
    });

    document.getElementById('btnResumeJump').addEventListener('click', () => {
        jumpToPage(parseInt(lastPage));
    });

    // Fullscreen toggle
    document.getElementById('btnFullscreen').addEventListener('click', () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(() => {});
        } else {
            document.exitFullscreen().catch(() => {});
        }
    });
</script>

</body>
</html>
