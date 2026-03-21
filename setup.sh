#!/bin/bash
# ============================================================
#  QuizBlast – One-Command Setup
#  Usage: bash setup.sh
# ============================================================
set -e

echo ""
echo "⚡  QuizBlast Setup"
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
  echo "✓  .env file created"
fi

# 5. Generate app key
php artisan key:generate --ansi

# 6. Create SQLite database
touch database/database.sqlite
echo "✓  SQLite database created"

# 7. Run migrations + seed
echo ""
echo "🗄   Running migrations..."
php artisan migrate --force

echo ""
echo "🌱  Seeding demo data..."
php artisan db:seed --force

# 8. Fix storage permissions
php artisan storage:link 2>/dev/null || true
mkdir -p storage/framework/{cache,sessions,views}
chmod -R 775 storage bootstrap/cache

echo ""
echo "============================================================"
echo "✅  Setup complete!"
echo ""
echo "   Demo login:"
echo "   Email:    demo@quizblast.app"
echo "   Password: password"
echo ""
echo "   Start the dev server:"
echo "   php artisan serve"
echo ""
echo "   (Optional) Enable real-time WebSockets in a second terminal:"
echo "   php artisan reverb:start"
echo ""
echo "   Then open: http://localhost:8000"
echo ""
echo "   Docs: https://github.com/hr-224/quizblast/tree/master/docs"
echo "============================================================"
echo ""
