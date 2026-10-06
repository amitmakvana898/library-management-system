<?php
// includes/fine_payment_modal.php - Student Online Fine Payment Modal Simulator (UPI / NetBanking / Cards)
if (!isset($unpaid_fine)) {
    $uid_tmp = $_SESSION['user_id'] ?? 0;
    $unpaid_res = $conn->query("SELECT SUM(fine) as s FROM book_issued WHERE user_id = '$uid_tmp' AND fine_status = 'unpaid'");
    $unpaid_fine = $unpaid_res ? floatval($unpaid_res->fetch_assoc()['s']) : 0.0;
}
?>

<!-- Online Fine Payment Modal -->
<div class="modal fade" id="finePaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header px-4 py-3 border-bottom" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(168, 85, 247, 0.1) 100%);">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-qrcode fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-main mb-0 font-space">Online Fine Payment</h5>
                        <small class="text-muted">Instant settlement via UPI, Card, or NetBanking</small>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 text-center">
                <?php if ($unpaid_fine > 0): ?>
                    <div class="p-3 rounded-4 mb-4" style="background: var(--light); border: 1px dashed var(--border-color);">
                        <small class="text-muted text-uppercase fw-semibold d-block mb-1">Total Outstanding Dues</small>
                        <div class="display-6 fw-bold text-danger">₹<?php echo number_format($unpaid_fine, 2); ?></div>
                        <small class="text-muted"><i class="fa-solid fa-shield-halved text-success me-1"></i> 256-Bit SSL Mock Gateway</small>
                    </div>

                    <!-- Dynamic UPI QR Simulation -->
                    <div class="mb-4">
                        <div class="p-3 bg-white d-inline-block rounded-4 shadow-sm border">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=<?php echo urlencode('upi://pay?pa=central.library@upi&pn=Central+Library+LMS&am=' . $unpaid_fine . '&cu=INR'); ?>" alt="Scan & Pay UPI" class="img-fluid" style="width: 150px; height: 150px;">
                        </div>
                        <div class="small text-muted mt-2">
                            <i class="fa-solid fa-mobile-screen-button me-1"></i> Scan with <strong>GPay, PhonePe, Paytm, BHIM</strong>
                        </div>
                    </div>

                    <!-- Payment Method Selector -->
                    <form id="finePaymentForm" method="POST" action="pay_fine.php">
                        <input type="hidden" name="pay_all" value="1">
                        
                        <div class="text-start mb-3">
                            <label class="form-label small fw-bold text-muted">Select Payment Method</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="p-2 border rounded-3 d-flex align-items-center gap-2 cursor-pointer w-100 bg-light">
                                        <input type="radio" name="payment_method" value="UPI (GPay / PhonePe / Paytm)" checked class="form-check-input mt-0">
                                        <span class="small fw-semibold"><i class="fa-solid fa-bolt text-warning me-1"></i> UPI Direct</span>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <label class="p-2 border rounded-3 d-flex align-items-center gap-2 cursor-pointer w-100 bg-light">
                                        <input type="radio" name="payment_method" value="Debit / Credit Card" class="form-check-input mt-0">
                                        <span class="small fw-semibold"><i class="fa-solid fa-credit-card text-primary me-1"></i> Card</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" id="btnConfirmFinePay" class="btn btn-primary-custom w-100 py-2 fw-bold shadow-md">
                            <i class="fa-solid fa-check-circle me-1"></i> Confirm & Pay ₹<?php echo number_format($unpaid_fine, 2); ?>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="py-4">
                        <div class="mb-3 text-success">
                            <i class="fa-solid fa-circle-check display-3"></i>
                        </div>
                        <h5 class="fw-bold text-main">All Cleared!</h5>
                        <p class="text-muted">You have no outstanding overdue fines on your account.</p>
                        <button type="button" class="btn btn-secondary-custom px-4 py-2" data-bs-dismiss="modal">Close</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
