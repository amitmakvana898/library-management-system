<?php
// admin_messages.php - Contact Messages & Inquiries
require_once __DIR__ . '/includes/config.php';
require_admin();

// Handle Mark Read
if (isset($_GET['mark_read'])) {
    $mid = intval($_GET['mark_read']);
    $conn->query("UPDATE messages SET status = 'read' WHERE id = $mid");
    set_flash('success', 'Message marked as read.');
    header("Location: admin_messages.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $mid = intval($_GET['delete']);
    $conn->query("DELETE FROM messages WHERE id = $mid");
    set_flash('success', 'Message deleted successfully.');
    header("Location: admin_messages.php");
    exit();
}

$messages = $conn->query("SELECT * FROM messages ORDER BY id DESC");

$is_dashboard = true;
$page_title = "Contact Messages";
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/includes/admin_sidebar.php'; ?>

    <div class="dashboard-main">
        <div class="dashboard-topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="sidebar-toggle" class="btn btn-sm btn-outline-secondary d-lg-none">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h5 class="fw-bold mb-0 text-main font-space">Contact Messages & Inquiries</h5>
                    <small class="text-muted">Review messages received through the public contact form</small>
                </div>
            </div>
            <a href="admin_dashboard.php" class="btn btn-sm btn-secondary-custom">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>

        <div class="dashboard-content">
            <?php display_flash(); ?>

            <div class="custom-table-card">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">Total Messages (<?php echo $messages ? $messages->num_rows : 0; ?>)</h6>
                </div>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Sender</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Date Received</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($messages && $messages->num_rows > 0): ?>
                                <?php while ($m = $messages->fetch_assoc()): ?>
                                    <?php $is_unread = ($m['status'] === 'unread'); ?>
                                    <tr class="<?php echo $is_unread ? 'fw-semibold bg-light' : ''; ?>">
                                        <td>
                                            <div class="text-dark"><?php echo htmlspecialchars($m['name']); ?></div>
                                            <small class="text-muted"><a href="mailto:<?php echo htmlspecialchars($m['email']); ?>"><?php echo htmlspecialchars($m['email']); ?></a></small>
                                        </td>
                                        <td class="text-dark"><?php echo htmlspecialchars($m['subject']); ?></td>
                                        <td>
                                            <div class="text-muted small" style="max-width: 320px;"><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
                                        </td>
                                        <td><?php echo date('d M, Y h:i A', strtotime($m['created_at'])); ?></td>
                                        <td>
                                            <?php if ($is_unread): ?>
                                                <span class="badge-pill-warning"><i class="fa-solid fa-envelope me-1"></i> Unread</span>
                                            <?php else: ?>
                                                <span class="badge-pill-secondary"><i class="fa-solid fa-envelope-open me-1"></i> Read</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <?php if ($is_unread): ?>
                                                    <a href="admin_messages.php?mark_read=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-success" title="Mark as Read">
                                                        <i class="fa-solid fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="mailto:<?php echo htmlspecialchars($m['email']); ?>?subject=Re: <?php echo urlencode($m['subject']); ?>" class="btn btn-sm btn-outline-primary" title="Reply via Email">
                                                    <i class="fa-solid fa-reply"></i>
                                                </a>
                                                <a href="admin_messages.php?delete=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this message?');" title="Delete">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-envelope-open fs-2 d-block mb-2"></i>
                                        No messages found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
