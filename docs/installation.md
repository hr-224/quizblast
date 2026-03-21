# Installation

## Requirements

| Requirement | Minimum | Notes |
|---|---|---|
| PHP | 8.2+ | Extensions: pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, curl |
| Composer | 2.x | [getcomposer.org](https://getcomposer.org) |
| Database | — | MySQL 8+ or MariaDB 10.3+ |
| Web Server | — | nginx **or** Apache 2.4 with `mod_rewrite` |

---

## Step 1 — Clone and install dependencies

```bash
git clone https://github.com/hr-224/quizblast.git
cd quizblast
composer install --no-dev --optimize-autoloader
```

Fix directory permissions:

```bash
chmod -R 775 storage bootstrap/cache
```

---

## Step 2 — Configure your web server

Point your web server's document root at the `public/` directory. Choose one:

### nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com;

    root /var/www/quizblast/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Reload nginx: `sudo nginx -t && sudo systemctl reload nginx`

### Apache 2.4

Enable required modules first:

```bash
sudo a2enmod rewrite proxy proxy_http proxy_wstunnel
```

Create a virtual host:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/quizblast/public

    <Directory /var/www/quizblast/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  ${APACHE_LOG_DIR}/quizblast-error.log
    CustomLog ${APACHE_LOG_DIR}/quizblast-access.log combined
</VirtualHost>
```

The `public/.htaccess` included in the repo handles URL rewriting — no extra configuration needed.

Reload Apache: `sudo a2ensite quizblast && sudo systemctl reload apache2`

---

## Step 3 — Run the web installer

Open **`http://yourdomain.com/install/`** in your browser.

If you visit the root URL without a configured `.env`, you will be redirected to the installer automatically.

### Installer steps

1. **Requirements** — checks PHP version, extensions, and directory permissions
2. **Database** — enter MySQL/MariaDB credentials; the installer tests the connection and creates the database if needed
3. **Site Settings** — set site name, URL, and create your admin account; optionally include sample quiz content
4. **Install** — runs migrations, generates the app key, creates your account; shows a live log

When complete, click **Open QuizBlast** to go to the app.

---

## Manual / CLI setup

For headless servers or automated deployments where you can't use a browser:

```bash
cp .env.example .env
# Edit .env: set APP_URL, DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

php artisan key:generate
php artisan migrate --force

# Create your admin account
php artisan tinker --execute="\App\Models\User::create(['name'=>'Admin','email'=>'you@example.com','password'=>bcrypt('yourpassword')]);"

# Mark installer as complete
touch install/.installed
```

---

## Enable WebSockets (optional)

WebSockets power the live player count, join ticker, and emoji reactions. Without them, the game works via HTTP polling.

Start the Reverb server as a background service — see [WebSockets](websockets.md) for the full setup including nginx proxy and Apache mod_proxy_wstunnel configuration.

---

## Next Steps

- [Configuration reference](configuration.md) — all `.env` variables explained
- [WebSockets setup](websockets.md) — Reverb with nginx and Apache
- [Self-hosting guide](self-hosting.md) — SSL, systemd, full production checklist
