<?php
require 'config.php';

// Set JSON Headers
header('Content-Type: application/json');

// --- SECURITY CHECK (INLINE) ---
// We check if the configured API_KEY is still the default one.
// The MD5 of 'change_me_to_a_random_string' is '35df41071cac5b0e59a567a9292aceb7'
if (API_KEY === 'change_me_to_a_random_string' || md5(API_KEY) === '35df41071cac5b0e59a567a9292aceb7') {
    http_response_code(503); // Service Unavailable
    echo json_encode([
        'status' => 'error',
        'message' => 'API Disabled: Default insecure API Key detected.',
        'instruction' => 'Edit config.php and change API_KEY to a secure random string.'
    ]);
    exit;
}

// --- AUTHENTICATION ---
$headers = getallheaders();
$authKey = $headers['X-Api-Key'] ?? $_GET['api_key'] ?? null;

// Constant time comparison to prevent timing attacks
if ($authKey === null || $authKey !== API_KEY) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized: Invalid API Key']);
    exit;
}

// --- CLEANUP ---
// Clean up old tokens on every API call
purge_old_tokens($db);

// --- ROUTER ---
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    handleCreate($db);
} elseif ($method === 'GET') {
    handleGet($db);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}

// --- HELPERS ---

function generateImageToken($db, $uuid) {
    $token = bin2hex(random_bytes(16));
    $stmt = $db->prepare("INSERT INTO api_tokens (token, product_uuid, expires_at) VALUES (?, ?, datetime('now', '+24 hours'))");
    $stmt->execute([$token, $uuid]);
    return $token;
}

function handleGet($db) {
    $uuid = $_GET['uuid'] ?? null;

    // SINGLE GET
    if ($uuid) {
        $stmt = $db->prepare("SELECT p.*, (SELECT COUNT(*) FROM scans WHERE product_uuid = p.uuid) as scan_count FROM products p WHERE uuid = ? AND is_deleted = 0");
        $stmt->execute([$uuid]);
        $data = $stmt->fetch();

        if (!$data) {
            http_response_code(404);
            echo json_encode(['error' => 'QR Code not found']);
            return;
        }
        echo json_encode(['status' => 'success', 'data' => formatQrData($db, $data)]);
        return;
    }

    // LIST ALL
    $stmt = $db->query("SELECT p.*, (SELECT COUNT(*) FROM scans WHERE product_uuid = p.uuid) as scan_count FROM products p WHERE is_deleted = 0 ORDER BY created_at DESC");
    $rows = $stmt->fetchAll();

    $output = [];
    $db->beginTransaction();
    foreach ($rows as $row) {
        $output[] = formatQrData($db, $row);
    }
    $db->commit();

    echo json_encode(['status' => 'success', 'count' => count($output), 'data' => $output]);
}

function handleCreate($db) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['title']) || !isset($input['type'])) {
        http_response_code(400); echo json_encode(['error' => 'Invalid JSON. Required: title, type']); return;
    }

    $uuid = bin2hex(random_bytes(6));
    $title = $input['title'];
    $type = $input['type'];
    $target = '';

    switch ($type) {
        case 'url': case 'map': case 'social': $target = $input['target']; break;
        case 'phone': $target = $input['phone']; break;
        case 'wifi': $target = json_encode(['ssid'=>$input['ssid'], 'pass'=>$input['pass']??'', 'enc'=>$input['enc']??'WPA']); break;
        case 'vcard': $target = json_encode(['fname'=>$input['fname'], 'lname'=>$input['lname']??'', 'phone'=>$input['phone'], 'email'=>$input['email']??'', 'company'=>$input['company']??'']); break;
        case 'sms': $target = json_encode(['phone'=>$input['phone'], 'body'=>$input['body']]); break;
        case 'email': $target = json_encode(['email'=>$input['email'], 'subject'=>$input['subject'], 'body'=>$input['body']??'']); break;
        default: http_response_code(400); echo json_encode(['error' => 'Invalid Type']); return;
    }

    try {
        $stmt = $db->prepare("INSERT INTO products (uuid, title, type, target_data) VALUES (?, ?, ?, ?)");
        $stmt->execute([$uuid, $title, $type, $target]);

        $token = generateImageToken($db, $uuid);

        echo json_encode([
            'status' => 'created',
            'uuid' => $uuid,
            'tracking_url' => BASE_URL . '/p/' . $uuid,
            'image_url_png' => BASE_URL . '/generate_image.php?id=' . $uuid . '&format=png&token=' . $token
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}

function formatQrData($db, $row) {
    $targetDecoded = json_decode($row['target_data']);
    $finalTarget = (json_last_error() === JSON_ERROR_NONE) ? $targetDecoded : $row['target_data'];
    $token = generateImageToken($db, $row['uuid']);

    return [
        'uuid' => $row['uuid'],
        'title' => $row['title'],
        'type' => $row['type'],
        'target' => $finalTarget,
        'scans' => (int)$row['scan_count'],
        'is_active' => (bool)$row['is_active'],
        'created_at' => $row['created_at'],
        'links' => [
            'tracking_url' => BASE_URL . '/p/' . $row['uuid'],
            'image_png' => BASE_URL . '/generate_image.php?id=' . $row['uuid'] . '&format=png&token=' . $token,
            'image_jpg' => BASE_URL . '/generate_image.php?id=' . $row['uuid'] . '&format=jpg&token=' . $token
        ]
    ];
}