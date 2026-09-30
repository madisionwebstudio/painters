<?php
/**
 * Roll n' Brush Painting — mailer.php
 * Handles the estimate form (AJAX via fetch, or plain POST fallback).
 * Sends one email with up to 3 photo attachments using PHP mail().
 *
 * Before launch:
 *   1. Set TO_EMAIL and FROM_EMAIL below.
 *   2. FROM_EMAIL should be an address on your own domain (SPF/DKIM),
 *      or deliverability will suffer. For best results swap mail() for SMTP (PHPMailer).
 *   3. Make sure php.ini allows the upload size: upload_max_filesize >= 8M, post_max_size >= 26M.
 */

declare(strict_types=1);

const TO_EMAIL   = '[EMAIL]';                        // [PLACEHOLDER] where leads are delivered
const FROM_EMAIL = 'no-reply@YOUR-DOMAIN.com';       // [PLACEHOLDER] must be on your domain
const BIZ_NAME   = "Roll n' Brush Painting";
const MAX_FILES  = 3;
const MAX_BYTES  = 8 * 1024 * 1024;
const MIN_SECONDS = 3;                                // faster than this = likely a bot
const RATE_LIMIT_SECONDS = 30;

$ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'];
$TOWNS    = ['Fitchburg', 'Madison', 'Verona', 'Middleton', 'Oregon', 'Monona', 'McFarland', 'Other'];
$SERVICES = ['Interior Painting', 'Exterior Painting', 'Cabinet Refinishing', 'Deck Staining', 'Drywall Repair', 'Not sure yet'];

$isAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch');

function respond(bool $ok, string $error = ''): void {
    global $isAjax;
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($ok ? 200 : 400);
        echo json_encode($ok ? ['ok' => true] : ['ok' => false, 'error' => $error]);
    } else {
        $page = preg_replace('/[^a-z\-]/', '', $_POST['page'] ?? '');
        $path = ['home' => '/', 'services' => '/services', 'our-work' => '/our-work'][$page] ?? '/';
        header('Location: ' . $path . '?sent=' . ($ok ? '1' : '0') . '#estimate', true, 303);
    }
    exit;
}

function clean(string $v, int $max): string {
    $v = trim(str_replace(["\r", "\0"], '', $v));
    return mb_substr(strip_tags($v), 0, $max);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed');
}

/* ---- Spam checks ---- */
if (!empty($_POST['website'])) respond(true);                    // honeypot: pretend success
$ts = (int)($_POST['ts'] ?? 0);
if ($ts > 0 && (time() - intdiv($ts, 1000)) < MIN_SECONDS) respond(true);

session_start();
if (isset($_SESSION['last_submit']) && (time() - $_SESSION['last_submit']) < RATE_LIMIT_SECONDS) {
    respond(false, 'Please wait a moment before sending again.');
}

/* ---- Validate fields ---- */
$name    = clean($_POST['name'] ?? '', 80);
$phone   = clean($_POST['phone'] ?? '', 30);
$email   = clean($_POST['email'] ?? '', 120);
$town    = clean($_POST['town'] ?? '', 40);
$service = clean($_POST['service'] ?? '', 60);
$message = clean($_POST['message'] ?? '', 2000);
$page    = clean($_POST['page'] ?? '', 20);

if (mb_strlen($name) < 2)                               respond(false, 'Please enter your name.');
if (strlen(preg_replace('/\D/', '', $phone)) < 10)      respond(false, 'Please enter a valid phone number.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL))         respond(false, 'Please enter a valid email address.');
if ($town !== '' && !in_array($town, $TOWNS, true))       $town = 'Other';
if ($service !== '' && !in_array($service, $SERVICES, true)) $service = 'Not sure yet';

/* ---- Attachments ---- */
$attachments = [];
if (!empty($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
    $count = 0;
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    foreach ($_FILES['photos']['name'] as $i => $origName) {
        $err = $_FILES['photos']['error'][$i];
        if ($err === UPLOAD_ERR_NO_FILE) continue;
        if ($err !== UPLOAD_ERR_OK)       respond(false, 'One of your photos could not be uploaded. Try a smaller file.');
        if (++$count > MAX_FILES)         respond(false, 'Please attach up to ' . MAX_FILES . ' photos.');
        $tmp = $_FILES['photos']['tmp_name'][$i];
        if (!is_uploaded_file($tmp))      respond(false, 'Upload failed.');
        if (filesize($tmp) > MAX_BYTES)   respond(false, 'Each photo must be 8 MB or smaller.');
        $mime = $finfo->file($tmp) ?: '';
        if (!in_array($mime, $ALLOWED_MIME, true)) respond(false, 'Photos must be JPG, PNG, WebP or HEIC.');
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($origName)) ?: ('photo' . $count);
        $attachments[] = ['name' => $safe, 'mime' => $mime, 'data' => file_get_contents($tmp)];
    }
}

/* ---- Build email ---- */
$subject = 'New estimate request: ' . ($service ?: 'Painting') . ' — ' . ($town ?: 'Town not given') . ' — ' . $name;
$subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

$body  = "New estimate request from the website\n";
$body .= "======================================\n\n";
$body .= "Name:     $name\n";
$body .= "Phone:    $phone\n";
$body .= "Email:    $email\n";
$body .= "Town:     " . ($town ?: '-') . "\n";
$body .= "Service:  " . ($service ?: '-') . "\n";
$body .= "Page:     " . ($page ?: '-') . "\n";
$body .= "Photos:   " . count($attachments) . "\n\n";
$body .= "Message:\n" . ($message ?: '-') . "\n\n";
$body .= "--\nSent " . date('Y-m-d H:i T') . " from IP " . ($_SERVER['REMOTE_ADDR'] ?? '-') . "\n";

$boundary = 'b_' . bin2hex(random_bytes(12));
$headers  = [];
$headers[] = 'From: ' . BIZ_NAME . ' Website <' . FROM_EMAIL . '>';
$headers[] = 'Reply-To: ' . str_replace(['<', '>', ','], '', $name) . ' <' . $email . '>';
$headers[] = 'MIME-Version: 1.0';

if ($attachments) {
    $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
    $mime  = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . $body . "\r\n";
    foreach ($attachments as $a) {
        $mime .= "--$boundary\r\n";
        $mime .= 'Content-Type: ' . $a['mime'] . '; name="' . $a['name'] . "\"\r\n";
        $mime .= "Content-Transfer-Encoding: base64\r\n";
        $mime .= 'Content-Disposition: attachment; filename="' . $a['name'] . "\"\r\n\r\n";
        $mime .= chunk_split(base64_encode($a['data'])) . "\r\n";
    }
    $mime .= "--$boundary--";
    $payload = $mime;
} else {
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $payload = $body;
}

$sent = @mail(TO_EMAIL, $subject, $payload, implode("\r\n", $headers), '-f' . FROM_EMAIL);

if (!$sent) respond(false, 'Sorry, your request could not be sent. Please call (608) 440-4428.');

$_SESSION['last_submit'] = time();
respond(true);
