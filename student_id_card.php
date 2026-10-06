<?php
// student_id_card.php - Printable Student Library ID Card Generator
require_once __DIR__ . '/includes/config.php';

$user_id = intval($_GET['id'] ?? ($_SESSION['user_id'] ?? 0));

if (!is_admin_logged_in() && (!is_user_logged_in() || $_SESSION['user_id'] != $user_id)) {
    set_flash('danger', 'Unauthorized access to ID card.');
    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    die("Student account not found.");
}

$user = $res->fetch_assoc();
$site = get_site_settings($conn);

$u_img = (!empty($user['profile_img']) && file_exists($user['profile_img'])) ? $user['profile_img'] : 'uploads/profile_1.jpg';
if (!file_exists($u_img)) $u_img = 'admin.png';

$qr_data = urlencode("USER:" . $user['id']);
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . $qr_data;

$dashboard_url = is_admin_logged_in() ? 'admin_dashboard.php' : (is_user_logged_in() ? 'user_dashboard.php' : 'index.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library ID Card - <?php echo htmlspecialchars($user['username']); ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
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
            transform: translateY(-1px);
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
            background: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a;
        }

        /* CARD CONTAINER */
        .id-card {
            width: 420px;
            height: 260px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 35px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            position: relative;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
        }

        .id-card-header {
            background: linear-gradient(135deg, #065f46 0%, #059669 50%, #06b6d4 100%);
            color: #ffffff;
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .id-card-header h4 {
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }
        .id-card-header span {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 999px;
            font-weight: 700;
        }

        .id-card-body {
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-grow: 1;
        }

        .id-photo {
            width: 84px;
            height: 104px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid #e2e8f0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            flex-shrink: 0;
        }

        .id-info {
            flex-grow: 1;
        }
        .id-name {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
            margin-bottom: 4px;
        }
        .id-meta {
            font-size: 0.8rem;
            color: #64748b;
            margin-bottom: 2px;
        }
        .id-meta strong {
            color: #1e293b;
        }

        .id-card-footer {
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            padding: 0.5rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.65rem;
            color: #94a3b8;
        }

        @media print {
            body { background: transparent; padding: 0; }
            .no-print-controls { display: none; }
            .id-card {
                box-shadow: none;
                border: 1px solid #000;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>

<div class="no-print-controls">
    <button class="btn-print" onclick="window.print();">
        <i class="fa-solid fa-print"></i> Print Library Card
    </button>
    <a href="<?php echo htmlspecialchars($dashboard_url); ?>" class="btn-back" onclick="return handleSmartGoBack(event);">
        <i class="fa-solid fa-arrow-left"></i> Go Back
    </a>
</div>

<!-- LAMINATED ID CARD -->
<div class="id-card">
    <div class="id-card-header">
        <div>
            <h4><i class="fa-solid fa-book-bookmark me-1"></i> <?php echo htmlspecialchars($site['library_name']); ?></h4>
        </div>
        <span>STUDENT PASS</span>
    </div>

    <div class="id-card-body">
        <img src="<?php echo htmlspecialchars($u_img); ?>" alt="Student Photo" class="id-photo">
        <div class="id-info">
            <div class="id-name"><?php echo htmlspecialchars($user['username']); ?></div>
            <div class="id-meta">Member ID: <strong>#LMS-<?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?></strong></div>
            <div class="id-meta">Email: <strong><?php echo htmlspecialchars($user['email']); ?></strong></div>
            <div class="id-meta">Mobile: <strong><?php echo htmlspecialchars($user['mobileno'] ?? '-'); ?></strong></div>
            <div class="id-meta">Valid Thru: <strong>December <?php echo date('Y') + 1; ?></strong></div>
        </div>
        <img src="<?php echo $qr_url; ?>" alt="QR Code" style="width: 75px; height: 75px; border-radius: 6px; border: 1px solid #e2e8f0;">
    </div>

    <div class="id-card-footer">
        <span>Authorized Circulation Card</span>
        <span>Barcode: USER:<?php echo $user['id']; ?></span>
    </div>
</div>

<script>
function handleSmartGoBack(e) {
    try {
        window.close();
    } catch(err) {}
    
    // If window is still open, try history.back if history exists, otherwise allow default href navigation
    if (window.history.length > 1) {
        history.back();
        return false;
    }
    return true; // Follow href to dashboard
}
</script>

</body>
</html>
