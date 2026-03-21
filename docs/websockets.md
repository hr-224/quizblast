# WebSockets (Laravel Reverb)

QuizBlast uses [Laravel Reverb](https://reverb.laravel.com/) for real-time WebSocket communication. A **polling fallback** is built in — if WebSockets are unavailable, the game still works via HTTP polling every 1.5 seconds.

---

## What WebSockets power

| Feature | WebSocket | Polling fallback |
|---|---|---|
| Game state changes (question start, reveal, end) | ✅ | ✅ |
| Live player count in lobby | ✅ | ❌ |
| Join ticker ("Alex just joined") | ✅ | ❌ |
| Emoji reactions | ✅ | ❌ |
| Power-up notifications | ✅ | ❌ |
| Answer count bar chart | ✅ | ✅ |

---

## Local Development

Start the Reverb WebSocket server alongside `php artisan serve`:

```bash
# Terminal 1 — web server
php artisan serve

# Terminal 2 — WebSocket server
php artisan reverb:start
```

The Reverb server starts on port **8080** by default. Your `.env` should have:

```env
BROADCAST_DRIVER=reverb
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
```

### Disabling WebSockets (polling-only mode)

If you don't need real-time features locally, set:

```env
BROADCAST_DRIVER=log
```

This silently logs broadcast events instead of sending them — the game works fine via polling.

---

## Production

In production, Reverb runs as a background process on a private port, and nginx proxies WebSocket connections over SSL.

### 1. Configure `.env`

```env
BROADCAST_DRIVER=reverb

REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret

# What the browser connects to (your public domain)
REVERB_HOST=yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https

# What the Reverb process binds to (internal)
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=7001
```

### 2. Add nginx WebSocket proxy block

Inside your nginx `server` block (after your existing `location /` block):

```nginx
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
```

### 3. Run Reverb as a systemd service

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

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl daemon-reload
sudo systemctl enable quizblast-reverb
sudo systemctl start quizblast-reverb

# Check status
sudo systemctl status quizblast-reverb
```

### 4. Verify

```bash
# Check the process is running
sudo systemctl status quizblast-reverb

# Test WebSocket connection (requires wscat: npm i -g wscat)
wscat -c "wss://yourdomain.com/app/your_app_key"
```

---

## Troubleshooting

**Players stuck on "Connecting…"**
- Check Reverb is running: `systemctl status quizblast-reverb`
- Check nginx WebSocket proxy block is present and nginx was reloaded
- Ensure `REVERB_HOST` matches your domain exactly (no trailing slash)
- Check firewall: port 7001 must be open internally (not publicly — nginx proxies it)

**WebSocket connection closes immediately**
- Check `REVERB_APP_KEY` in `.env` matches what the browser sends (visible in browser DevTools → Network → WS tab)
- Ensure `APP_URL` is set to your full domain with protocol (`https://yourdomain.com`)

**Fallback to polling**
- If Reverb is not running, the game automatically falls back to polling — game still works, just without the lobby count/ticker/reactions
- Check browser console for WebSocket connection errors
