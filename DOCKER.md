# Docker Setup

This project uses Docker with FrankenPHP (PHP 8.5) and PostgreSQL.  
FrankenPHP handles HTTPS directly in prod — no separate reverse proxy needed.  
Vite runs inside the app container alongside FrankenPHP in dev.

---

## First-time setup for Dev

### 1. For Windows, add your dev domain to your hosts file

Edit `C:\Windows\System32\drivers\etc\hosts` and add the dev domain (must use `.test` TLD):

```
127.0.0.1     mrifqyabdallah.test
```

> **Why `.test`?** The `.local` TLD causes mDNS conflicts on Windows, making the domain resolve to an IPv6 address instead of `127.0.0.1`. Use `.test` to avoid this.

### 2. Run setup

For a fresh install, simply run:

```bash
make dev.init
```

This will:
- Copy `docker-compose.dev.yml` → `docker-compose.yml` (if not exists)
- Copy `.env.example` → `.env` (if not exists)
- Validate `APP_HOST` and `APP_URL`
- Build and start containers
- Wait for the app to be healthy
- Run `composer install`
- Generate `APP_KEY` (if not set)
- Run migrations
- Print your app URL

Otherwise, just run Vite:
```bash
make npm run dev
```

### 4. Access the app

| URL                               | What |
|-----------------------------------|------|
| `http://mrifqyabdallah.test`      | Your Laravel app |
| `http://mrifqyabdallah.test:5173` | Vite dev server (HMR) |

---

## Makefile

Docker compose:

```bash
make d up          # start containers
make d down        # stop containers
make d.rebuild     # stop, rebuild image, start
make d.restart     # stop and start without rebuilding
make d.logs        # tail all logs
make d.logs app    # tail app container logs only
```
Common commands:

```bash
# Artisan
make artisan migrate
make artisan "migrate:fresh --seed" # flags (--) must be quoted
make artisan tinker

# Composer
make composer install
make composer require some/package

# npm
make npm install
make npm run dev

# Shell access
make app           # bash in app container
make postgres      # bash in postgres container
make pg            # psql CLI directly
```

---

## Wipe and start fresh (dev)

```bash
make d.fresh
```

Prompts for confirmation, then removes all containers and volumes (including the database) and rebuilds from scratch.

---

## Production

### Prerequisites on your VPS

- Docker and Docker Compose installed
- Ports 80 and 443 open in your firewall
- Your domain pointed at your VPS IP in Cloudflare

### Cloudflare setup

1. Log in to [Cloudflare](https://dash.cloudflare.com)
2. Add your domain and set the DNS A record to your VPS IP
3. Enable the proxy (orange cloud ☁️)
4. Set SSL/TLS mode to **Full (strict)** under SSL/TLS → Overview
5. Add a wildcard CNAME — name `*`, target `@` (proxied) — for subdomains

### Create a Cloudflare API token

1. Go to https://dash.cloudflare.com/profile/api-tokens
2. Click **Create Token**
3. Use the **Edit zone DNS** template
4. Under Zone Resources, select your domain
5. Copy the token

### Add GitHub Actions secrets

In your GitHub repo, go to Settings → Secrets and variables → Actions, and add:

| Secret | Value |
|--------|-------|
| `VPS_HOST` | Your VPS IP address |
| `VPS_USER` | SSH username (e.g. `root`) |
| `VPS_SSH_KEY` | Your private SSH key |

### Bootstrap the VPS (first time only)

SSH into your VPS and run:

```bash
# Install Docker
curl -fsSL https://get.docker.com | sh

# Login to GHCR, then enter your GitHub Personal Access Token
docker login ghcr.io -u your-github-username

# Clone the repo to get docker-compose.prod.yml
git clone https://github.com/yourusername/yourapp.git /app
cd /app

# Setup compose and env
cp docker-compose.prod.yml docker-compose.yml
cp .env.example .env

nano .env  # fill in all prod values (see below)
```

Required `.env` values for prod:

```
GITHUB_REPOSITORY="mrifqyabdallah/mrifqyabdallah.com"
APP_URL=https://mrifqyabdallah.com
APP_HOST=mrifqyabdallah.com
APP_ENV=production
APP_DEBUG=false

APP_KEY=             # generate locally then copy-paste
DB_PASSWORD=         # use a strong password
CF_DNS_API_TOKEN=    # your Cloudflare API token
ACME_EMAIL=          # your email for Let's Encrypt
```

Optionally, whitelist Cloudflare connection

```bash
chmod +x /app/scripts/update-cloudflare-ips.sh
/app/scripts/update-cloudflare-ips.sh

# Add weekly cron job
(crontab -l 2>/dev/null; echo "0 3 * * 1 /app/scripts/update-cloudflare-ips.sh >> /var/log/update-cloudflare-ips.log 2>&1") | crontab -
```

Then pull and start:

```bash
docker compose pull
docker compose up -d
docker compose exec app php artisan migrate --force
```

### Subsequent deploys

Everything is automated — just push to `main`. GitHub Actions will run static analysis and tests, build and push the image to GHCR, then deploy to your VPS automatically.

### Manual deploy (if needed)

From your VPS:

```bash
cd /app
docker compose pull
docker compose up -d
docker compose exec app php artisan migrate --force
```
