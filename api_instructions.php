<?php
require 'config.php';
require_auth(); 

$apiKey = defined('API_KEY') ? API_KEY : '';
$isDefaultKey = ($apiKey === 'change_me_to_a_random_string' || md5($apiKey) === '35df41071cac5b0e59a567a9292aceb7');
$apiUrl = BASE_URL . '/api.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Docs & Console | Tuxxin QR Track</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Recursive:wght@300..900&display=swap" rel="stylesheet">
    <style>
        :root { --bg: #121212; --card: #1e1e1e; --text: #e0e0e0; --accent: #ff6600; --border: #333; --code-bg: #151515; --danger: #ff4444; }
        body { font-family: 'Recursive', sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 20px; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 30px; }
        
        header { grid-column: 1 / -1; border-bottom: 1px solid var(--border); padding-bottom: 20px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        h1 { color: var(--accent); margin: 0; }
        h2 { border-left: 4px solid var(--accent); padding-left: 10px; margin-top: 30px; color: #fff; }
        
        .doc-section { background: var(--card); padding: 25px; border-radius: 8px; border: 1px solid var(--border); }
        .console-section { background: #181818; padding: 25px; border-radius: 8px; border: 1px solid var(--border); position: sticky; top: 20px; height: fit-content; }
        
        /* Alerts */
        .alert-danger { grid-column: 1 / -1; background: rgba(255, 68, 68, 0.1); border: 1px solid var(--danger); padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .alert-danger h3 { color: var(--danger); margin-top: 0; }
        
        pre { background: var(--code-bg); padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #333; color: #a5d6a7; font-size: 0.9em; margin: 0; }
        code { font-family: 'Courier New', monospace; color: #ff9e42; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 0.75em; font-weight: bold; background: #333; cursor: pointer; border: 1px solid transparent; transition:0.2s; }
        .badge:hover { border-color: var(--accent); color: white; }
        
        label { display: block; margin: 12px 0 5px; color: #888; font-size: 0.85em; text-transform: uppercase; letter-spacing: 1px; }
        input, select, textarea { width: 100%; padding: 10px; background: #222; border: 1px solid #444; color: white; border-radius: 4px; box-sizing: border-box; font-family: 'Courier New', monospace; }
        button { background: var(--accent); color: white; border: none; padding: 12px; width: 100%; cursor: pointer; font-weight: bold; border-radius: 4px; margin-top: 20px; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 100; align-items: center; justify-content: center; }
        .modal-content { background: var(--card); padding: 30px; border-radius: 10px; width: 90%; max-width: 600px; position: relative; border: 1px solid var(--border); }
        .close-btn { position: absolute; top: 15px; right: 20px; color: #fff; cursor: pointer; font-size: 1.5em; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

        .keygen-box { background: #222; border: 1px solid var(--accent); padding: 15px; border-radius: 5px; margin-bottom: 25px; text-align: center; }
        .keygen-val { font-family: monospace; font-size: 1.1em; color: var(--accent); display: block; margin: 10px 0; word-break: break-all; }
        .btn-copy { background: #444; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 0.8em; }
    </style>
</head>
<body>

<div class="container">
    <header>
        <div>
            <h1>API Documentation</h1>
            <small style="color:#888;">Endpoint: <?= $apiUrl ?></small>
        </div>
        <div><a href="/" style="color:white; text-decoration:none; background:#333; padding:8px 15px; border-radius:4px;">&larr; Dashboard</a></div>
    </header>

    <?php if ($isDefaultKey): ?>
    <div class="alert-danger">
        <h3>---  API IS DISABLED  ---</h3>
        <p>You are using the default insecure API Key. For security, the API endpoint has been deactivated.</p>
        <p>Please use the generator below to create a new key, update your <code>config.php</code> file, and refresh this page.</p>
        
        <div class="keygen-box" style="max-width:500px; margin:20px auto; background:#000;">
            <strong style="color:white; font-size:0.9em;">GENERATE NEW SECURE KEY</strong>
            <span id="newKeyDisplay" class="keygen-val">Click generate below...</span>
            <button class="btn-copy" onclick="generateKey()">Generate & Copy</button>
        </div>
    </div>
    <?php else: ?>

    <div class="doc-section">
        <h2>1. Authentication</h2>
        <p>Header required:</p>
        <pre>X-Api-Key: <?= substr($apiKey, 0, 5) ?>... (See config.php)</pre>

        <h2>2. Endpoints & Payloads</h2>
        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px;">
            <span class="badge" onclick="showPayload('url')">URL</span>
            <span class="badge" onclick="showPayload('wifi')">WiFi</span>
            <span class="badge" onclick="showPayload('vcard')">vCard</span>
            <span class="badge" onclick="showPayload('sms')">SMS</span>
            <span class="badge" onclick="showPayload('email')">Email</span>
            <span class="badge" onclick="showPayload('phone')">Phone</span>
            <span class="badge" onclick="showPayload('map')">Map</span>
            <span class="badge" onclick="showPayload('social')">Social</span>
        </div>

        <h3>Global GET Endpoints</h3>
        <p><strong>List All:</strong> <code>GET /api.php</code></p>
        <p><strong>Get Single:</strong> <code>GET /api.php?uuid={uuid}</code></p>
    </div>

    <div class="console-section">
        <h2 style="margin-top:0">Live Console</h2>
        <form id="apiForm">
            <input type="hidden" id="apiKey" value="<?= $apiKey ?>">
            
            <label>Action</label>
            <select id="apiAction" onchange="toggleConsole()">
                <option value="create">Create QR (POST)</option>
                <option value="list">List All QRs (GET)</option>
                <option value="get">Get Single QR (GET)</option>
            </select>

            <div id="createWrapper">
                <label>Title</label><input type="text" id="qrTitle" placeholder="Reference Name">
                <label>Type</label>
                <select id="qrType" onchange="toggleTypeFields()">
                    <option value="url">Website URL</option>
                    <option value="wifi">WiFi Network</option>
                    <option value="vcard">vCard Contact</option>
                    <option value="sms">SMS Message</option>
                    <option value="email">Email</option>
                    <option value="phone">Phone Number</option>
                    <option value="map">Map Location</option>
                    <option value="social">Social Media</option>
                </select>
                
                <div id="f-url" class="field-group"><label>Target URL</label><input type="text" id="in-target" placeholder="https://..."></div>
                <div id="f-phone" class="field-group" style="display:none"><label>Phone Number</label><input type="text" id="in-phone" placeholder="+1555..."></div>
                <div id="f-wifi" class="field-group" style="display:none">
                    <label>SSID</label><input type="text" id="in-ssid">
                    <div class="grid-2">
                        <div><label>Password</label><input type="text" id="in-pass"></div>
                        <div><label>Encryption</label><select id="in-enc"><option>WPA</option><option>WEP</option><option>nopass</option></select></div>
                    </div>
                </div>
                <div id="f-vcard" class="field-group" style="display:none">
                    <div class="grid-2">
                        <div><label>First Name</label><input type="text" id="in-fname"></div>
                        <div><label>Last Name</label><input type="text" id="in-lname"></div>
                    </div>
                    <label>Phone</label><input type="text" id="in-vphone">
                    <label>Email</label><input type="text" id="in-vemail">
                </div>
                <div id="f-sms" class="field-group" style="display:none"><label>Phone</label><input type="text" id="in-sphone"><label>Message</label><textarea id="in-sbody"></textarea></div>
                <div id="f-email" class="field-group" style="display:none"><label>Email To</label><input type="text" id="in-eaddr"><label>Subject</label><input type="text" id="in-esub"><label>Body</label><textarea id="in-ebody"></textarea></div>
            </div>

            <div id="getWrapper" style="display:none;"><label>UUID</label><input type="text" id="qrUuid" placeholder="e.g. 5f3a2b..."></div>

            <button type="submit">Send Request</button>
        </form>

        <label style="margin-top:20px;">Response</label>
        <pre id="responseArea" style="min-height:100px;">...</pre>
    </div>
    <?php endif; ?>
</div>

<div id="payloadModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="document.getElementById('payloadModal').style.display='none'">&times;</span>
        <h2 id="modalTitle" style="margin-top:0">JSON Payload</h2>
        <pre id="modalCode"></pre>
    </div>
</div>

<script>
    // SHARED FUNCTIONS
    function generateKey() {
        const array = new Uint8Array(16);
        window.crypto.getRandomValues(array);
        let hash = '';
        for (let i = 0; i < array.length; i++) hash += array[i].toString(16).padStart(2, '0');
        
        document.getElementById('newKeyDisplay').innerText = hash;
        navigator.clipboard.writeText(hash).then(() => alert("New API Key Copied!"));
    }

    <?php if (!$isDefaultKey): ?>
    const payloads = {
        url: { title: "My Link", type: "url", target: "https://google.com" },
        map: { title: "Office", type: "map", target: "123 Main St, New York, NY" },
        social: { title: "My Facebook", type: "social", target: "https://facebook.com/..." },
        phone: { title: "Call Support", type: "phone", phone: "+15550000000" },
        wifi: { title: "Guest WiFi", type: "wifi", ssid: "MyNet", pass: "123456", enc: "WPA" },
        vcard: { title: "John Doe", type: "vcard", fname: "John", lname: "Doe", phone: "+1555...", email: "john@doe.com", company: "Acme Inc" },
        sms: { title: "Text Me", type: "sms", phone: "+1555...", body: "Hello!" },
        email: { title: "Email Support", type: "email", email: "help@site.com", subject: "Help", body: "I need help with..." }
    };

    function showPayload(type) {
        document.getElementById('modalTitle').innerText = type.toUpperCase() + " Payload";
        document.getElementById('modalCode').innerText = JSON.stringify(payloads[type], null, 4);
        document.getElementById('payloadModal').style.display = 'flex';
    }

    function toggleConsole() {
        const action = document.getElementById('apiAction').value;
        document.getElementById('createWrapper').style.display = (action === 'create') ? 'block' : 'none';
        document.getElementById('getWrapper').style.display = (action === 'get') ? 'block' : 'none';
    }

    function toggleTypeFields() {
        document.querySelectorAll('.field-group').forEach(el => el.style.display = 'none');
        const type = document.getElementById('qrType').value;
        
        if(type === 'url' || type === 'map' || type === 'social') document.getElementById('f-url').style.display = 'block';
        else if(type === 'phone') document.getElementById('f-phone').style.display = 'block';
        else if(type === 'wifi') document.getElementById('f-wifi').style.display = 'block';
        else if(type === 'vcard') document.getElementById('f-vcard').style.display = 'block';
        else if(type === 'sms') document.getElementById('f-sms').style.display = 'block';
        else if(type === 'email') document.getElementById('f-email').style.display = 'block';
    }

    document.getElementById('apiForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const action = document.getElementById('apiAction').value;
        const resultArea = document.getElementById('responseArea');
        resultArea.textContent = 'Sending...';

        let url = '<?= $apiUrl ?>';
        let options = {
            headers: { 'X-Api-Key': document.getElementById('apiKey').value, 'Content-Type': 'application/json' }
        };

        if (action === 'list') {
            options.method = 'GET';
        } else if (action === 'get') {
            url += '?uuid=' + document.getElementById('qrUuid').value;
            options.method = 'GET';
        } else if (action === 'create') {
            options.method = 'POST';
            const type = document.getElementById('qrType').value;
            let payload = { title: document.getElementById('qrTitle').value, type: type };

            if(type === 'url' || type === 'map' || type === 'social') payload.target = document.getElementById('in-target').value;
            else if(type === 'phone') payload.phone = document.getElementById('in-phone').value;
            else if(type === 'wifi') {
                payload.ssid = document.getElementById('in-ssid').value;
                payload.pass = document.getElementById('in-pass').value;
                payload.enc = document.getElementById('in-enc').value;
            }
            else if(type === 'vcard') {
                payload.fname = document.getElementById('in-fname').value;
                payload.lname = document.getElementById('in-lname').value;
                payload.phone = document.getElementById('in-vphone').value;
                payload.email = document.getElementById('in-vemail').value;
            }
            else if(type === 'sms') {
                payload.phone = document.getElementById('in-sphone').value;
                payload.body = document.getElementById('in-sbody').value;
            }
            else if(type === 'email') {
                payload.email = document.getElementById('in-eaddr').value;
                payload.subject = document.getElementById('in-esub').value;
                payload.body = document.getElementById('in-ebody').value;
            }

            options.body = JSON.stringify(payload);
        }

        try {
            const req = await fetch(url, options);
            const json = await req.json();
            resultArea.textContent = JSON.stringify(json, null, 4);
        } catch (err) {
            resultArea.textContent = 'Error: ' + err.message;
        }
    });
    <?php endif; ?>

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) event.target.style.display = "none";
    }
</script>

</body>
</html>