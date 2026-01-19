<?php
require 'config.php';
require_auth();

header('Content-Type: application/json');

$uuid = $_GET['uuid'] ?? '';
if (!$uuid) { echo json_encode([]); exit; }

// Fetch Scans
$stmt = $db->prepare("SELECT ip_address, user_agent, scanned_at, scan_status FROM scans WHERE product_uuid = ? ORDER BY scanned_at DESC");
$stmt->execute([$uuid]);
$scans = $stmt->fetchAll();

// Process Data
foreach ($scans as &$scan) {
    // --- TIMEZONE FIX ---
    // 1. Create DateTime from DB time (which is UTC by default in SQLite)
    $dt = new DateTime($scan['scanned_at'], new DateTimeZone('UTC'));
    
    // 2. Convert to the timezone defined in config.php
    $dt->setTimezone(new DateTimeZone(TIMEZONE));
    
    // 3. Save back the formatted string
    $scan['scanned_at'] = $dt->format('Y-m-d H:i:s');
    
    // --- GEO LOCATION ---
    $ip = $scan['ip_address'];
    
    // Skip local/private IPs to avoid API errors
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $scan['geo'] = ['city' => 'Local', 'region' => 'LAN', 'country' => 'Local Network', 'isp' => 'Private'];
        continue;
    }

    // Call IP-API
    $ch = curl_init("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,isp,org");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2); 
    $response = curl_exec($ch);
    curl_close($ch);

    $geo = json_decode($response, true);

    if ($geo && $geo['status'] === 'success') {
        $scan['geo'] = [
            'city' => $geo['city'],
            'region' => $geo['regionName'],
            'country' => $geo['country'],
            'isp' => $geo['isp']
        ];
    } else {
        $scan['geo'] = ['city' => 'Unknown', 'region' => '', 'country' => '', 'isp' => ''];
    }
}

echo json_encode($scans);