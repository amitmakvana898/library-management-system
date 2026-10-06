<?php
// contact.php
require_once __DIR__ . '/includes/config.php';
$site = get_site_settings($conn);

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $conn->prepare("INSERT INTO messages (name, email, subject, message, status) VALUES (?, ?, ?, ?, 'unread')");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            $success = "Thank you for reaching out! Your message has been sent to the library staff.";
            $_POST = [];
        } else {
            $error = "Failed to send message: " . $conn->error;
        }
    }
}

$page_title = "Contact Us";
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5 bg-light border-bottom">
    <div class="container text-center">
        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold mb-3">Get in Touch</span>
        <h1 class="fw-bold text-dark">Contact Library Support</h1>
        <p class="text-muted mx-auto" style="max-width: 600px;">
            Have questions regarding book availability, borrowing limits, or research resources? We're here to help.
        </p>
    </div>
</div>

<div class="py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-5">
                <div class="custom-card p-4 h-100">
                    <h4 class="fw-bold text-dark mb-4">Contact Information</h4>
                    
                    <div class="d-flex align-items-start gap-3 mb-4">
                        <div class="stat-icon primary m-0" style="width: 48px; height: 48px; font-size: 1.25rem;">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Our Location</h6>
                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($site['address']); ?></p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 mb-4">
                        <div class="stat-icon info m-0" style="width: 48px; height: 48px; font-size: 1.25rem;">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Email Support</h6>
                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($site['contact_email']); ?></p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 mb-4">
                        <div class="stat-icon success m-0" style="width: 48px; height: 48px; font-size: 1.25rem;">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Phone Number</h6>
                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($site['contact_phone']); ?></p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon warning m-0" style="width: 48px; height: 48px; font-size: 1.25rem;">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Operating Hours</h6>
                            <p class="text-muted small mb-0">Monday - Saturday: 8:00 AM - 8:00 PM<br>Sunday: Closed for maintenance</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="custom-card p-4">
                    <h4 class="fw-bold text-dark mb-3">Send Us a Message</h4>
                    <p class="text-muted small mb-4">Fill out the form below and our librarians will get back to you promptly.</p>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="contact.php" class="d-flex flex-column gap-3">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Your Name *</label>
                                <input type="text" name="name" class="form-control form-control-custom" placeholder="John Doe" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-muted">Email Address *</label>
                                <input type="email" name="email" class="form-control form-control-custom" placeholder="john@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div>
                            <label class="form-label fw-semibold small text-muted">Subject *</label>
                            <input type="text" name="subject" class="form-control form-control-custom" placeholder="e.g. Book Renewal Inquiry" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required>
                        </div>

                        <div>
                            <label class="form-label fw-semibold small text-muted">Message *</label>
                            <textarea name="message" rows="5" class="form-control form-control-custom" placeholder="Write your message here..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary-custom py-2 px-4 align-self-start">
                            <i class="fa-solid fa-paper-plane me-1"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>