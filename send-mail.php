<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/*
|--------------------------------------------------------------------------
| PHPMailer Includes (manual install version for Hostinger)
|--------------------------------------------------------------------------
| Upload PHPMailer folder like:
| /PHPMailer/src/Exception.php
| /PHPMailer/src/PHPMailer.php
| /PHPMailer/src/SMTP.php
*/

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

/*
|--------------------------------------------------------------------------
| Config
|--------------------------------------------------------------------------
*/

$TO_EMAIL   = 'hello@trivonstudios.com';
$FROM_EMAIL = 'hello@trivonstudios.com'; // MUST be same as SMTP user
$FROM_NAME  = 'Trivon Studios';

/*
|--------------------------------------------------------------------------
| Only POST allowed
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

/*
|--------------------------------------------------------------------------
| Read JSON input
|--------------------------------------------------------------------------
*/

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

/*
|--------------------------------------------------------------------------
| Helper sanitize
|--------------------------------------------------------------------------
*/

function clean($v) {
    return htmlspecialchars(strip_tags(trim($v ?? '')));
}

/*
|--------------------------------------------------------------------------
| Input fields
|--------------------------------------------------------------------------
*/

$name    = clean($data['name']);
$email   = clean($data['email']);
$phone   = clean($data['phone']);
$service = clean($data['service']);
$budget  = clean($data['budget']);
$message = clean($data['message']);
$page    = clean($data['page']);
$sent_at = clean($data['sent_at']);

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

if (!$name || !$email || !$message) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Name, email, and message required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid email']);
    exit;
}

/*
|--------------------------------------------------------------------------
| Send Email via SMTP
|--------------------------------------------------------------------------
*/

$mail = new PHPMailer(true);

try {
    // SMTP Settings (Hostinger)
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $FROM_EMAIL;
    $mail->Password   = '1bCp0vzGr^'; // IMPORTANT
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Sender
    $mail->setFrom($FROM_EMAIL, $FROM_NAME);
    $mail->addReplyTo($email, $name);
    $mail->addAddress($TO_EMAIL);

    // Content
    $mail->isHTML(false);
    $mail->Subject = "New Contact Form — $name";

    $mail->Body =
"New Contact Form Submission

-----------------------------
Name: $name
Email: $email
Phone: $phone
Service: $service
Budget: $budget
Page: $page
Sent At: $sent_at
-----------------------------

Message:
$message
";

    $mail->send();

    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully'
    ]);

} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => "Mailer Error: {$mail->ErrorInfo}"
    ]);
}