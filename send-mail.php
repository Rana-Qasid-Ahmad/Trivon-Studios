<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');          // restrict to your domain in production
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// ── Config ──────────────────────────────────────────
$TO      = 'hello@trivonstudios.com';         // your inbox
$FROM    = 'noreply@trivonstudios.com';       // sender (must match your domain)
$SUBJECT = 'New Contact Form Submission';
// ────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Read and decode JSON body
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON.']);
    exit;
}

// Sanitize
function clean($val) {
    return htmlspecialchars(strip_tags(trim((string)($val ?? ''))));
}

$name    = clean($data['name']);
$email   = clean($data['email']);
$phone   = clean($data['phone']);
$service = clean($data['service']);
$budget  = clean($data['budget']);
$message = clean($data['message']);
$page    = clean($data['page']);
$sent_at = clean($data['sent_at']);

// Required fields
if (!$name || !$email || !$message) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Name, email, and message are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
    exit;
}

// Build email body
$body = "
New contact form submission from GrowthLab\n
────────────────────────────────
Name:     $name
Email:    $email
Phone:    " . ($phone ?: '—') . "
Service:  " . ($service ?: '—') . "
Budget:   " . ($budget ?: '—') . "
Page:     $page
Sent at:  $sent_at
────────────────────────────────
Message:

$message
";

$headers  = "From: $FROM\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$sent = mail($TO, $SUBJECT . ' — ' . $name, $body, $headers);

if ($sent) {
    echo json_encode(['success' => true, 'message' => 'Message sent successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Mail server error. Please email us directly.']);
}
?>