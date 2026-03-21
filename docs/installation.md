# Installation

## Requirements

| Requirement | Minimum | Notes |
|---|---|---|
| PHP | 8.2+ | Extensions: pdo, pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, curl |
| Composer | 2.x | [getcomposer.org](https://getcomposer.org) |
| Database | — | MySQL 8+ or MariaDB 10.3+ |
| Web Server | — | PHP built-in (`php artisan serve`) for local dev; nginx for production |

---

## Web Installer (recommended)

QuizBlast includes a browser-based setup wizard. No command line knowledge required beyond cloning the repo.

### 1. Clone and install dependencies

```bash
git clone https://github.com/hr-224/quizblast.git
cd quizblast
composer install
```

### 2. Start the development server

```bash
php artisan serve
```

### 3. Open the installer

Visit **http://localhost:8000/install/** in your browser.

If you navigate to `http://localhost:8000` without a configured `.env`, you'll be redirected to the installer automatically.

### Installer steps

**Step 1 — Requirements check**
The installer verifies your PHP version, required extensions, and that the `storage/` and `bootstrap/cache/` directories are writable. Fix any red items before proceeding.

**Step 2 — Database**
Enter your MySQL/MariaDB credentials. Use "Test Connection" to verify before continuing. The installer will create the database if it doesn't already exist.

**Step 3 — Site settings**
Set your site name, URL, and create your admin account. Optionally include sample quiz content (5 questions) to test the game immediately.

**Step 4 — Install**
Click "Install →" and watch the installer run migrations and set up your account. Takes 10–30 seconds.

Once complete, click **Open QuizBlast** to go directly to the app.

---

## Manual / CLI Setup

For headless servers or automated deployments:

```bash
git clone https://github.com/hr-224/quizblast.git
cd quizblast
composer install

# Copy and edit config
cp .env.example .env
# → Set DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD, APP_URL

php artisan key:generate
php artisan migrate --force

# Create admin account
php artisan tinker
> \App\Models\User::create(['name'=>'Admin','email'=>'you@example.com','password'=>bcrypt('yourpassword')])

# Fix permissions
chmod -R 775 storage bootstrap/cache

# Mark as installed so web installer shows "already installed"
touch install/.installed
```

---

## Enable Real-Time WebSockets (optional for local dev)

Start the Reverb WebSocket server in a second terminal:

```bash
php artisan reverb:start
```

Without Reverb, the app uses HTTP polling — all game features work, but the lobby player count, join ticker, and emoji reactions require WebSockets.

See [websockets.md](websockets.md) for production configuration.

---

## Next Steps

- [Configuration reference](configuration.md) — all `.env` variables explained
- [WebSockets setup](websockets.md) — Reverb local and production
- [Self-hosting guide](self-hosting.md) — nginx, SSL, systemd on your own server
