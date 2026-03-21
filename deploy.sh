#!/bin/bash
# ============================================================
#  QuizBlast — Server Deploy Script
#  Domain:  quizblast.ultmods.com
#  Webroot: /var/www/quizblast
#  Run as root or a user with sudo
# ============================================================
set -e

DEPLOY_DIR="/var/www/quizblast"
NGINX_CONF="/etc/nginx/sites-available/quizblast.ultmods.com"
NGINX_LINK="/etc/nginx/sites-enabled/quizblast.ultmods.com"
WEB_USER="www-data"

echo ""
echo "⚡  QuizBlast — Deploying to $DEPLOY_DIR"
echo "============================================================"

# 1. Create webroot
echo "→ Creating webroot..."
mkdir -p "$DEPLOY_DIR"

# 2. Copy project files (run from the directory containing kahoot-clone/)
echo "→ Copying project files..."
cp -r kahoot-clone/. "$DEPLOY_DIR/"

# 3. Install Composer dependencies
echo "→ Installing Composer dependencies..."
cd "$DEPLOY_DIR"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# 4. Set up .env
if [ ! -f "$DEPLOY_DIR/.env" ]; then
    cp "$DEPLOY_DIR/.env.example" "$DEPLOY_DIR/.env"
    echo "→ .env created from example"
fi

# Set APP_URL
sed -i 's|APP_URL=.*|APP_URL=https://quizblast.ultmods.com|' "$DEPLOY_DIR/.env"
sed -i 's|APP_ENV=.*|APP_ENV=production|' "$DEPLOY_DIR/.env"
sed -i 's|APP_DEBUG=.*|APP_DEBUG=false|' "$DEPLOY_DIR/.env"

# 5. Generate app key if not set
if ! grep -q "^APP_KEY=base64:" "$DEPLOY_DIR/.env"; then
    echo "→ Generating app key..."
    php artisan key:generate --ansi
fi

# 6. Create SQLite DB if using sqlite
if grep -q "DB_CONNECTION=sqlite" "$DEPLOY_DIR/.env"; then
    touch "$DEPLOY_DIR/database/database.sqlite"
    echo "→ SQLite database created"
fi

# 7. Run migrations + seed
echo "→ Running migrations..."
php artisan migrate --force

echo "→ Seeding demo data..."
php artisan db:seed --force

# 8. Storage setup
echo "→ Linking storage..."
php artisan storage:link 2>/dev/null || true
mkdir -p "$DEPLOY_DIR/storage/framework/"{cache,sessions,views}
mkdir -p "$DEPLOY_DIR/storage/logs"
touch "$DEPLOY_DIR/storage/logs/laravel.log"

# 9. Set permissions
echo "→ Setting permissions..."
chown -R "$WEB_USER":"$WEB_USER" "$DEPLOY_DIR"
find "$DEPLOY_DIR" -type f -exec chmod 644 {} \;
find "$DEPLOY_DIR" -type d -exec chmod 755 {} \;
chmod -R 775 "$DEPLOY_DIR/storage" "$DEPLOY_DIR/bootstrap/cache"
chmod +x "$DEPLOY_DIR/artisan"

# 10. Nginx config
echo "→ Installing nginx config..."
cp "$(dirname "$0")/quizblast.ultmods.com.conf" "$NGINX_CONF"
ln -sf "$NGINX_CONF" "$NGINX_LINK"

# 11. Test & reload nginx
echo "→ Testing nginx config..."
nginx -t

echo "→ Reloading nginx..."
systemctl reload nginx

echo ""
echo "============================================================"
echo "✅  Deployed!"
echo ""
echo "   Site:    https://quizblast.ultmods.com"
echo ""
echo "   Demo login:"
echo "   Email:    demo@quizblast.app"
echo "   Password: password"
echo "============================================================"
echo ""
