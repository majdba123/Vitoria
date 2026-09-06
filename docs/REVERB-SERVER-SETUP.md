# Laravel Reverb (WebSocket) — Server Setup

This guide documents a safe production-oriented Reverb setup without embedding real infrastructure addresses or credentials in source control.

## Security rules

- Never commit `REVERB_APP_SECRET`, production host IPs, tokens, or other deployment credentials.
- Store production values in the server environment or an approved secrets manager.
- Treat any credential previously committed to Git as compromised and rotate it at the provider/server level.
- Prefer HTTPS/WSS behind a reverse proxy in production.

## 1. Production environment

Configure these values on the server only:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=<reverb-app-id>
REVERB_APP_KEY=<reverb-app-key>
REVERB_APP_SECRET=<reverb-app-secret>

REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

REVERB_HOST=<your-domain.example>
REVERB_PORT=443
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
VITE_REVERB_ENABLED=true
```

After changing runtime configuration:

```bash
npm run build
php artisan config:clear
```

## 2. Run Reverb locally for validation

```bash
php artisan reverb:start
```

Stop the foreground process after validating connectivity, then run Reverb under a process manager in production.

## 3. Supervisor example

```ini
[program:app-reverb]
process_name=%(program_name)s
command=php /var/www/your-app/artisan reverb:start
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/your-app/storage/logs/reverb.log
stopwaitsecs=3600
```

Apply the configuration:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart app-reverb
sudo supervisorctl status app-reverb
```

## 4. Reverse proxy with Nginx

Expose Reverb through your HTTPS domain instead of publishing a raw server IP or unencrypted WebSocket endpoint.

```nginx
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

Validate Nginx before reloading:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## 5. Validation

Check the browser Network panel for a successful WSS connection to your configured domain. Do not publish production credentials, access tokens, or infrastructure addresses in issue screenshots, documentation, logs, or repository files.
