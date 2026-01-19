<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Disabled | Tuxxin QR Track</title>
    <meta name="description" content="The QR Code or Link you scanned is currently inactive.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Recursive:wght@300..900&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #121212; --card: #1e1e1e; --text: #e0e0e0; --accent: #ff6600; }
        body { font-family: 'Recursive', sans-serif; background: var(--bg); color: var(--text); height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; text-align: center; }
        .card { background: var(--card); padding: 40px; border-radius: 10px; max-width: 400px; width: 90%; border: 1px solid #333; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        h1 { color: var(--accent); margin-bottom: 10px; }
        p { color: #888; line-height: 1.6; }
        .logo { width: 80px; margin-bottom: 20px; cursor: pointer; transition: transform 0.2s; }
        .logo:hover { transform: scale(1.05); }
        .timer { font-weight: bold; color: #fff; margin-top: 20px; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="card">
        <a href="https://tuxxin.com">
            <img src="<?= BASE_URL ?>/logo-v2.png" alt="Logo" class="logo" onerror="this.style.display='none'">
        </a>
        
        <h1>Link Inactive</h1>
        <p>The QR code you scanned is currently paused, disabled, or has been removed by the owner.</p>
        
        <p class="timer" id="countdown">Redirecting to Tuxxin.com in 5...</p>
        
        <p><small>Powered by Tuxxin QR Track</small></p>
    </div>

    <script>
        // 5 Second Countdown & Redirect
        let seconds = 5;
        const display = document.getElementById('countdown');
        
        const timer = setInterval(function() {
            seconds--;
            display.innerText = "Redirecting to Tuxxin.com in " + seconds + "...";
            
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.href = "https://tuxxin.com";
            }
        }, 1000);
    </script>
</body>
</html>
