<?php
// includes/config.php - Enterprise Config, .env Loader, CSRF Engine, and Security Layer
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Compatibility Polyfills for PHP 7.x / 8.x
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return (string)$needle !== '' && strpos($haystack, $needle) !== false;
    }
}

// 1. .env Environment Variable Loader
function load_env_file($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) return;
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}
load_env_file(__DIR__ . '/../.env');

// Database Credentials from Environment or Defaults
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'lms');
define('DB_PORT', intval(getenv('DB_PORT') ?: 3306));

// Establish MySQLi connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_error) {
    error_log("Database Connection Error: " . $conn->connect_error);
    die("A secure database connection error occurred. Please check server logs.");
}

$conn->set_charset("utf8mb4");

// --- 2. CSRF Protection Engine ---
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// Site Configuration Helper
function get_site_settings($conn) {
    $sql = "SELECT * FROM site_settings LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return $row;
    }
    return [
        'library_name' => 'Genious Library',
        'hero_title' => 'Welcome to Genious Library',
        'hero_subtitle' => 'Explore, Discover, and Borrow Your Favorite Books',
        'contact_email' => 'info@geniouslibrary.com',
        'contact_phone' => '+91 98765 43210',
        'address' => 'Opp. University Campus, Near City Library, Ahmedabad, Gujarat',
        'fine_per_day' => 5.00,
        'loan_days' => 14
    ];
}

// Flash Message Helpers
function set_flash($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

function display_flash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        $type = htmlspecialchars($flash['type']);
        $icon = ($type === 'success') ? 'fa-circle-check' : (($type === 'danger') ? 'fa-triangle-exclamation' : 'fa-circle-info');
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                <i class='fa-solid {$icon} me-2'></i> " . htmlspecialchars($flash['message']) . "
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
        unset($_SESSION['flash_message']);
    }
}

// Authentication & Access Controls
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function is_user_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_admin() {
    if (!is_admin_logged_in()) {
        set_flash('danger', 'Please login as Admin to access this page.');
        header("Location: admin_login.php");
        exit();
    }
}

function require_user() {
    if (!is_user_logged_in()) {
        set_flash('danger', 'Please login to access your account.');
        header("Location: userlogin.php");
        exit();
    }
}

function get_logged_admin($conn) {
    if (!is_admin_logged_in()) return null;
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function get_logged_user($conn) {
    if (!is_user_logged_in()) return null;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Secure File MIME & Size Validator
function validate_uploaded_file($file, $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'], $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'], $max_size_mb = 5) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'No file uploaded or upload error occurred.'];
    }

    if ($file['size'] > ($max_size_mb * 1024 * 1024)) {
        return ['valid' => false, 'error' => "File size exceeds the {$max_size_mb}MB limit."];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_extensions)) {
        return ['valid' => false, 'error' => 'Invalid file extension. Allowed: ' . implode(', ', $allowed_extensions)];
    }

    // Verify MIME type using fileinfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_mimes)) {
        return ['valid' => false, 'error' => 'Invalid file MIME type (' . htmlspecialchars($mime) . '). Upload rejected.'];
    }

    return ['valid' => true, 'extension' => $ext, 'mime' => $mime];
}

// Utility: Sanitize Input
function sanitize($conn, $data) {
    return mysqli_real_escape_string($conn, trim($data));
}

// Utility: Calculate Late Fine
function calculate_fine($due_date, $return_date = null, $fine_rate = 5.0) {
    $today = $return_date ? new DateTime($return_date) : new DateTime();
    $due = new DateTime($due_date);
    
    if ($today > $due) {
        $interval = $today->diff($due);
        $days_overdue = $interval->days;
        return $days_overdue * $fine_rate;
    }
    return 0;
}
?>
