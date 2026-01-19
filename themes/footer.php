</div> <div id="qrModal" class="modal">
    <div class="modal-content" style="text-align: center;">
        <span class="close" onclick="closeModal('qrModal')">&times;</span>
        <h2 id="qrTitle">QR Code</h2>
        <img id="qrImage" src="" style="width: 250px; height: 250px; border: 5px solid white; margin: 20px 0;">
        <div style="display:flex; gap:10px; justify-content:center;">
            <a id="dlPng" href="#" download class="btn btn-sm">Download PNG</a>
            <a id="dlJpg" href="#" download class="btn btn-sm">Download JPG</a>
            <button onclick="printQR()" class="btn btn-sm" style="background: #444;">Print</button>
        </div>
    </div>
</div>

<div id="statsModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('statsModal')">&times;</span>
        <h2>Scan History</h2>
        <div id="statsContent" style="max-height: 400px; overflow-y: auto;">Loading...</div>
    </div>
</div>

<script>
    // Close Modals
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    window.onclick = function(event) { if (event.target.classList.contains('modal')) event.target.style.display = "none"; }

    // Print
    function printQR() {
        const win = window.open('');
        win.document.write('<html><body style="text-align:center;"><img src="' + document.getElementById('qrImage').src + '" onload="window.print();window.close()" /></body></html>');
        win.document.close();
    }
</script>
</body>
</html>