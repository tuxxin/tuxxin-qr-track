<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined('PAGE_TITLE') ? PAGE_TITLE : 'Tuxxin QR Track' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Recursive:wght@300..900&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #121212; --card: #1e1e1e; --text: #e0e0e0; --accent: #ff6600; --border: #333; --danger: #ff4444; --info: #007bff; }
        body { font-family: 'Recursive', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; }
        
        .container { max-width: 1000px; margin: 0 auto; }
        
        /* Header */
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid var(--border); padding-bottom: 20px; }
        .header-brand { display: flex; align-items: center; gap: 15px; }
        .logo-img { height: 50px; width: auto; }
        h1 { margin: 0; font-weight: 800; background: linear-gradient(45deg, #ff6600, #ff9e42); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        /* UI Elements */
        .btn { background: var(--accent); color: #fff; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn:hover { opacity: 0.9; }
        .btn-sm { padding: 5px 10px; font-size: 0.8rem; }
        .btn-danger { background: var(--danger); }
        .btn-info { background: var(--info); } /* New Info Button Style */
        
        /* Compact Grid */
        .qr-list { display: grid; gap: 8px; }
        .qr-item { 
            background: var(--card); 
            padding: 12px 15px; 
            border-radius: 6px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            border: 1px solid var(--border); 
            transition: transform 0.2s; 
        }
        .qr-item:hover { transform: translateY(-1px); border-color: var(--accent); }
        .qr-info h3 { margin: 0; font-size: 1.05rem; }
        .qr-meta { font-size: 0.8rem; color: #888; margin-top: 2px; }
        .qr-stats { font-weight: bold; color: var(--accent); cursor: pointer; text-decoration: underline; margin-right: 15px;}
        
        /* Modals */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 100; align-items: center; justify-content: center; }
        .modal-content { background: var(--card); padding: 30px; border-radius: 10px; width: 90%; max-width: 600px; position: relative; max-height: 90vh; overflow-y: auto; border: 1px solid var(--border); }
        
        /* SVG Close Icon Style */
        .close-icon { 
            position: absolute; top: 15px; right: 20px; cursor: pointer; 
            fill: #fff; width: 24px; height: 24px; opacity: 0.7; transition: opacity 0.2s;
        }
        .close-icon:hover { opacity: 1; }
        
        /* Inputs */
        input, select, textarea { width: 100%; padding: 12px; margin: 8px 0 20px; background: #2a2a2a; border: 1px solid #444; color: white; border-radius: 4px; box-sizing: border-box; font-family: inherit; }
        
        /* Toggle Switch */
        .switch { position: relative; display: inline-block; width: 40px; height: 20px; margin: 0 15px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #444; transition: .4s; border-radius: 20px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 2px; bottom: 2px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--accent); }
        input:checked + .slider:before { transform: translateX(20px); }

        /* Responsive Scan Stats */
        .scan-row { 
            border-bottom: 1px solid #333; padding: 12px 0; font-size: 0.9rem; 
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
        }
        .scan-meta { color: #888; font-size: 0.8rem; grid-column: 1 / -1; }
        
        @media (min-width: 600px) {
            .scan-row { grid-template-columns: 1.5fr 1.5fr 1fr; align-items: start; }
            .scan-meta { grid-column: auto; }
        }

        .type-fields { display: none; }
    </style>
</head>
<body>
<div class="container">
    <header>
        <div class="header-brand">
            <img src="logo-v2.png" alt="Logo" class="logo-img">
            <div>
                <h1>Tuxxin QR Track</h1>
                <small style="color: #666;">Generate & Track QR Codes Easily!</small>
            </div>
        </div>
        
        <?php if(defined('SHOW_ADD_BTN')): ?>
        <div style="display:flex; gap:10px;">
            <a href="api_instructions.php" class="btn btn-info">API</a>
            <button class="btn" onclick="openModal('addModal')">+ New QR Code</button>
        </div>
        <?php endif; ?>
    </header>
