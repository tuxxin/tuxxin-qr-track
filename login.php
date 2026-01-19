<?php
require 'config.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
        $_SESSION['logged_in'] = true;
        session_regenerate_id(true);
        
        // --- CLEANUP TRIGGER ---
        purge_old_tokens($db);
        
        header("Location: " . BASE_URL);
        exit;
    } else {
        $error = "Invalid credentials.";
    }
}
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Tuxxin QR Track</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Recursive:wght@300..900&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #121212; --card: #1e1e1e; --text: #e0e0e0; --accent: #ff6600; --border: #333; }
        body { font-family: 'Recursive', sans-serif; background: var(--bg); color: var(--text); height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .login-card { background: var(--card); padding: 40px; border-radius: 10px; width: 100%; max-width: 350px; border: 1px solid var(--border); box-shadow: 0 4px 20px rgba(0,0,0,0.5); }
        h2 { margin: 0 0 20px 0; text-align: center; color: var(--accent); }
        input { width: 100%; padding: 12px; margin: 10px 0; background: #2a2a2a; border: 1px solid #444; color: white; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: var(--accent); border: none; color: white; border-radius: 4px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        button:hover { opacity: 0.9; }
        .error { color: #ff5555; text-align: center; margin-bottom: 15px; font-size: 0.9em; }
        .logo { display: block; margin: 0 auto 20px auto; width: 60px; }
    </style>
</head>
<body>
    <div class="login-card">
        <img src="logo-v2.png" alt="Logo" class="logo">
        <h2>Admin Login</h2>
        <?php if($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required autofocus>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Sign In</button>
        </form>
    </div>
</body>
</html>