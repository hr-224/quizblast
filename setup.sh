#!/bin/bash
# ============================================================
#  QuizBlast – CLI Setup (headless / automated environments)
#
#  For a guided browser-based install, use the web installer:
#    1. composer install
#    2. Point nginx or Apache at the public/ directory
#    3. Open http://yourdomain.com/install/ in your browser
#
#  See docs/installation.md for web server configuration.
#  This script is for servers without a browser.
# ============================================================
set -e

echo ""
echo "⚡  QuizBlast CLI Setup"
echo "============================================================"

# 1. Check PHP
if ! command -v php &>/dev/null; then
  echo "❌ PHP not found. Install PHP 8.2+ and try again."
  exit 1
fi
PHP_VER=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "✓  PHP $PHP_VER detected"

# 2. Check Composer
if ! command -v composer &>/dev/null; then
  echo "⬇  Composer not found. Downloading..."
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php composer-setup.php --quiet
  php -r "unlink('composer-setup.php');"
  COMPOSER="php composer.phar"
else
  COMPOSER="composer"
  echo "✓  Composer detected"
fi

# 3. Install dependencies
echo ""
echo "📦  Installing PHP dependencies..."
$COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# 4. Environment
if [ ! -f .env ]; then
  cp .env.example .env
  echo "✓  .env created from example"
  echo ""
  echo "⚠️  Edit .env and configure APP_URL and your MySQL credentials."
  echo "    Then re-run this script."
  exit 0
fi

# 5. Generate app key
php artisan key:generate --ansi

# 6. Run migrations
echo ""
echo "🗄   Running migrations..."
php artisan migrate --force

# 7. Fix storage permissions
mkdir -p storage/framework/{cache,sessions,views}
chmod -R 775 storage bootstrap/cache

# 8. Cache config
php artisan config:cache

# 9. Write installer lock
touch install/.installed

echo ""
echo "============================================================"
echo "✅  Setup complete!"
echo ""
echo "   Create your admin account:"
echo "   php artisan tinker"
echo "   > \\App\\Models\\User::create(['name'=>'Admin','email'=>'you@example.com','password'=>bcrypt('yourpassword')])"
echo ""
echo "   Ensure nginx or Apache is pointing to: $(pwd)/public"
echo ""
echo "   (Optional) Start WebSocket server:"
echo "   php artisan reverb:start --host=127.0.0.1 --port=7001"
echo ""
echo "   Docs: https://github.com/hr-224/quizblast/tree/master/docs"
echo "============================================================"
echo ""
