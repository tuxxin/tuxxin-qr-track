<?php
require 'vendor/autoload.php';
require 'config.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// --- SECURITY CHECK ---
// 1. Start Session to check if Admin is logged in
if (session_status() === PHP_SESSION_NONE) session_start();
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// 2. Get Input
$uuid = $_GET['id'] ?? '';
$token = $_GET['token'] ?? '';
$format = $_GET['format'] ?? 'png';

if (!$uuid) { http_response_code(400); die('No ID'); }

// 3. Authorization Logic
$authorized = false;

if ($isLoggedIn) {
    // Admin is logged into the dashboard -> Allow
    $authorized = true;
} elseif (!empty($token)) {
    // API User provided a token -> Check DB
    // Must match UUID and NOT be expired
    $stmt = $db->prepare("SELECT id FROM api_tokens WHERE token = ? AND product_uuid = ? AND expires_at > datetime('now') LIMIT 1");
    $stmt->execute([$token, $uuid]);
    if ($stmt->fetch()) {
        $authorized = true;
    }
}

if (!$authorized) {
    http_response_code(403);
    die('Forbidden: Invalid or expired access token. Login to dashboard or generate a new API token.');
}

// --- FETCH DATA ---
$stmt = $db->prepare("SELECT * FROM products WHERE uuid = :uuid");
$stmt->execute([':uuid' => $uuid]);
$item = $stmt->fetch();

if(!$item) { http_response_code(404); die('Invalid ID'); }

// --- CONTENT LOGIC ---
if ($item['type'] === 'wifi') {
    $j = json_decode($item['target_data'], true);
    $qrContent = "WIFI:S:{$j['ssid']};T:{$j['enc']};P:{$j['pass']};;";
} else {
    $qrContent = BASE_URL . "/p/" . $uuid;
}

// --- GENERATION ---
$options = new QROptions([
    'version'    => 7, 
    'outputType' => $format === 'png' ? QRCode::OUTPUT_IMAGE_PNG : QRCode::OUTPUT_IMAGE_JPG,
    'eccLevel'   => QRCode::ECC_H,
    'scale'      => 10,
    'imageBase64' => false,
    'imageTransparent' => ($format === 'png'),
]);

$qrcode = new QRCode($options);
$qrImage = $qrcode->render($qrContent);

// --- LOGO MERGING ---
$logoPath = ($item['logo_path'] && file_exists(LOGO_DIR . '/' . $item['logo_path'])) 
            ? LOGO_DIR . '/' . $item['logo_path'] 
            : null;

if ($logoPath) {
    $src = imagecreatefromstring($qrImage);
    $logo = imagecreatefromstring(file_get_contents($logoPath));
    
    if ($src && $logo) {
        $QR_width = imagesx($src);
        $QR_height = imagesy($src);
        $logo_width = imagesx($logo);
        $logo_height = imagesy($logo);
        
        $logo_qr_width = $QR_width / 5;
        $scale = $logo_width / $logo_qr_width;
        $logo_qr_height = $logo_height / $scale;
        
        imagecopyresampled($src, $logo, 
            (int)(($QR_width - $logo_qr_width) / 2), 
            (int)(($QR_height - $logo_qr_height) / 2), 
            0, 0, 
            (int)$logo_qr_width, (int)$logo_qr_height, 
            $logo_width, $logo_height
        );
        
        header('Content-Type: image/' . $format);
        if($format === 'png') imagepng($src);
        else imagejpeg($src);
        
        imagedestroy($src);
        imagedestroy($logo);
        exit;
    }
}

header('Content-Type: image/' . $format);
echo $qrImage;