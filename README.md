# Tuxxin QR Track

![tuxxin-qr-track](https://github.com/user-attachments/assets/3bfd4d83-2f4f-47ba-94d8-4f608ba5f4b9)


A lightweight, self-hosted dynamic QR code tracking system built with PHP and SQLite.
Create, manage, and track QR codes for URLs, WiFi networks, vCards, and more—all from a secure, dark-mode dashboard or via a REST API.

## 🚀 Features

* **Dynamic QR Codes:** Update the destination URL or content instantly without changing the printed QR code.
* **Detailed Tracking:**
    * Logs **Real User IP** (even behind Cloudflare Tunnels).
    * Captures **Geo-Location** (City, Region, Country, ISP).
    * Detects **Device Type** (User Agent).
* **Privacy Aware:** Includes logic to detect and handle traffic from Apple iCloud Private Relay, CloudFlare and Proxies.
* **Multiple Content Types:**
    * 🔗 **URL:** Standard website links.
    * 📶 **WiFi:** Direct connection QR codes (WPA/WEP/Open). **(Not trackable)**
    * 👤 **vCard:** Add contacts directly to address books.
    * 📍 **Maps, Phone, SMS, Email, Social Media.**
* **Secure API:**
    * Full REST API for creating and retrieving QR codes programmatically.
    * **Secure Image Access:** API generates temporary, expiring tokens to allow external scripts to download QR images securely.
    * **Built-in Console:** Includes an Instructional API page with documentation and a live testing tool.
* **Customization:** Upload logos to embed them into the center of the QR code. **(Currently not supported by API)**
* **Management Dashboard:**
    * Real-time scan statistics.
    * Soft Delete & Restore.
    * "Disable" toggle (redirects users to a custom "Link Inactive" page).

---

## 🛠️ Requirements

* **OS:** Linux (Debian/Ubuntu recommended)
* **Web Server:** Apache (with `mod_rewrite`) or Nginx
* **PHP:** 8.0+ (Extensions: `sqlite3`, `gd`, `curl`, `mbstring`, `xml`)
* **Database:** SQLite 3

---

## 📥 Quick Install

1.  **Clone & Install Dependencies**
    ```bash
    git clone https://github.com/tuxxin/qr-track.git
    cd qr-track
    composer install
    ```

2.  **Set Permissions**
    ```bash
    # It's recommended to place these outside your HTDOCS. 
    mkdir -p db tmp
    chmod -R 775 db tmp
    ```

3.  **Configure**

    Edit `config.php`:
    * Set `ADMIN_USER`, `ADMIN_PASS`, and a random `API_KEY`.
    * Update `BASE_URL` to your domain.
    * Update `DB_PATH` to your db folder location.
    * Update `logo_path` to your tmp folder location.
    * Toggle `USE_CLOUDFLARE_TUNNEL` based on your network.

    Edit `.htaccess`:
 
    ```bash
    RewriteEngine On

    # Update to your root or sub-directory
    RewriteBase /qr-track/

    # Rewrite rule for QR Scan Proxy
    RewriteRule ^p/([a-zA-Z0-9]+)$ proxy.php?id=$1 [L,QSA]

    # These files don't need to be seen!
    <FilesMatch "\.(sqlite|db|log)$">
       Order Deny,Allow
       Deny from all
    </FilesMatch>
    <Files "config.php">
       Order Deny,Allow
       Deny from all
    </Files>
