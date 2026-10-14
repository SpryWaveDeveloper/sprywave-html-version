<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/phpmailer/Exception.php';
require_once __DIR__ . '/vendor/phpmailer/PHPMailer.php';
require_once __DIR__ . '/vendor/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}

$turnstileSecret = getenv('TURNSTILE_SECRET_KEY') ?: '[PLACEHOLDER_TURNSTILE_SECRET_KEY]';
$turnstileAction = 'contact';
$allowedHostnamesEnv = getenv('TURNSTILE_HOSTNAMES');
$allowedHostnames = $allowedHostnamesEnv !== false
    ? array_map('trim', explode(',', $allowedHostnamesEnv))
    : ['sprywave.com', 'www.sprywave.com'];
$recipient = 'info@sprywave.com';

$smtpHost       = getenv('SMTP_HOST')        ?: '[PLACEHOLDER_SMTP_HOST]';
$smtpPort       = (int) (getenv('SMTP_PORT') ?: 587);
$smtpUsername   = getenv('SMTP_USER')        ?: '[PLACEHOLDER_SMTP_USER]';
$smtpPassword   = getenv('SMTP_PASS')        ?: '[PLACEHOLDER_SMTP_PASS]';
$smtpEncryption = getenv('SMTP_ENCRYPTION')  ?: 'tls';
$smtpFromAddress= getenv('SMTP_FROM')        ?: 'no-reply@sprywave.com';
$smtpFromName   = getenv('SMTP_FROM_NAME')   ?: 'Sprywave Technologies';

function respond(bool $ok, string $error = '', int $status = 200): void
{
    http_response_code($status);
    echo json_encode($ok ? ['ok' => true] : ['ok' => false, 'error' => $error]);
    exit;
}

$token = $_POST['cf-turnstile-response'] ?? '';
if ($token === '' || strlen($token) > 2048) {
    respond(false, 'Please complete the verification check.', 400);
}

$verifyResponse = file_get_contents(
    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    false,
    stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'timeout' => 10,
            'content' => http_build_query([
                'secret'   => $turnstileSecret,
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]),
        ],
    ])
);

$verifyResult = $verifyResponse ? json_decode($verifyResponse, true) : null;
if (
    !is_array($verifyResult)
    || empty($verifyResult['success'])
    || ($verifyResult['action'] ?? '') !== $turnstileAction
    || !in_array($verifyResult['hostname'] ?? '', $allowedHostnames, true)
) {
    respond(false, 'Verification failed. Please try again.', 403);
}

$firstName = trim((string) ($_POST['first_name'] ?? ''));
$lastName  = trim((string) ($_POST['last_name']  ?? ''));
$email     = trim((string) ($_POST['email']      ?? ''));
$phone     = trim((string) ($_POST['phone']      ?? ''));
$role      = trim((string) ($_POST['role']       ?? ''));
$topic     = trim((string) ($_POST['topic']      ?? ''));
$message   = trim((string) ($_POST['message']    ?? ''));

if ($firstName === '' || $lastName === '' || $role === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Please fill in all required fields with a valid email address.', 400);
}

$allowedTopics = [
    'Software Development',
    'Virtual Teams',
    'Call Center Solutions',
    'ERP Solutions',
    'Consulting Services',
    'One of our products',
    'Something else',
];
if (!in_array($topic, $allowedTopics, true)) {
    $topic = 'Something else';
}

$fullName    = htmlspecialchars($firstName . ' ' . $lastName, ENT_QUOTES, 'UTF-8');
$emailEsc    = htmlspecialchars($email,   ENT_QUOTES, 'UTF-8');
$phoneEsc    = htmlspecialchars($phone !== '' ? $phone : '—', ENT_QUOTES, 'UTF-8');
$roleEsc     = htmlspecialchars($role,    ENT_QUOTES, 'UTF-8');
$topicEsc    = htmlspecialchars($topic,   ENT_QUOTES, 'UTF-8');
$messageEsc  = nl2br(htmlspecialchars($message !== '' ? $message : '—', ENT_QUOTES, 'UTF-8'));
$subject     = 'New enquiry from sprywave.com: ' . $firstName . ' ' . $lastName;
$year        = date('Y');

$htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$subject}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:40px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

        <!-- Header -->
        <tr>
          <td style="background:#1a2c5b;border-radius:12px 12px 0 0;padding:32px 40px;text-align:center;">
            <div style="font-size:22px;font-weight:700;color:#ffffff;letter-spacing:-0.3px;">
              Sprywave Technologies
            </div>
            <div style="font-size:13px;color:#93b4e8;margin-top:4px;">New Contact Form Enquiry</div>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="background:#ffffff;padding:36px 40px;">

            <p style="margin:0 0 24px;font-size:15px;color:#374151;line-height:1.6;">
              You have a new message from <strong style="color:#1a2c5b;">{$fullName}</strong> submitted via the contact form on <a href="https://sprywave.com" style="color:#2563eb;text-decoration:none;">sprywave.com</a>.
            </p>

            <!-- Details table -->
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:24px;">
              <tr style="background:#f9fafb;">
                <td style="padding:12px 16px;font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;width:140px;border-bottom:1px solid #e5e7eb;">Name</td>
                <td style="padding:12px 16px;font-size:14px;color:#111827;border-bottom:1px solid #e5e7eb;">{$fullName}</td>
              </tr>
              <tr>
                <td style="padding:12px 16px;font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #e5e7eb;">Email</td>
                <td style="padding:12px 16px;font-size:14px;border-bottom:1px solid #e5e7eb;"><a href="mailto:{$emailEsc}" style="color:#2563eb;text-decoration:none;">{$emailEsc}</a></td>
              </tr>
              <tr style="background:#f9fafb;">
                <td style="padding:12px 16px;font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #e5e7eb;">Phone</td>
                <td style="padding:12px 16px;font-size:14px;color:#111827;border-bottom:1px solid #e5e7eb;">{$phoneEsc}</td>
              </tr>
              <tr>
                <td style="padding:12px 16px;font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid #e5e7eb;">Role</td>
                <td style="padding:12px 16px;font-size:14px;color:#111827;border-bottom:1px solid #e5e7eb;">{$roleEsc}</td>
              </tr>
              <tr style="background:#f9fafb;">
                <td style="padding:12px 16px;font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Interested In</td>
                <td style="padding:12px 16px;">
                  <span style="display:inline-block;background:#dbeafe;color:#1d4ed8;font-size:12px;font-weight:600;padding:3px 10px;border-radius:100px;">{$topicEsc}</span>
                </td>
              </tr>
            </table>

            <!-- Message -->
            <div style="background:#f9fafb;border:1px solid #e5e7eb;border-left:4px solid #1a2c5b;border-radius:0 8px 8px 0;padding:16px 20px;margin-bottom:28px;">
              <div style="font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">Message</div>
              <div style="font-size:14px;color:#374151;line-height:1.7;">{$messageEsc}</div>
            </div>

            <!-- CTA -->
            <table cellpadding="0" cellspacing="0">
              <tr>
                <td style="border-radius:8px;background:#1a2c5b;">
                  <a href="mailto:{$emailEsc}?subject=Re: Your enquiry to Sprywave Technologies"
                     style="display:inline-block;padding:12px 24px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;letter-spacing:0.2px;">
                    Reply to {$fullName} →
                  </a>
                </td>
              </tr>
            </table>

          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#f4f6f9;border-top:1px solid #e5e7eb;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;">
            <p style="margin:0;font-size:12px;color:#9ca3af;">
              This email was sent automatically from the contact form at
              <a href="https://sprywave.com" style="color:#6b7280;">sprywave.com</a>.
              &copy; {$year} Sprywave Technologies Limited. Accra, Ghana.
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

// Plain-text fallback
$plainBody = "New enquiry from sprywave.com\n"
    . "==============================\n\n"
    . "Name:         {$firstName} {$lastName}\n"
    . "Email:        {$email}\n"
    . "Phone:        " . ($phone !== '' ? $phone : '—') . "\n"
    . "Role:         {$role}\n"
    . "Interested in: {$topic}\n\n"
    . "Message:\n" . ($message !== '' ? $message : '—') . "\n\n"
    . "---\nsprywave.com contact form";

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->Port       = $smtpPort;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUsername;
    $mail->Password   = $smtpPassword;
    $mail->SMTPSecure = $smtpEncryption === 'ssl'
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;

    $mail->setFrom($smtpFromAddress, $smtpFromName);
    $mail->addAddress($recipient);
    $mail->addReplyTo($email, "{$firstName} {$lastName}");

    $mail->Subject  = $subject;
    $mail->isHTML(true);
    $mail->Body     = $htmlBody;
    $mail->AltBody  = $plainBody;

    $mail->send();
} catch (PHPMailerException $e) {
    respond(false, 'We could not send your message right now. Please email info@sprywave.com directly.', 502);
}

respond(true);