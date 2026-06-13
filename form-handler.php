<?php
/**
 * Dubai Metal Aluminium
 * Form Handler: Lead form submission
 * - Validates input server-side
 * - Saves to SQLite database
 * - Sends notification email to info@dubaimetalaluminium.com
 * - Sends auto-reply to the customer
 */

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// ===== CSRF / Honeypot Spam Protection =====
$honeypot = isset($_POST['honeypot']) ? trim($_POST['honeypot']) : '';
if ($honeypot !== '') {
    http_response_code(200);
    echo json_encode(['success' => true]); // Silent drop
    exit;
}

// ===== Rate limiting (IP-based, simple file lock) =====
$rateLimitFile = sys_get_temp_dir() . '/dma_rl_' . md5($_SERVER['REMOTE_ADDR'] ?? 'anon');
$rateWindow    = 60; // seconds
if (file_exists($rateLimitFile)) {
    $lastTime = (int) file_get_contents($rateLimitFile);
    if ((time() - $lastTime) < $rateWindow) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many requests. Please wait and try again.']);
        exit;
    }
}
file_put_contents($rateLimitFile, time());

// ===== Input Sanitization & Validation =====
function sanitize($val) {
    return htmlspecialchars(strip_tags(trim($val ?? '')), ENT_QUOTES, 'UTF-8');
}

$fullName    = sanitize($_POST['fullName']   ?? '');
$companyName = sanitize($_POST['companyName'] ?? '');
$city        = sanitize($_POST['city']        ?? '');
$phone       = sanitize($_POST['phone']       ?? '');
$email       = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$inquiryType = sanitize($_POST['inquiryType'] ?? '');
$message     = sanitize($_POST['message']     ?? '');

$validInquiryTypes = ['product-quote', 'dealership', 'bulk-order', 'custom-fabrication', 'general'];

$errors = [];
if (strlen($fullName) < 2)                              $errors[] = 'Full name is required.';
if (strlen($city) < 2)                                  $errors[] = 'City is required.';
if (!preg_match('/^(\+92|0092|0)[0-9]{9,11}$/', $phone)) $errors[] = 'Invalid phone number.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))         $errors[] = 'Invalid email address.';
if (!in_array($inquiryType, $validInquiryTypes))        $errors[] = 'Invalid inquiry type.';
if (strlen($message) < 10)                              $errors[] = 'Message too short.';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

$inquiryLabels = [
    'product-quote'      => 'Product Price Quote',
    'dealership'         => 'Dealership Application',
    'bulk-order'         => 'Bulk / Wholesale Order',
    'custom-fabrication' => 'Custom Fabrication',
    'general'            => 'General Inquiry',
];
$inquiryLabel = $inquiryLabels[$inquiryType] ?? $inquiryType;

// ===== Save to SQLite Database =====
$dbDir  = __DIR__ . '/data';
$dbFile = $dbDir . '/leads.db';

if (!is_dir($dbDir)) {
    mkdir($dbDir, 0700, true);
}

