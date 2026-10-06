<?php
// api_quick_help.php - Instant Librarian Help & Support Ticket Endpoint
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json');

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$topic = trim($_POST['topic'] ?? 'General Inquiry');
$message_text = trim($_POST['message'] ?? '');

if (is_user_logged_in()) {
    $u = get_logged_user($conn);
    if (empty($name)) $name = $u['username'];
    if (empty($email)) $email = $u['email'];
}

if (empty($name) || empty($message_text)) {
    echo json_encode(['status' => 'error', 'message' => 'Please provide your name and question.']);
    exit();
}

$subject = "[Quick Help] " . $topic;
$stmt = $conn->prepare("INSERT INTO messages (name, email, subject, message, status) VALUES (?, ?, ?, ?, 'unread')");
$stmt->bind_param("ssss", $name, $email, $subject, $message_text);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Your message has been sent directly to the librarian desk! We will reply promptly.'
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send message: ' . $conn->error]);
}
exit();
