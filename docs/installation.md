# Installation

## Requirements

| Requirement | Minimum | Notes |
|---|---|---|
| PHP | 8.2+ | Extensions: pdo, pdo_sqlite, mbstring, openssl, tokenizer, xml, ctype, json |
| Composer | 2.x | [getcomposer.org](https://getcomposer.org) |
| Database | — | SQLite (bundled with PHP) **or** MySQL 8 / MariaDB 10.3+ |
| Web Server | — | PHP built-in (`php artisan serve`) for local dev; nginx for production |

---

## Option 1 — Automated setup (recommended)

```bash
git clone https://github.com/hr-224/quizblast.git
cd quizblast
bash setup.sh
```

The script will:
1. Verify PHP 8.2+
2. Install/download Composer if missing
3. Run `composer install`
4. Copy `.env.example` → `.env`
5. Generate `APP_KEY`
6. Create `database/database.sqlite`
7. Run all migrations
8. Seed the demo quiz and demo account
9. Fix `storage/` and `bootstrap/cache/` permissions

Then start the development server:

```bash
php artisan serve
```

Open **http://localhost:8000**.

---

## Option 2 — Manual setup

```bash
git clone https://github.com/hr-224/quizblast.git
cd quizblast

# Install PHP dependencies
composer install

# Create environment file
cp .env.example .env
php artisan key:generate

# Create SQLite database
touch database/database.sqlite

# Run migrations and seed demo data
php artisan migrate --seed

# Fix permissions
chmod -R 775 storage bootstrap/cache

# Start
php artisan serve
```

---

## Using MySQL / MariaDB

Edit `.env` before running migrations:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=quizblast
DB_USERNAME=root
DB_PASSWORD=yourpassword
```

Create the database first:

```sql
CREATE DATABASE quizblast CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then run:

```bash
php artisan migrate --seed
```

---

## Demo Account

After seeding, a demo host account is available:

```
Email:    demo@quizblast.app
Password: password
```

It includes a sample quiz with questions to test the full game flow.

---

## Enable Real-Time WebSockets (optional for local dev)

By default, the app works with HTTP polling — no extra setup needed.

To enable WebSockets for live player count, join ticker, and instant updates, start the Reverb server in a second terminal:

```bash
php artisan reverb:start
```

See [websockets.md](websockets.md) for full configuration details.

---

## Next Steps

- [Configuration reference](configuration.md) — all `.env` variables
- [WebSockets setup](websockets.md) — Reverb local and production
- [Self-hosting guide](self-hosting.md) — nginx, SSL, systemd
