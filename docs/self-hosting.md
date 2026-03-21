# Self-Hosting Guide

Deploy QuizBlast to your own Linux server (Ubuntu / Debian).

---

## Server Requirements

| Component | Minimum |
|---|---|
| OS | Ubuntu 22.04+ / Debian 12+ |
| PHP | 8.2+ with extensions: pdo, pdo_sqlite or pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, curl |
| Web server | nginx |
| Database | SQLite (no setup) **or** MySQL 8+ / MariaDB 10.3+ |
| RAM | 512 MB+ |
| Ports | 80, 443 (public); 7001 (internal, Reverb) |

---

## 1. Install dependencies

```bash
# PHP 8.3 + extensions
sudo apt update
sudo apt install -y php8.3-cli php8.3-fpm php8.3-mbstring php8.3-xml \
    php8.3-sqlite3 php8.3-mysql php8.3-curl php8.3-tokenizer php8.3-ctype \
    nginx git unzip

# Composer
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

cp .env.example .env
php artisan key:generate
```

---

## 3. Configure `.env`

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database (SQLite or MySQL — see docs/configuration.md)
DB_CONNECTION=sqlite

# Sessions
SESSION_DRIVER=database

# WebSockets
BROADCAST_DRIVER=reverb
REVERB_APP_ID=quizblast
REVERB_APP_KEY=changeme_random_hex
REVERB_APP_SECRET=changeme_random_hex
REVERB_HOST=yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=7001
```

---

## 4. Run migrations

```bash
touch database/database.sqlite   # SQLite only
php artisan migrate --force --seed
```

---

## 5. Set permissions

```bash
sudo chown -R www-data:www-data /var/www/quizblast
sudo find /var/www/quizblast -type f -exec chmod 644 {} \;
sudo find /var/www/quizblast -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/quizblast/storage /var/www/quizblast/bootstrap/cache
sudo chmod +x /var/www/quizblast/artisan
```

---

## 6. Configure nginx

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

    root /var/www/quizblast/public;
    index index.php;

    # PHP-FPM
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

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/quizblast /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 7. SSL with Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

---

## 8. Run Reverb as a systemd service

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

## 9. Verify

```bash
# Check nginx
curl -I https://yourdomain.com

# Check Reverb
sudo systemctl status quizblast-reverb

# Check logs
tail -f /var/www/quizblast/storage/logs/laravel.log
```

---

## Updating

```bash
cd /var/www/quizblast
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan view:clear
php artisan config:cache
sudo systemctl restart quizblast-reverb
sudo systemctl reload nginx
```

---

## Shared Hosting (polling-only mode)

If your host doesn't support WebSockets or long-running processes:

```env
BROADCAST_DRIVER=log
```

The game runs entirely via HTTP polling. All game features work except the lobby player count ticker and emoji reactions.
