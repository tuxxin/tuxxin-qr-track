<?php
declare(strict_types=1);

// --- AUTHENTICATION SETTINGS ---
define('ADMIN_USER', 'admin'); 
define('ADMIN_PASS', 'password'); 

// --- API SETTINGS ---
// KEY used for the JSON API (X-Api-Key header)
define('API_KEY', 'change_me_to_a_random_string');

// Site Settings
define('BASE_URL', 'https://yourdomain.com'); 
define('DB_PATH', '/home/user/db/tuxxin_qr.sqlite'); <-- Place outside of htdoc root
define('TIMEZONE', 'America/New_York');
define('THEME_PATH', __DIR__ . '/themes');
define('LOGO_DIR', '/home/user/tmp'); <-- Set for local tmp but can also use /tmp

// Network Settings (Enable when webservers behind tunnel like CloudFlare Zero Trust)
define('USE_CLOUDFLARE_TUNNEL', true); <-- Used for scan statistics.

// END OF CONFIGURATION


// --- HELPER FUNCTIONS ---
date_default_timezone_set(TIMEZONE);
function require_auth() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: /login.php");
        exit;
    }
}

// Cleans up tokens older than 24 hours
function purge_old_tokens($db) {
    // Delete entries where expiration is in the past
    $db->exec("DELETE FROM api_tokens WHERE expires_at < datetime('now')");
}

// --- DATABASE CONNECTION ---
$dbDir = dirname(DB_PATH);
$dbFile = DB_PATH;

if ((!is_dir($dbDir) || !is_writable($dbDir)) || (file_exists($dbFile) && !is_writable($dbFile))) {
    exit("Database Permission Error: PHP cannot write to $dbDir");
}

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Products Table
    $db->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid TEXT UNIQUE,
        title TEXT,
        type TEXT, target_data TEXT, logo_path TEXT DEFAULT NULL,
        is_active INTEGER DEFAULT 1, is_deleted INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Scans Table
    $db->exec("CREATE TABLE IF NOT EXISTS scans (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_uuid TEXT, ip_address TEXT, user_agent TEXT,
        scan_status TEXT DEFAULT 'success', scanned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(product_uuid) REFERENCES products(uuid)
    )");
    
    // NEW: Temporary API Tokens Table
    $db->exec("CREATE TABLE IF NOT EXISTS api_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        token TEXT UNIQUE,
        product_uuid TEXT,
        expires_at DATETIME
    )");
    // Index for faster lookups
    $db->exec("CREATE INDEX IF NOT EXISTS idx_api_token ON api_tokens(token)");

} catch (PDOException $e) { die("Database Error: " . $e->getMessage()); }
