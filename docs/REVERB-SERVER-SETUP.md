# Laravel Reverb (WebSocket) – server setup

Run these on the server (e.g. Ubuntu at 62.84.188.239). Reverb is already in the project; you only need to configure it and run it.

---

## 1. Server .env (production)

In `/var/www/MSZ/.env` set or add:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=242425
REVERB_APP_KEY=xug8lrn04c6zewgn12lk
REVERB_APP_SECRET=bzsq8iufmi4sbmoxfc4a

# Reverb server (listens on this host/port)
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

# What the browser connects to (your server IP or domain)
REVERB_HOST=62.84.188.239
REVERB_PORT=8080
REVERB_SCHEME=http

# Frontend (Vite) – same values so built JS has correct WebSocket URL
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
VITE_REVERB_ENABLED=true
```

After changing `.env`, rebuild frontend so Vite gets the right host/port:

```bash
cd /var/www/MSZ && npm run build
php artisan config:clear
```

---

## 2. Run Reverb once (test)

```bash
cd /var/www/MSZ
php artisan reverb:start
```

You should see something like: `Reverb server started on 0.0.0.0:8080`.  
Stop it with Ctrl+C. Then run it in the background with Supervisor (step 3).

---

## 3. Run Reverb with Supervisor (keeps it running)

**Install Supervisor (if not installed):**

```bash
sudo apt update
sudo apt install supervisor -y
```

**Create Reverb config:**

```bash
sudo nano /etc/supervisor/conf.d/msz-reverb.conf
```

Paste (adjust paths if your app is not in `/var/www/MSZ`):

```ini
[program:msz-reverb]
process_name=%(program_name)s
command=php /var/www/MSZ/artisan reverb:start
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/MSZ/storage/logs/reverb.log
stopwaitsecs=3600
```

**Start and enable:**

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start msz-reverb
sudo supervisorctl status msz-reverb
```

**Useful commands:**

```bash
sudo supervisorctl restart msz-reverb   # restart after code/config change
sudo supervisorctl stop msz-reverb
sudo supervisorctl start msz-reverb
tail -f /var/www/MSZ/storage/logs/reverb.log
```

---

## 4. Open port 8080 (firewall)

If UFW is enabled:

```bash
sudo ufw allow 8080/tcp
sudo ufw reload
sudo ufw status
```

---

## 5. Optional: proxy WebSocket through Nginx (port 80)

If you want clients to connect to `ws://62.84.188.239/app/KEY` (port 80) instead of port 8080, add this **inside** the MSZ `server { }` block in `nginx-msz-standalone.conf`:

```nginx
# Reverb WebSocket proxy
location /app/ {
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_pass http://127.0.0.1:8080;
}
```

Then in `.env` set:

```env
REVERB_HOST=62.84.188.239
REVERB_PORT=80
REVERB_SCHEME=http
```

And `VITE_REVERB_PORT=80`, rebuild assets, reload Nginx. Most setups can keep port 8080 and skip this.

---

## 6. Check from browser

Open the MSZ site; the app uses Echo to connect to Reverb. In DevTools → Network, filter by WS and confirm a connection to `ws://62.84.188.239:8080` (or port 80 if you proxied).

---

## Summary

| Step | Command / action |
|------|-------------------|
| 1 | Set REVERB_* and VITE_REVERB_* in `/var/www/MSZ/.env`, then `npm run build` and `php artisan config:clear` |
| 2 | Test: `php artisan reverb:start` (Ctrl+C to stop) |
| 3 | Install supervisor, add `/etc/supervisor/conf.d/msz-reverb.conf`, `supervisorctl update` and `start msz-reverb` |
| 4 | `sudo ufw allow 8080/tcp` if using UFW |
| 5 | Optional: Nginx proxy for port 80 |
