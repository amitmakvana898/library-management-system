<?php
// print_receipt.php - Printable Circulation & Fine Receipt
require_once __DIR__ . '/includes/config.php';

$issue_id = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    SELECT bi.*, u.username, u.email, u.mobileno, b.authorname, b.category, b.rack_no 
    FROM book_issued bi 
    LEFT JOIN users u ON bi.user_id = u.id 
    LEFT JOIN books b ON bi.bookid = b.bookid 
    WHERE bi.id = ? 
    LIMIT 1
");
$stmt->bind_param("i", $issue_id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    die("Loan record not found.");
}

$issue = $res->fetch_assoc();
$site = get_site_settings($conn);

$qr_data = urlencode("LOAN:" . $issue['id'] . "|BOOK:" . $issue['bookid'] . "|USER:" . $issue['user_id']);
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . $qr_data;

$dashboard_url = is_admin_logged_in() ? 'issuedhistory.php' : (is_user_logged_in() ? 'my_issued_books.php' : 'index.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Circulation Receipt #<?php echo $issue['id']; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            min-height: 100vh;
            padding: 2.5rem 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .no-print-controls {
            margin-bottom: 2rem;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .btn-print {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
            padding: 0.65rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
            transition: all 0.2s;
        }
        .btn-print:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        .btn-back {
            background: #fff;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 0.65rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-back:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .receipt-card {
            width: 100%;
            max-width: 650px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 2.5rem;
        }

        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px dashed #e2e8f0;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .receipt-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
        }
        .receipt-meta {
            font-size: 0.85rem;
            color: #64748b;
        }

        .receipt-table {
            width: 100%;
            margin-bottom: 1.5rem;
            border-collapse: collapse;
        }
        .receipt-table td {
            padding: 0.65rem 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.95rem;
        }
        .receipt-table td.label {
            color: #64748b;
            font-weight: 600;
            width: 40%;
        }
        .receipt-table td.value {
            color: #0f172a;
            font-weight: 700;
            text-align: right;
        }

        .fine-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border: 1px solid #e2e8f0;
        }

        .receipt-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 1px solid #e2e8f0;
            padding-top: 1.5rem;
        }

        @media print {
            body { background: transparent; padding: 0; }
            .no-print-controls { display: none; }
            .receipt-card { box-shadow: none; border: none; padding: 0; }
        }
    </style>
</head>
<body>

<div class="no-print-controls">
    <button class="btn-print" onclick="window.print();">
        <i class="fa-solid fa-print me-1"></i> Print Receipt Slip
    </button>
    <a href="<?php echo htmlspecialchars($dashboard_url); ?>" class="btn-back" onclick="return handleSmartGoBack(event);">
        <i class="fa-solid fa-arrow-left me-1"></i> Go Back
    </a>
</div>

<div class="receipt-card">
    <div class="receipt-header">
        <div>
            <h2 class="receipt-title"><i class="fa-solid fa-book-bookmark text-primary me-2"></i><?php echo htmlspecialchars($site['library_name']); ?></h2>
            <p class="receipt-meta"><?php echo htmlspecialchars($site['address']); ?></p>
            <p class="receipt-meta">Email: <?php echo htmlspecialchars($site['contact_email']); ?> | Tel: <?php echo htmlspecialchars($site['contact_phone']); ?></p>
        </div>
        <div class="text-end">
            <img src="<?php echo $qr_url; ?>" alt="Receipt QR" style="width: 80px; height: 80px; border-radius: 6px; border: 1px solid #e2e8f0;">
            <span class="small text-muted d-block mt-1">#LOAN-<?php echo $issue['id']; ?></span>
        </div>
    </div>

    <table class="receipt-table">
        <tr>
            <td class="label">Transaction Type:</td>
            <td class="value">
                <span style="color: #10b981; text-transform: uppercase;">Circulation Receipt</span>
            </td>
        </tr>
        <tr>
            <td class="label">Borrower Name:</td>
            <td class="value"><?php echo htmlspecialchars($issue['username'] ?? 'Student'); ?></td>
        </tr>
        <tr>
            <td class="label">Member ID:</td>
            <td class="value">#LMS-<?php echo str_pad($issue['user_id'], 4, '0', STR_PAD_LEFT); ?></td>
        </tr>
        <tr>
            <td class="label">Book Title:</td>
            <td class="value"><?php echo htmlspecialchars($issue['book_title']); ?></td>
        </tr>
        <tr>
            <td class="label">Book Accession ID:</td>
            <td class="value">#<?php echo htmlspecialchars($issue['bookid']); ?></td>
        </tr>
        <tr>
            <td class="label">Issue Date:</td>
            <td class="value"><?php echo date('d M, Y', strtotime($issue['issue_date'])); ?></td>
        </tr>
        <tr>
            <td class="label">Due Return Date:</td>
            <td class="value" style="color:#10b981; font-weight:700;"><?php echo date('d M, Y', strtotime($issue['due_date'])); ?></td>
        </tr>
        <?php if (!empty($issue['return_date']) && $issue['return_date'] !== '0000-00-00'): ?>
            <tr>
                <td class="label">Actual Return Date:</td>
                <td class="value" style="color:#10b981;"><?php echo date('d M, Y', strtotime($issue['return_date'])); ?></td>
            </tr>
        <?php endif; ?>
    </table>

    <div class="fine-box">
        <div>
            <span class="small text-muted d-block fw-semibold">Late Fine Penalty:</span>
            <span class="fw-bold" style="font-size: 1.25rem; color: <?php echo $issue['fine'] > 0 ? '#ef4444' : '#10b981'; ?>">
                ₹<?php echo number_format($issue['fine'], 2); ?>
            </span>
        </div>
        <div>
            <span class="small text-muted d-block fw-semibold">Status:</span>
            <span class="badge" style="background:#d1fae5; color:#065f46; font-size:0.8rem; padding:4px 10px; border-radius:6px;">
                <?php echo strtoupper($issue['status']); ?> (<?php echo strtoupper($issue['fine_status'] ?? 'PAID'); ?>)
            </span>
        </div>
    </div>

    <div class="receipt-footer">
        <div class="small text-muted">
            <em>Thank you for returning books on time.</em>
        </div>
        <div class="text-end">
            <div style="border-bottom: 1px solid #334155; width: 140px; margin-bottom: 4px;"></div>
            <span class="small fw-semibold text-muted">Librarian Signature</span>
        </div>
    </div>
</div>

<script>
function handleSmartGoBack(e) {
    try {
        window.close();
    } catch(err) {}
    
    if (window.history.length > 1) {
        history.back();
        return false;
    }
    return true;
}
</script>

</body>
</html>
