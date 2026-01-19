<?php
require 'config.php';

$uuid = $_GET['id'] ?? null;
if (!$uuid) { http_response_code(404); die("QR Code not found."); }

$stmt = $db->prepare("SELECT * FROM products WHERE uuid = :uuid LIMIT 1");
$stmt->execute([':uuid' => $uuid]);
$product = $stmt->fetch();

if (!$product) { http_response_code(404); die("Invalid ID"); }

// --- IP DETECTION LOGIC ---
$ip = 'Unknown';

if (defined('USE_CLOUDFLARE_TUNNEL') && USE_CLOUDFLARE_TUNNEL === true) {
    // Tunnel Mode: Trust X-Forwarded-For
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ipList[0]);
    } else {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? 'Unknown';
    }
} else {
    // Standard Mode: Use Direct Connection IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
}

$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// --- DETERMINE STATUS ---
$status = 'success';
if (!$product['is_active'] || $product['is_deleted']) {
    $status = 'blocked';
}

// --- LOG SCAN ---
$db->prepare("INSERT INTO scans (product_uuid, ip_address, user_agent, scan_status) VALUES (?, ?, ?, ?)")
   ->execute([$uuid, $ip, $ua, $status]);

// --- HANDLE DISABLED STATE ---
if ($status === 'blocked') {
    $disabledPage = THEME_PATH . '/qr-disabled.php';
    
    // Check if the file actually exists before including it
    if (file_exists($disabledPage)) {
        include $disabledPage;
    } else {
        // SAFETY FALLBACK: If the file is missing or unreadable, show this HTML instead of a blank page.
        echo "<!DOCTYPE html>
        <html style='background:#121212;color:#e0e0e0;font-family:sans-serif;height:100vh;display:flex;align-items:center;justify-content:center;'>
            <div style='text-align:center;'>
                <h1 style='color:#ff6600'>Link Disabled</h1>
                <p>This QR code is currently inactive.</p>
                <small style='color:#666'>Error: Theme file not found at $disabledPage</small>
            </div>
        </html>";
    }
    exit;
}

// --- FORWARDING ---
$type = $product['type'];
$data = $product['target_data'];

switch ($type) {
    case 'url':
    case 'social':
        header("Location: " . $data);
        break;

    case 'phone':
        header("Location: tel:" . $data);
        break;

    case 'map':
        header("Location: https://www.google.com/maps/search/?api=1&query=" . urlencode($data));
        break;

    case 'sms':
        $j = json_decode($data, true);
        $body = rawurlencode($j['body']);
        header("Location: sms:{$j['phone']}?&body={$body}");
        break;

    case 'email':
        $j = json_decode($data, true);
        $sub = rawurlencode($j['subject']);
        $body = rawurlencode($j['body']);
        header("Location: mailto:{$j['email']}?subject={$sub}&body={$body}");
        break;

    case 'vcard':
        $v = json_decode($data, true);
        $vcf = "BEGIN:VCARD\r\nVERSION:3.0\r\n";
        $vcf .= "N:{$v['lname']};{$v['fname']};;;\r\n";
        $vcf .= "FN:{$v['fname']} {$v['lname']}\r\n";
        $vcf .= "ORG:{$v['company']}\r\n";
        $vcf .= "TEL;TYPE=CELL:{$v['phone']}\r\n";
        $vcf .= "EMAIL:{$v['email']}\r\n";
        $vcf .= "END:VCARD";
        header('Content-Type: text/x-vcard');
        header('Content-Disposition: attachment; filename="'.$product['title'].'.vcf"');
        echo $vcf;
        break;
}
exit;
