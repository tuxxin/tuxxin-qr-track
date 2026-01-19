<?php
// /home/qr.tuxxin.net/www/debug.php
echo "<h1>Header Debugger</h1>";
echo "<p>Search this list for your real IP address.</p>";
echo "<pre>";
foreach ($_SERVER as $key => $value) {
    if (str_starts_with($key, 'HTTP_') || $key === 'REMOTE_ADDR') {
        echo "$key = $value\n";
    }
}
echo "</pre>";