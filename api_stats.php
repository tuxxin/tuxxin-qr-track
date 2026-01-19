<?php
require 'config.php';
require_auth();

header('Content-Type: application/json');

$uuid = $_GET['uuid'] ?? '';
if (!$uuid) { echo json_encode([]); exit; }

// Fetch Scans including the new cached columns
$stmt = $db->prepare("SELECT id, ip_address, user_agent, scanned_at, scan_status, geo_city, geo_region, geo_country, geo_isp FROM scans WHERE product_uuid = ? ORDER BY scanned_at DESC");
$stmt->execute([$uuid]);
$scans = $stmt->fetchAll();

$updates_made = false;

// Process Data
foreach ($scans as &$scan) {
    // 1. Timezone Fix
    $dt = new DateTime($scan['scanned_at'], new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone(TIMEZONE));
    $scan['scanned_at'] = $dt->format('Y-m-d H:i:s');
    
    // 2. Geo Caching Logic
    // If we already have the city in the DB, use it (Fast!)
    if (!empty($scan['geo_city']) || $scan['geo_city'] === 'Local') {
        $scan['geo'] = [
            'city' => $scan['geo_city'],
            'region' => $scan['geo_region'],
            'country' => $scan['geo_country'],
            'isp' => $scan['geo_isp']
        ];
        continue; // Skip API call
    }

    // Data missing? Call API (Slow, but only once per IP/Scan)
    $ip = $scan['ip_address'];
    
    // Check for private IPs
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $geoData = ['city' => 'Local', 'region' => 'LAN', 'country' => 'Local Network', 'isp' => 'Private'];
    } else {
        // Call IP-API
        $ch = curl_init("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,isp");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2); 
        $response = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($response, true);
        
        if ($json && $json['status'] === 'success') {
            $geoData = [
                'city' => $json['city'],
                'region' => $json['regionName'],
                'country' => $json['country'],
                'isp' => $json['isp']
            ];
        } else {
            $geoData = ['city' => 'Unknown', 'region' => '', 'country' => '', 'isp' => ''];
        }
    }

    // Save back to Database so we never have to lookup this specific scan ID again
    $upd = $db->prepare("UPDATE scans SET geo_city=?, geo_region=?, geo_country=?, geo_isp=? WHERE id=?");
    $upd->execute([$geoData['city'], $geoData['region'], $geoData['country'], $geoData['isp'], $scan['id']]);

    // Update the array for current response
    $scan['geo'] = $geoData;
    $updates_made = true;
}

echo json_encode($scans);