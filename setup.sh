#!/bin/bash
# ============================================================
#  QuizBlast – CLI Setup (for servers without a browser)
#
#  Most users should use the web installer instead:
#    1. composer install
#    2. php artisan serve
#    3. Open http://localhost:8000/install/
#
#  This script is for headless/automated environments.
#  Requires MySQL/MariaDB — edit .env before running.
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
$COMPOSER install --no-interaction --prefer-dist --optimize-autoloader

# 4. Environment
if [ ! -f .env ]; then
  cp .env.example .env
  echo "✓  .env created from example"
  echo ""
  echo "⚠️  Edit .env and set your MySQL credentials before continuing."
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
php artisan storage:link 2>/dev/null || true
mkdir -p storage/framework/{cache,sessions,views}
chmod -R 775 storage bootstrap/cache

# 8. Create installer lock so web installer shows "already installed"
touch install/.installed

echo ""
echo "============================================================"
echo "✅  Setup complete!"
echo ""
echo "   Add an admin account:"
echo "   php artisan tinker"
echo "   > \\App\\Models\\User::create(['name'=>'Admin','email'=>'you@example.com','password'=>bcrypt('yourpassword')])"
echo ""
echo "   Start the dev server:"
echo "   php artisan serve"
echo ""
echo "   (Optional) Enable WebSockets in a second terminal:"
echo "   php artisan reverb:start"
echo "============================================================"
echo ""
