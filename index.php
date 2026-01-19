<?php
require 'config.php';
require_auth();

// --- HANDLE FORM SUBMISSIONS & ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Action: Add New QR Code
    if ($_POST['action'] === 'add') {
        $uuid = bin2hex(random_bytes(6));
        
        // Store RAW title in DB (Sanitize on Output only)
        $title = $_POST['title']; 
        $type = $_POST['type'];
        
        // Construct Target Data based on Type
        $target = '';
        if ($type === 'vcard') {
            $target = json_encode([
                'fname' => $_POST['v_fname'], 'lname' => $_POST['v_lname'],
                'phone' => $_POST['v_phone'], 'email' => $_POST['v_email'],
                'company' => $_POST['v_company']
            ]);
        } elseif ($type === 'wifi') {
             $target = json_encode([
                'ssid' => $_POST['wifi_ssid'],
                'pass' => $_POST['wifi_pass'],
                'enc' => $_POST['wifi_enc']
            ]);
        } elseif ($type === 'sms') {
             $target = json_encode([
                'phone' => $_POST['sms_phone'],
                'body' => $_POST['sms_body']
            ]);
        } elseif ($type === 'email') {
             $target = json_encode([
                'email' => $_POST['email_addr'],
                'subject' => $_POST['email_sub'],
                'body' => $_POST['email_body']
            ]);
        } else {
            // Standard Types: URL, Map, Phone, Social
            $target = $_POST['target'];
        }

        // Handle Logo Upload
        $logoPath = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
                if (!is_dir(LOGO_DIR)) {
                    mkdir(LOGO_DIR, 0755, true);
                }
                $filename = 'logo_' . $uuid . '.' . $ext;
                $destPath = LOGO_DIR . '/' . $filename;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $destPath)) {
                    $logoPath = $filename;
                } else {
                    error_log("File upload error: Unable to move file to " . $destPath);
                }
            }
        }

        // Insert into Database
        $stmt = $db->prepare("INSERT INTO products (uuid, title, type, target_data, logo_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$uuid, $title, $type, $target, $logoPath]);
        
        header("Location: " . BASE_URL);
        exit;
    }
    
    // Action: Toggle Active Status
    if ($_POST['action'] === 'toggle') {
        $stmt = $db->prepare("UPDATE products SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        exit;
    }

    // Action: Soft Delete
    if ($_POST['action'] === 'delete') {
        $stmt = $db->prepare("UPDATE products SET is_active = 0, is_deleted = 1 WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        exit;
    }
}

// --- FETCH DATA ---
$products = $db->query("
    SELECT p.*, (SELECT COUNT(*) FROM scans WHERE product_uuid = p.uuid) as scan_count 
    FROM products p 
    WHERE is_deleted = 0
    ORDER BY created_at DESC
")->fetchAll();

// --- RENDER VIEW ---
define('SHOW_ADD_BTN', true);
include THEME_PATH . '/header.php';
?>

<div class="qr-list">
    <?php foreach($products as $p): ?>
    <div class="qr-item">
        <div class="qr-info">
            <h3><?= htmlspecialchars($p['title']) ?> <span style="font-size:0.7em; opacity:0.6">[<?= strtoupper($p['type']) ?>]</span></h3>
            <div class="qr-meta">Created: <?= date('M d, Y', strtotime($p['created_at'])) ?></div>
        </div>
        
        <div style="display: flex; align-items: center;">
            
            <?php if($p['type'] === 'wifi'): ?>
                <div class="qr-stats" style="color: #666; text-decoration: none; cursor: default;">
                    Not Trackable
                </div>
            <?php else: ?>
                <div onclick="loadStats('<?= $p['uuid'] ?>')" class="qr-stats">
                    <?= $p['scan_count'] ?> Scans
                </div>
            <?php endif; ?>
            
            <label class="switch">
                <input type="checkbox" onchange="toggleQR(<?= $p['id'] ?>)" <?= $p['is_active'] ? 'checked' : '' ?>>
                <span class="slider"></span>
            </label>
            
            <button class="btn btn-sm" onclick="showQR('<?= $p['uuid'] ?>', <?= htmlspecialchars(json_encode($p['title']), ENT_QUOTES) ?>)" style="margin-right: 10px;">Get Code</button>
            
            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $p['id'] ?>)" title="Delete" style="display:flex; align-items:center; padding: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                  <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                </svg>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div id="addModal" class="modal">
    <div class="modal-content">
        <svg class="close-icon" onclick="closeModal('addModal')" viewBox="0 0 24 24">
            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
        </svg>

        <h2>Add Product / QR</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <label>Title</label> <input type="text" name="title" required placeholder="Product Name">
            <label>Type</label>
            <select name="type" id="typeSelect" onchange="toggleFields()">
                <option value="url">Website URL</option>
                <option value="phone">Phone Number</option>
                <option value="map">Map Location</option>
                <option value="vcard">vCard Contact</option>
                <option value="wifi">Wi-Fi Network</option>
                <option value="sms">SMS Message</option>
                <option value="email">Email Message</option>
                <option value="social">Social Media</option>
            </select>
            
            <div id="field-general" class="type-fields" style="display:block;"><input type="url" name="target" placeholder="https://example.com"></div>
            <div id="field-vcard" class="type-fields">
                <input type="text" name="v_fname" placeholder="First Name"><input type="text" name="v_lname" placeholder="Last Name">
                <input type="text" name="v_phone" placeholder="Phone"><input type="email" name="v_email" placeholder="Email"><input type="text" name="v_company" placeholder="Company">
            </div>
            <div id="field-wifi" class="type-fields">
                <input type="text" name="wifi_ssid" placeholder="Network Name (SSID)">
                <input type="text" name="wifi_pass" placeholder="Password">
                <select name="wifi_enc"><option value="WPA">WPA/WPA2</option><option value="WEP">WEP</option><option value="nopass">No Encryption</option></select>
            </div>
            <div id="field-sms" class="type-fields"><input type="tel" name="sms_phone" placeholder="Phone Number"><textarea name="sms_body" placeholder="Message Body"></textarea></div>
            <div id="field-email" class="type-fields"><input type="email" name="email_addr" placeholder="Recipient"><input type="text" name="email_sub" placeholder="Subject"><textarea name="email_body" placeholder="Body"></textarea></div>

            <label>Embedded Logo (Optional)</label>
            <input type="file" name="logo" accept="image/png, image/jpeg">
            <button type="submit" class="btn" style="width:100%; margin-top:20px;">Generate QR</button>
        </form>
    </div>
</div>

<div id="statsModal" class="modal">
    <div class="modal-content">
        <svg class="close-icon" onclick="closeModal('statsModal')" viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        <h2>Scan History</h2>
        <div id="statsContent" style="max-height: 400px; overflow-y: auto;">Loading...</div>
    </div>
</div>

<div id="qrModal" class="modal">
    <div class="modal-content" style="text-align: center;">
        <svg class="close-icon" onclick="closeModal('qrModal')" viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>

        <h2 id="qrTitle">QR Code</h2>
        <img id="qrImage" src="" style="width: 250px; height: 250px; border: 5px solid white; margin: 20px 0;">
        <div style="display:flex; gap:10px; justify-content:center;">
            <a id="dlPng" href="#" download class="btn btn-sm">Download PNG</a>
            <a id="dlJpg" href="#" download class="btn btn-sm">Download JPG</a>
            <button onclick="printQR()" class="btn btn-sm" style="background: #444;">Print</button>
        </div>
    </div>
</div>

<div id="deleteModal" class="modal">
    <div class="modal-content" style="text-align: center;">
        <h2>Are you sure?</h2>
        <p>This will hide the QR code from the list.</p>
        <div style="margin-top: 20px;">
            <button id="confirmDeleteBtn" class="btn btn-danger">Yes, Delete</button>
            <button onclick="closeModal('deleteModal')" class="btn" style="background: #444;">Cancel</button>
        </div>
    </div>
</div>

<script>
    // UTILITIES
    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    window.onclick = function(event) { if (event.target.classList.contains('modal')) event.target.style.display = "none"; }
    
    // PRINT LOGIC
    function printQR() {
        const win = window.open('');
        win.document.write('<html><body style="text-align:center;"><h2 style="font-family:sans-serif">' + document.getElementById('qrTitle').innerText + '</h2><img src="' + document.getElementById('qrImage').src + '" onload="window.print();window.close()" /></body></html>');
        win.document.close();
    }

    // FORM FIELDS
    function toggleFields() {
        document.querySelectorAll('.type-fields').forEach(e => e.style.display = 'none');
        const type = document.getElementById('typeSelect').value;
        if(['vcard','wifi','sms','email'].includes(type)) {
            document.getElementById('field-' + type).style.display = 'block';
        } else {
            document.getElementById('field-general').style.display = 'block';
            const generalInput = document.querySelector('#field-general input');
            generalInput.type = (type === 'url' || type === 'social') ? 'url' : 'text';
            if(type === 'phone') generalInput.placeholder = '+15550000000';
            else if(type === 'map') generalInput.placeholder = '123 Main St, City, ST';
            else generalInput.placeholder = 'https://...';
        }
    }

    // ACTIONS
    function toggleQR(id) {
        const fd = new FormData(); fd.append('action', 'toggle'); fd.append('id', id);
        fetch('index.php', { method: 'POST', body: fd });
    }

    function showQR(uuid, title) {
        const urlBase = 'generate_image.php?id=' + uuid;
        document.getElementById('qrImage').src = urlBase + '&format=jpg'; 
        document.getElementById('qrTitle').innerText = title;
        document.getElementById('dlPng').href = urlBase + '&format=png';
        document.getElementById('dlJpg').href = urlBase + '&format=jpg';
        document.getElementById('dlPng').setAttribute('download', title + '-QR.png');
        document.getElementById('dlJpg').setAttribute('download', title + '-QR.jpg');
        openModal('qrModal');
    }

    function loadStats(uuid) {
        openModal('statsModal');
        document.getElementById('statsContent').innerHTML = '<p style="text-align:center; padding:20px;">Loading geolocation data...</p>';
        fetch('api_stats.php?uuid=' + uuid)
            .then(res => res.json())
            .then(data => {
                let html = '';
                if(data.length === 0) html = '<p>No scans yet.</p>';
                else {
                    data.forEach(row => {
                        let statusBadge = '';
                        if(row.scan_status === 'blocked') {
                            statusBadge = '<span style="background:red; padding:2px 5px; border-radius:3px; font-size:0.7em;">DISABLED SCAN</span> ';
                        }

                        html += `
                        <div class="scan-row">
                            <div>
                                <strong style="color:white; font-size:1.1em;">${row.ip_address}</strong>
                                <div style="color:#aaa; font-size:0.85em;">${row.geo.isp || 'Unknown ISP'}</div>
                            </div>
                            <div>
                                <div style="color: var(--accent); font-weight:bold;">${row.geo.city}, ${row.geo.region}</div>
                                <div style="color:#aaa; font-size:0.85em;">${row.geo.country}</div>
                            </div>
                            <div class="scan-meta">
                                <div>${statusBadge} ${row.scanned_at}</div>
                                <div style="font-size:0.75em; opacity:0.7; margin-top:4px;">${row.user_agent.substring(0, 60)}...</div>
                            </div>
                        </div>`;
                    });
                }
                document.getElementById('statsContent').innerHTML = html;
            })
            .catch(err => {
                document.getElementById('statsContent').innerHTML = '<p style="color:red">Error loading stats.</p>';
            });
    }

    // DELETE
    let deleteId = null;
    function confirmDelete(id) { deleteId = id; openModal('deleteModal'); }
    document.getElementById('confirmDeleteBtn').onclick = function() {
        const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', deleteId);
        fetch('index.php', { method: 'POST', body: fd }).then(() => location.reload());
    }
</script>

<?php include THEME_PATH . '/footer.php'; ?>