try {
    $db = new PDO('sqlite:' . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE IF NOT EXISTS leads (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name    TEXT NOT NULL,
        company_name TEXT,
        city         TEXT NOT NULL,
        phone        TEXT NOT NULL,
        email        TEXT NOT NULL,
        inquiry_type TEXT NOT NULL,
        message      TEXT NOT NULL,
        ip_address   TEXT,
        submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $db->prepare("INSERT INTO leads (full_name, company_name, city, phone, email, inquiry_type, message, ip_address)
                          VALUES (:full_name, :company_name, :city, :phone, :email, :inquiry_type, :message, :ip_address)");
    $stmt->execute([
        ':full_name'    => $fullName,
        ':company_name' => $companyName,
        ':city'         => $city,
        ':phone'        => $phone,
        ':email'        => $email,
        ':inquiry_type' => $inquiryType,
        ':message'      => $message,
        ':ip_address'   => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    ]);
} catch (Exception $e) {
    // DB failure should not block email sending
    error_log('DMA DB Error: ' . $e->getMessage());
}

// ===== Email Configuration =====
$companyEmail = 'info@dubaimetalaluminium.com';
$companyName  = 'Dubai Metal Aluminium';

// ===== Notification Email to Company =====
$toCompany      = $companyEmail;
$subjectCompany = "New Lead: [{$inquiryLabel}] from {$fullName} – {$city}";
$headersCompany = implode("\r\n", [
    "MIME-Version: 1.0",
    "Content-Type: text/html; charset=UTF-8",
    "From: {$companyName} Website <{$companyEmail}>",
    "Reply-To: {$email}",
    "X-Mailer: PHP/" . PHP_VERSION,
]);

$bodyCompany = "<!DOCTYPE html>
<html lang='en'>
<head><meta charset='UTF-8'><style>
  body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:0}
  .wrap{max-width:620px;margin:30px auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1)}
  .header{background:linear-gradient(135deg,#1a5faa,#2179d3);padding:30px;text-align:center}
  .header h1{color:#fff;margin:0;font-size:22px}
  .header p{color:rgba(255,255,255,0.8);margin:8px 0 0;font-size:14px}
  .body{padding:30px}
  .field{margin-bottom:18px}
  .label{font-size:11px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:#6b7fa0;margin-bottom:4px}
  .value{font-size:15px;color:#0a1628;padding:12px 16px;background:#f0f4fb;border-radius:8px;border-left:3px solid #2179d3}
  .badge{display:inline-block;padding:6px 14px;border-radius:20px;background:#2179d3;color:#fff;font-size:12px;font-weight:700}
  .footer{background:#0a1628;padding:20px;text-align:center;color:rgba(255,255,255,0.5);font-size:12px}
</style></head>
<body>
<div class='wrap'>
  <div class='header'>
    <h1>📨 New Inquiry Received</h1>
    <p>Dubai Metal Aluminium Website</p>
  </div>
  <div class='body'>
    <div class='field'><div class='label'>Inquiry Type</div><div class='value'><span class='badge'>{$inquiryLabel}</span></div></div>
    <div class='field'><div class='label'>Full Name</div><div class='value'>{$fullName}</div></div>"
    . (!empty($companyName) ? "<div class='field'><div class='label'>Company</div><div class='value'>{$companyName}</div></div>" : "") .
    "<div class='field'><div class='label'>City</div><div class='value'>{$city}</div></div>
    <div class='field'><div class='label'>Phone</div><div class='value'><a href='tel:{$phone}'>{$phone}</a></div></div>
    <div class='field'><div class='label'>Email</div><div class='value'><a href='mailto:{$email}'>{$email}</a></div></div>
    <div class='field'><div class='label'>Message</div><div class='value'>" . nl2br($message) . "</div></div>
  </div>
  <div class='footer'>{$companyName} | Canal Road, 47 Pull, Sargodha, Pakistan</div>
</div>
</body></html>";

mail($toCompany, $subjectCompany, $bodyCompany, $headersCompany);

// ===== Auto-Reply to Customer =====
$subjectReply = "Thank you for contacting Dubai Metal Aluminium!";
$headersReply = implode("\r\n", [
    "MIME-Version: 1.0",
    "Content-Type: text/html; charset=UTF-8",
    "From: {$companyName} <{$companyEmail}>",
    "Reply-To: {$companyEmail}",
    "X-Mailer: PHP/" . PHP_VERSION,
]);

$bodyReply = "<!DOCTYPE html>
<html lang='en'>
<head><meta charset='UTF-8'><style>
  body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:0}
  .wrap{max-width:620px;margin:30px auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1)}
  .header{background:linear-gradient(135deg,#1a5faa,#2179d3);padding:36px 30px;text-align:center}
  .header img{width:90px;border-radius:50%;margin-bottom:12px}
  .header h1{color:#fff;margin:0;font-size:22px;font-weight:800}
  .header p{color:rgba(255,255,255,0.85);margin:6px 0 0;font-size:14px}
  .body{padding:36px 30px}
  .greeting{font-size:17px;color:#0a1628;font-weight:700;margin-bottom:16px}
  .text{font-size:14.5px;color:#44556f;line-height:1.8;margin-bottom:16px}
  .summary{background:#f0f4fb;border-left:4px solid #2179d3;padding:16px 20px;border-radius:0 8px 8px 0;margin:22px 0}
  .summary p{margin:0 0 6px;font-size:13.5px;color:#44556f}
  .summary strong{color:#0a1628}
  .cta-btn{display:block;width:max-content;margin:28px auto;padding:14px 32px;background:linear-gradient(135deg,#1a5faa,#2179d3);color:#fff;border-radius:8px;font-size:15px;font-weight:700;text-decoration:none;text-align:center}
  .contact-row{text-align:center;margin:18px 0}
  .contact-row a{color:#2179d3;font-size:14px;text-decoration:none;margin:0 10px}
  .footer{background:#0a1628;padding:22px;text-align:center;color:rgba(255,255,255,0.5);font-size:12px;line-height:1.7}
  .footer a{color:rgba(255,255,255,0.6)}
</style></head>
<body>
<div class='wrap'>
  <div class='header'>
    <h1>🏭 Dubai Metal Aluminium</h1>
    <p>Pakistan's Premier Metal & Aluminium Supplier</p>
  </div>
  <div class='body'>
    <div class='greeting'>Dear {$fullName},</div>
    <p class='text'>Thank you for reaching out to <strong>Dubai Metal Aluminium</strong>! We have received your inquiry and our team will contact you within <strong>24 hours</strong> with a detailed response.</p>
    <p class='text'>Here is a summary of what you submitted:</p>
    <div class='summary'>
      <p><strong>Inquiry Type:</strong> {$inquiryLabel}</p>
      <p><strong>City:</strong> {$city}</p>
      <p><strong>Phone:</strong> {$phone}</p>
      <p><strong>Message:</strong> " . nl2br($message) . "</p>
    </div>
    <p class='text'>In the meantime, feel free to reach us directly:</p>
    <div class='contact-row'>
      <a href='tel:+923008702149'>📞 +92 300 8702149</a>
      <a href='tel:+923016702149'>📞 +92 301 6702149</a>
      <a href='https://wa.me/923008702149'>💬 WhatsApp</a>
    </div>
    <a href='https://wa.me/923008702149?text=Hello%2C%20I%20submitted%20an%20inquiry%20on%20your%20website.' class='cta-btn'>💬 Chat on WhatsApp</a>
    <p class='text' style='text-align:center;color:#6b7fa0;font-size:13px;'>We look forward to serving you!</p>
  </div>
  <div class='footer'>
    <strong style='color:rgba(255,255,255,0.8)'>{$companyName}</strong><br />
    Canal Road, 47 Pull, Sargodha, Punjab, Pakistan<br />
    <a href='mailto:{$companyEmail}'>{$companyEmail}</a>
  </div>
</div>
</body></html>";

mail($email, $subjectReply, $bodyReply, $headersReply);

// ===== Success Response =====
echo json_encode([
    'success' => true,
    'message' => 'Your inquiry has been received. We will contact you within 24 hours!'
]);
exit;
