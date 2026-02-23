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

### Create a Cloudflare API token

1. Go to https://dash.cloudflare.com/profile/api-tokens
2. Click **Create Token**
3. Use the **Edit zone DNS** template
4. Under Zone Resources, select your domain
5. Copy the token

### Set up environment on the VPS

```bash
cp docker-compose.prod.yml docker-compose.yml
cp .env.example .env
```

Edit `.env` and set:

```
APP_ENV=production
APP_DEBUG=false
APP_HOST=foo.com
APP_URL=https://foo.com
APP_KEY=           # see below
DB_PASSWORD=       # use a strong password
CF_DNS_API_TOKEN=  # your Cloudflare API token
ACME_EMAIL=        # your email for Let's Encrypt
```

### Generate an app key

```bash
docker run --rm dunglas/frankenphp:1-php8.5 php artisan key:generate --show
```

Paste the output as `APP_KEY` in your `.env`.

### Deploy

```bash
docker compose up -d --build
docker compose exec app php artisan migrate --force
```

The first deploy will build the image (installs dependencies, compiles frontend assets) and obtain a Let's Encrypt certificate via Cloudflare DNS — this takes about 30 seconds.

### Deploying updates

```bash
git pull
docker compose up -d --build
docker compose exec app php artisan migrate --force
```

### Adding subdomains (e.g. bar.foo.com)

Add a DNS CNAME record in Cloudflare pointing `bar` to `foo.com` (proxied).  
No Docker or Caddy config changes needed — the wildcard cert (`*.foo.com`) already covers it.  
Handle subdomain routing inside Laravel using route groups.

---

## File structure

```
your-project/
├── Dockerfile                      # Multi-stage: runtime → builder → production
├── .dockerignore
├── Makefile                        # Shortcuts for common commands
├── docker-compose.dev.yml          # Copy to docker-compose.yml for dev
├── docker-compose.prod.yml         # Copy to docker-compose.yml for prod
├── vite.config.js                  # Modified for Docker HMR support
├── .env.example                    # Template — copy to .env and fill in values
├── DOCKER.md                       # This file
└── docker/
    └── frankenphp/
        ├── Caddyfile.dev           # Caddy config for dev (HTTP only)
        ├── Caddyfile.prod          # Caddy config for prod (Let's Encrypt + Cloudflare)
        └── entrypoint.dev.sh       # Dev entrypoint: starts FrankenPHP + Vite together
```
