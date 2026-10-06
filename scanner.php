<?php
// scanner.php - Live Camera QR & Barcode Scanner for Circulation Desk & Readers
require_once __DIR__ . '/includes/config.php';

$is_admin = is_admin_logged_in();
$is_user = is_user_logged_in();
$is_dashboard = ($is_admin || $is_user);
$page_title = "QR & Barcode Scanner";

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($is_dashboard): ?>
<div class="dashboard-wrapper">
    <?php 
        if ($is_admin) {
            require_once __DIR__ . '/includes/admin_sidebar.php';
        } else {
            require_once __DIR__ . '/includes/user_sidebar.php';
        }
    ?>

    <div class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="sidebar-toggle" class="btn btn-sm btn-outline-secondary d-lg-none">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h5 class="fw-bold mb-0 text-main"><i class="fa-solid fa-qrcode text-primary me-2"></i> Live QR & Barcode Scanner</h5>
                    <small class="text-muted">Instantly scan Book QR codes or Student Library ID Cards</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if ($is_admin): ?>
                    <a href="admin_dashboard.php" class="btn btn-sm btn-secondary-custom">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                    <a href="issuebooks.php" class="btn btn-sm btn-primary-custom">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i> Manual Issue Desk
                    </a>
                <?php else: ?>
                    <a href="user_dashboard.php" class="btn btn-sm btn-secondary-custom">
                        <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                    <a href="books.php" class="btn btn-sm btn-secondary-custom">
                        <i class="fa-solid fa-book-open"></i> Browse Catalog
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-content">
            <?php display_flash(); ?>
<?php else: ?>
<div class="py-5">
    <div class="container">
        <div class="text-center mb-4">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill fw-bold mb-2">HARDWARE CAMERA RECOGNITION</span>
            <h1 class="fw-bold text-main">Live QR & Barcode Scanner</h1>
            <p class="text-muted">Point your mobile or laptop camera to scan physical book QR codes</p>
        </div>
<?php endif; ?>

            <div class="row g-4">
                <!-- Camera Scanner Box -->
                <div class="col-lg-6">
                    <div class="custom-card p-4 text-center">
                        <h6 class="fw-bold text-main mb-3"><i class="fa-solid fa-camera text-primary me-2"></i> Camera Feed</h6>
                        
                        <div id="reader" style="width: 100%; min-height: 300px; border-radius: 12px; overflow: hidden; background: #000;" class="mb-3"></div>
                        
                        <div class="d-flex justify-content-center gap-2 mb-3">
                            <button id="startScanBtn" class="btn btn-sm btn-success px-3 py-2">
                                <i class="fa-solid fa-play me-1"></i> Start Camera
                            </button>
                            <button id="stopScanBtn" class="btn btn-sm btn-danger px-3 py-2" disabled>
                                <i class="fa-solid fa-stop me-1"></i> Stop Camera
                            </button>
                        </div>
                        <small class="text-muted d-block">Point your device camera at a Book QR code or Student ID Card to auto-detect.</small>
                    </div>
                </div>

                <!-- Scan Results & Quick Action -->
                <div class="col-lg-6">
                    <div class="custom-card p-4">
                        <h6 class="fw-bold text-main mb-3"><i class="fa-solid fa-bolt text-warning me-2"></i> Instant Scan Result</h6>
                        
                        <div id="scanResultBox" class="p-4 bg-light rounded-3 border text-center mb-4">
                            <i class="fa-solid fa-barcode fs-1 text-muted mb-2 d-block"></i>
                            <h6 class="fw-bold text-main mb-1">Awaiting Scanner Input...</h6>
                            <p class="text-muted small mb-0">Scanned barcode or QR data will automatically load here.</p>
                        </div>

                        <!-- Manual Code Entry Helper -->
                        <div class="border-top pt-3">
                            <label class="form-label fw-semibold small text-muted">Or Enter Code / ID Manually</label>
                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="fa-solid fa-keyboard text-muted"></i></span>
                                <input type="text" id="manualCodeInput" class="form-control" placeholder="e.g. BOOK:16 or USER:2">
                                <button type="button" id="lookupCodeBtn" class="btn btn-primary-custom">Lookup</button>
                            </div>
                        </div>

                        <!-- Quick Action Links for Admins -->
                        <?php if ($is_admin): ?>
                            <div class="d-flex flex-column gap-2" id="quickActionButtons" style="display: none !important;">
                                <a id="issueLink" href="#" class="btn btn-primary-custom w-100 py-2">
                                    <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Issue Scanned Book
                                </a>
                                <a id="returnLink" href="#" class="btn btn-secondary-custom w-100 py-2">
                                    <i class="fa-solid fa-arrow-rotate-left me-1"></i> Return Scanned Book
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-2" id="quickActionButtons" style="display: none !important;">
                                <a id="viewBookLink" href="books.php" class="btn btn-primary-custom w-100 py-2">
                                    <i class="fa-solid fa-book-open me-1"></i> View Scanned Book in Catalog
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

