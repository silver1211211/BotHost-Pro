# Private Beta VPS Checklist

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL` is the real HTTPS domain
- [ ] MySQL database and user are configured
- [ ] Redis is configured with password protection
- [ ] `CACHE_STORE=redis`
- [ ] `QUEUE_CONNECTION=redis`
- [ ] `NODE_RUNTIME_SECRET` is rotated and not reused from local development
- [ ] Supervisor is running the Laravel queue worker
- [ ] Supervisor is running the Node runtime
- [ ] Cron `schedule:run` is installed
- [ ] SSL certificate is active
- [ ] Telegram webhook is set successfully
- [ ] `/start` works in Telegram
- [ ] Support flow works
- [ ] Callback button works
- [ ] OxaPay secret is configured only if payments are enabled
- [ ] Backups are configured and restore has been tested
