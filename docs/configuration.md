# Configuration Reference

All configuration is done via the `.env` file in the project root. Copy `.env.example` to get started:

```bash
cp .env.example .env
php artisan key:generate
```

---

## Application

| Variable | Default | Description |
|---|---|---|
| `APP_NAME` | `QuizBlast` | Site name shown in the browser title |
| `APP_ENV` | `local` | Environment: `local`, `production` |
| `APP_KEY` | _(generated)_ | 32-byte encryption key — run `php artisan key:generate` |
| `APP_DEBUG` | `true` | Show detailed errors. Set to `false` in production |
| `APP_URL` | `http://localhost` | Full URL of your site, used for links and WebSocket routing |

---

## Database

| Variable | Default | Description |
|---|---|---|
| `DB_CONNECTION` | `mysql` | Always `mysql` — MySQL and MariaDB both use this driver |
| `DB_HOST` | `127.0.0.1` | Database host |
| `DB_PORT` | `3306` | Database port |
| `DB_DATABASE` | `quizblast` | Database name |
| `DB_USERNAME` | — | Database username |
| `DB_PASSWORD` | — | Database password |

---

## Sessions

| Variable | Default | Description |
|---|---|---|
| `SESSION_DRIVER` | `file` | Where sessions are stored: `file`, `database`, `cookie` |
| `SESSION_LIFETIME` | `120` | Session expiry in minutes |

For production, `SESSION_DRIVER=database` is recommended. Run `php artisan migrate` to create the sessions table.

---

## Cache

| Variable | Default | Description |
|---|---|---|
| `CACHE_DRIVER` | `file` | Cache backend: `file`, `array`, `database`, `redis` |

---

## Broadcasting & WebSockets (Reverb)

QuizBlast uses [Laravel Reverb](https://reverb.laravel.com/) for real-time WebSocket updates. HTTP polling is the fallback when WebSockets are unavailable.

| Variable | Default | Description |
|---|---|---|
| `BROADCAST_DRIVER` | `reverb` | Set to `reverb` to enable WebSockets, `log` to disable |
| `REVERB_APP_ID` | — | Unique identifier for your Reverb application |
| `REVERB_APP_KEY` | — | Public key used by the browser client to connect |
| `REVERB_APP_SECRET` | — | Secret key — keep this private |
| `REVERB_HOST` | `127.0.0.1` | Hostname clients connect to (your domain in production) |
| `REVERB_PORT` | `8080` | WebSocket port for clients (`443` in production with SSL proxy) |
| `REVERB_SCHEME` | `http` | `http` locally, `https` in production |
| `REVERB_SERVER_HOST` | `0.0.0.0` | Interface the Reverb server binds to |
| `REVERB_SERVER_PORT` | `7001` | Port the Reverb process listens on (nginx proxies to this) |

Generate new Reverb credentials:

```bash
php artisan reverb:install
```

Or set them manually to any random strings — `APP_ID` is numeric, `APP_KEY` and `APP_SECRET` are hex strings.

---

## Mail

| Variable | Default | Description |
|---|---|---|
| `MAIL_MAILER` | `log` | Mail driver. `log` writes to `storage/logs/laravel.log` — no real emails |

QuizBlast does not currently send email. This setting only affects any future mail features.

---

## Queue

| Variable | Default | Description |
|---|---|---|
| `QUEUE_CONNECTION` | `sync` | Job driver. `sync` runs jobs immediately in the request — no worker needed |

---

## Production Checklist

When deploying to a public server:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

SESSION_DRIVER=database

REVERB_HOST=yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_PORT=7001
```

Quiz banner uploads need PHP's `upload_max_filesize` and `post_max_size` to be at least 3M (PHP's default `upload_max_filesize` is exactly 2M), nginx's `client_max_body_size` to be at least 3m, and `public/uploads/` to be writable by the web server user.

See [self-hosting.md](self-hosting.md) for the full production deployment guide.