<?php if ($is_dashboard): ?>
        </div>
    </div>
</div>
<?php else: ?>
    </div>
</div>
<?php endif; ?>

<!-- HTML5-QRCode Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let html5QrcodeScanner = null;
    const resultBox = document.getElementById('scanResultBox');
    const startBtn = document.getElementById('startScanBtn');
    const stopBtn = document.getElementById('stopScanBtn');
    const manualInput = document.getElementById('manualCodeInput');
    const lookupBtn = document.getElementById('lookupCodeBtn');
    const actionBtns = document.getElementById('quickActionButtons');
    const issueLink = document.getElementById('issueLink');
    const returnLink = document.getElementById('returnLink');
    const viewBookLink = document.getElementById('viewBookLink');

    function handleScanSuccess(decodedText) {
        let cleanText = decodedText.trim();
        resultBox.innerHTML = `
            <div class="alert alert-success border-0 mb-0 text-start">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="fa-solid fa-circle-check fs-4 text-success"></i>
                    <strong class="fs-6">Scan Successful!</strong>
                </div>
                <div class="bg-white p-2 rounded border font-monospace small mb-2 text-dark">
                    ${cleanText}
                </div>
                <small class="text-muted">Detected code automatically decoded.</small>
            </div>
        `;

        if (cleanText.startsWith("BOOK:")) {
            let bId = cleanText.replace("BOOK:", "").trim();
            if (issueLink) issueLink.href = `issuebooks.php?book_id=${bId}`;
            if (returnLink) returnLink.href = `returnbook.php?book_id=${bId}`;
            if (viewBookLink) viewBookLink.href = `books.php?q=${bId}`;
            if (actionBtns) actionBtns.style.setProperty('display', 'flex', 'important');
        } else if (cleanText.startsWith("USER:")) {
            let uId = cleanText.replace("USER:", "").trim();
            if (issueLink) issueLink.href = `issuebooks.php?user_id=${uId}`;
            if (actionBtns) actionBtns.style.setProperty('display', 'flex', 'important');
        }
    }

    startBtn.addEventListener('click', () => {
        html5QrcodeScanner = new Html5Qrcode("reader");
        html5QrcodeScanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: { width: 250, height: 250 } },
            handleScanSuccess,
            (errorMessage) => {}
        ).then(() => {
            startBtn.disabled = true;
            stopBtn.disabled = false;
        }).catch((err) => {
            alert("Could not access camera: " + err);
        });
    });

    stopBtn.addEventListener('click', () => {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop().then(() => {
                startBtn.disabled = false;
                stopBtn.disabled = true;
                document.getElementById('reader').innerHTML = '';
            });
        }
    });

    lookupBtn.addEventListener('click', () => {
        if (manualInput.value.trim()) {
            handleScanSuccess(manualInput.value.trim());
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
