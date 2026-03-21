# Self-Hosting Guide

Deploy QuizBlast to your own Linux server (Ubuntu / Debian). Choose nginx or Apache 2.4 — both are fully supported.

---

## Server Requirements

| Component | Minimum |
|---|---|
| OS | Ubuntu 22.04+ / Debian 12+ |
| PHP | 8.2+ with: pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, curl |
| Web server | nginx **or** Apache 2.4 with mod_rewrite |
| Database | MySQL 8+ or MariaDB 10.3+ |
| RAM | 512 MB+ |
| Ports | 80, 443 (public); 7001 (internal, Reverb) |

---

## 1. Install dependencies

### With nginx

```bash
sudo apt update
sudo apt install -y php8.3-cli php8.3-fpm php8.3-mbstring php8.3-xml \
    php8.3-mysql php8.3-curl php8.3-tokenizer php8.3-ctype \
    mysql-server nginx git unzip

curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### With Apache 2.4

```bash
sudo apt update
sudo apt install -y php8.3 php8.3-mbstring php8.3-xml \
    php8.3-mysql php8.3-curl php8.3-tokenizer php8.3-ctype \
    mysql-server apache2 libapache2-mod-php8.3 git unzip

sudo a2enmod rewrite proxy proxy_http proxy_wstunnel

curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

## 2. Clone and install

```bash
cd /var/www
sudo git clone https://github.com/hr-224/quizblast.git
sudo chown -R $USER:www-data quizblast
cd quizblast

composer install --no-dev --optimize-autoloader

sudo chown -R www-data:www-data /var/www/quizblast
sudo find /var/www/quizblast -type f -exec chmod 644 {} \;
sudo find /var/www/quizblast -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/quizblast/storage /var/www/quizblast/bootstrap/cache
sudo chmod +x /var/www/quizblast/artisan
```

---

## 3. Configure the web server

### nginx

Create `/etc/nginx/sites-available/quizblast`:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com;

    ssl_certificate     /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    root  /var/www/quizblast/public;
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

    # WebSocket proxy (Reverb)
    location /app {
        proxy_pass http://127.0.0.1:7001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 60s;
    }

    # Static assets — long cache
    location ~* \.(css|js|ico|svg|png|jpg|woff2?)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }

    location ~ /\.ht {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/quizblast /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### Apache 2.4

Create `/etc/apache2/sites-available/quizblast.conf`:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    Redirect permanent / https://yourdomain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot /var/www/quizblast/public

    SSLEngine on
    SSLCertificateFile    /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem

    <Directory /var/www/quizblast/public>
        AllowOverride All
        Require all granted
    </Directory>

    # WebSocket proxy (Reverb)
    ProxyRequests Off
    <Location /app>
        ProxyPass        ws://127.0.0.1:7001/app
        ProxyPassReverse ws://127.0.0.1:7001/app
    </Location>

    ErrorLog  ${APACHE_LOG_DIR}/quizblast-error.log
    CustomLog ${APACHE_LOG_DIR}/quizblast-access.log combined
</VirtualHost>
```

```bash
sudo a2ensite quizblast
sudo systemctl reload apache2
```

---

## 4. SSL with Let's Encrypt

```bash
sudo apt install -y certbot

# For nginx:
sudo apt install -y python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com

# For Apache:
sudo apt install -y python3-certbot-apache
sudo certbot --apache -d yourdomain.com
```

---

## 5. Create MySQL database and user

```bash
sudo mysql -u root -e "CREATE DATABASE quizblast CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -u root -e "CREATE USER 'quizblast'@'localhost' IDENTIFIED BY 'strongpassword';"
sudo mysql -u root -e "GRANT ALL PRIVILEGES ON quizblast.* TO 'quizblast'@'localhost';"
```

---

## 6. Run the web installer

With the web server running and DNS pointing to your server, visit:

```
https://yourdomain.com/install/
```

The installer will configure `.env`, run migrations, and create your admin account. When it's done, click **Open QuizBlast**.

### CLI alternative (headless)

```bash
cp .env.example .env
# Edit .env: APP_URL, DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

php artisan key:generate
php artisan migrate --force

php artisan tinker --execute="\App\Models\User::create(['name'=>'Admin','email'=>'you@example.com','password'=>bcrypt('yourpassword')]);"

touch install/.installed
php artisan config:cache
```

---

## 7. Run Reverb as a systemd service

Create `/etc/systemd/system/quizblast-reverb.service`:

```ini
[Unit]
Description=QuizBlast Reverb WebSocket Server
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/quizblast
ExecStart=/usr/bin/php artisan reverb:start --host=127.0.0.1 --port=7001
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable quizblast-reverb
sudo systemctl start quizblast-reverb
```

---

## 8. Verify

```bash
curl -I https://yourdomain.com                    # Should return 200
sudo systemctl status quizblast-reverb            # Should be active (running)
tail -f /var/www/quizblast/storage/logs/laravel.log
```

---

## Updating

```bash
cd /var/www/quizblast
sudo -u www-data git pull
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan view:clear
sudo systemctl restart quizblast-reverb

# nginx
sudo systemctl reload nginx
# Apache
sudo systemctl reload apache2
```

---

## Without WebSockets (polling-only mode)

If your server can't run long-lived processes, set:

```env
BROADCAST_DRIVER=log
```

All core game features work via HTTP polling. The lobby player count/ticker and emoji reactions require WebSockets.
