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

## How it works

Reverb runs as a background process on a private port (default `7001`). Your web server (nginx or Apache) proxies WebSocket connections from the browser to that process over the standard HTTPS port (443).

```
Browser  ──wss://yourdomain.com/app──►  nginx/Apache (443)
                                               │
                                        proxy_pass ws://
                                               │
                                        Reverb (:7001)
```

---

## Run Reverb as a systemd service

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
sudo systemctl status quizblast-reverb
```

---

## nginx WebSocket proxy

Add this block inside your `server { }` block, **after** the `location /` block:

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

Reload: `sudo nginx -t && sudo systemctl reload nginx`

---

## Apache 2.4 WebSocket proxy

Enable the required modules:

```bash
sudo a2enmod proxy proxy_http proxy_wstunnel
```

Add these directives inside your `<VirtualHost>` block:

```apache
# WebSocket proxy (Reverb)
ProxyRequests Off

<Location /app>
    ProxyPass        ws://127.0.0.1:7001/app
    ProxyPassReverse ws://127.0.0.1:7001/app
</Location>
```

Reload: `sudo systemctl reload apache2`

---

## `.env` configuration

```env
BROADCAST_DRIVER=reverb

# What the browser connects to (your public domain)
REVERB_HOST=yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https

# What the Reverb process binds to (internal only)
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=7001
```

The `REVERB_APP_ID`, `REVERB_APP_KEY`, and `REVERB_APP_SECRET` are generated automatically by the web installer. If setting up manually, use any random strings (key and secret should be hex).

---

## Disabling WebSockets (polling-only mode)

If you don't need real-time lobby or reactions, set:

```env
BROADCAST_DRIVER=log
```

All game features work; the lobby player count/ticker and emoji reactions are the only things that require WebSockets.

---

## Troubleshooting

**Players stuck on "Connecting…"**
- Check Reverb is running: `sudo systemctl status quizblast-reverb`
- Check the proxy block is present and the web server was reloaded
- Confirm `REVERB_HOST` matches your domain exactly (no trailing slash, no port suffix for 443)
- Check your firewall allows port 7001 internally (it should NOT be public — the proxy handles it)

**WebSocket closes immediately**
- Verify `REVERB_APP_KEY` in `.env` matches what's used in the Pusher client (visible in browser DevTools → Network → WS tab)
- Ensure `APP_URL` includes the protocol: `https://yourdomain.com`

**Apache: 502 Bad Gateway on `/app`**
- Confirm `mod_proxy_wstunnel` is enabled: `apache2ctl -M | grep wstunnel`
- Confirm Reverb is running on port 7001: `ss -tlnp | grep 7001`
