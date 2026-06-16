# BotHost Pro Git Deployment

These notes are for a private beta VPS using Git-based deployment. Replace every `CHANGE_ME_*`, `/var/www/bothostpro`, `www-data`, `/usr/bin/php`, `/usr/bin/node`, `production`, and `https://app.example.com` placeholder with real server values. Do not commit live `.env` files or secrets.

## 1. Server Packages

Install PHP 8.3 or newer, Composer, Node.js, MySQL, Redis, Supervisor, cron, and Nginx:

```bash
sudo apt update
sudo apt install nginx mysql-server redis-server supervisor cron unzip git \
  php8.3-cli php8.3-fpm php8.3-bcmath php8.3-curl php8.3-mbstring \
  php8.3-mysql php8.3-redis php8.3-xml php8.3-zip
```

Confirm the binary paths for the supervisor and cron examples:

```bash
which php
which node
which composer
```

## 2. Clone And Checkout

Clone the repository on the VPS and check out the production branch:

```bash
sudo mkdir -p /var/www
sudo chown www-data:www-data /var/www
cd /var/www
git clone CHANGE_ME_GIT_REPOSITORY_URL bothostpro
cd /var/www/bothostpro
git checkout production
```

Use the actual branch name if it is not `production`.

## 3. Environment

Create the live `.env` manually from the example, then edit it on the server:

```bash
cp deploy/env.production.example .env
nano .env
```

Set the real HTTPS `APP_URL`, MySQL credentials, Redis password, mail settings, and a rotated `NODE_RUNTIME_SECRET`. If the server does not resolve Node.js as `node`, set `NODE_BINARY=/usr/bin/node` or the correct absolute path. Generate the Laravel app key after `.env` exists:

```bash
php artisan key:generate --force
```

Never commit `.env`, generated app keys, API keys, bot tokens, database passwords, Redis passwords, or payment secrets.

## 4. Dependencies And Build

Install production PHP dependencies, build frontend assets, and install the Node runtime dependencies:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
cd runtime-node
npm ci --omit=dev
cd ..
```

## 5. MySQL

Create the production database and user, then match the values in `.env`:

```sql
CREATE DATABASE bothostpro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bothostpro'@'localhost' IDENTIFIED BY 'CHANGE_ME_MYSQL_PASSWORD';
GRANT ALL PRIVILEGES ON bothostpro.* TO 'bothostpro'@'localhost';
FLUSH PRIVILEGES;
```

Run migrations only after the live `.env` points to the production database:

```bash
php artisan migrate --force
php artisan db:seed --class=RuntimeHelperCategorySeeder --force
```

## 6. Redis

For host-installed Redis, require a password and keep Redis bound to localhost in `/etc/redis/redis.conf`:

```text
bind 127.0.0.1 ::1
protected-mode yes
requirepass CHANGE_ME_REDIS_PASSWORD
supervised systemd
```

Restart Redis and verify it locally:

```bash
sudo systemctl restart redis-server
redis-cli -a CHANGE_ME_REDIS_PASSWORD ping
```

If using `docker-compose.runtime.yml`, set `REDIS_PASSWORD` in the deployment environment or live `.env`. The compose example publishes Redis as `127.0.0.1:6379:6379` only and requires a password.

## 7. Laravel Optimize

Cache Laravel production artifacts and link storage:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

## 8. Supervisor

Copy the supervisor examples, replace placeholders, then reload Supervisor:

```bash
sudo cp deploy/supervisor/bothostpro-worker.conf.example /etc/supervisor/conf.d/bothostpro-worker.conf
sudo cp deploy/supervisor/bothostpro-runtime.conf.example /etc/supervisor/conf.d/bothostpro-runtime.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart bothostpro-worker:*
sudo supervisorctl restart bothostpro-runtime:*
```

The queue worker should run from the project root. The Node runtime should run `runtime-node/server.js` on `HOST=127.0.0.1`.

## 9. Cron

Install the Laravel scheduler cron:

```bash
sudo cp deploy/cron/bothostpro-scheduler.example /etc/cron.d/bothostpro-scheduler
sudo chmod 0644 /etc/cron.d/bothostpro-scheduler
```

The scheduler must run every minute so Laravel scheduled jobs, including `audit-logs:prune`, can run.

## 10. Deploy Updates

For later Git deployments:

```bash
cd /var/www/bothostpro
git fetch --all --prune
git checkout production
git pull --ff-only
composer install --no-dev --optimize-autoloader
npm ci
npm run build
cd runtime-node && npm ci --omit=dev && cd ..
php artisan migrate --force
php artisan db:seed --class=RuntimeHelperCategorySeeder --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
sudo supervisorctl restart bothostpro-worker:*
sudo supervisorctl restart bothostpro-runtime:*
```

After publishing an admin helper bundle, restart the Node runtime so non-Docker local runtimes load the new `runtime-node/admin-helpers-generated.js` file:

```bash
sudo supervisorctl restart bothostpro-runtime:*
```

For Docker runtimes, run the admin Docker refresh dry-run first, review the report for unknown containers, and run live refresh only with the exact confirmation text:

```text
YES_RECREATE_DOCKER_CONTAINERS
```

## 11. Webhook Verification

Verify the Node runtime is local-only:

```bash
curl http://127.0.0.1:8787/health
```

Confirm Telegram webhook URLs use HTTPS and the production `APP_URL`. Webhooks must not point at localhost, a raw IP, or plain HTTP in production.
