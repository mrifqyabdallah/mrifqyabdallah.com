# Docker Setup

This project uses Docker with FrankenPHP (PHP 8.5) and PostgreSQL.  
FrankenPHP handles HTTPS directly — no separate reverse proxy needed.

There are two compose files — one for dev, one for prod. Neither is committed as `docker-compose.yml`; you copy the one you need.

---

## Development (WSL + mrifqyabdallah.local)

### 1. Add mrifqyabdallah.local to your hosts file

On Windows, open Notepad as Administrator and edit `C:\Windows\System32\drivers\etc\hosts`.  
Add this line:

```
127.0.0.1 mrifqyabdallah.local
```

### 2. Set up environment

```bash
cp .env.example .env
```

The defaults in `.env.example` are ready for dev — you only need to run `php artisan key:generate` (step 4).

### 3. Start the containers

If you're running a fresh install, first run composer install through a temporary container
```bash
docker run --rm -v $(pwd):/app -w /app composer:2 composer install
```

Then spin up the container
```bash
cp docker-compose.dev.yml docker-compose.yml
docker compose up
```

First run will take a few minutes as images are pulled and npm packages are installed.

### 4. First-time Laravel setup

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

### 5. Access the app

| URL | What |
|-----|------|
| https://mrifqyabdallah.local | Your Laravel app |
| http://mrifqyabdallah.local:5173 | Vite dev server (HMR) |

Your browser will show a security warning for `mrifqyabdallah.local` because the certificate is self-signed.  
Click **Advanced → Proceed** (Chrome) or **Accept the Risk** (Firefox). You only need to do this once.

---

## Production

### Prerequisites on your VPS

- Docker and Docker Compose installed
- Ports 80 and 443 open in your firewall
- Your domain (foo.com) pointed at your VPS IP in Cloudflare

### Cloudflare setup

1. Log in to [Cloudflare](https://dash.cloudflare.com)
2. Add your domain and set the DNS A record to your VPS IP
3. Enable the proxy (orange cloud ☁️)
4. Set SSL/TLS mode to **Full (strict)** under SSL/TLS settings

### Create a Cloudflare API token

1. Go to https://dash.cloudflare.com/profile/api-tokens
2. Click **Create Token**
3. Use the **Edit zone DNS** template
4. Under Zone Resources, select your domain (foo.com)
5. Copy the token

### 1. Set up environment on the VPS

```bash
cp .env.example .env
```

Edit `.env` and set:

```
APP_ENV=production
APP_DEBUG=false
APP_HOST=foo.com
APP_URL=https://foo.com
APP_KEY=          # see step 2
DB_PASSWORD=      # use a strong password
CF_DNS_API_TOKEN= # your Cloudflare API token
ACME_EMAIL=       # your email for Let's Encrypt
```

### 2. Generate an app key

```bash
docker run --rm dunglas/frankenphp:latest-php8.5 php artisan key:generate --show
```

Paste the output as `APP_KEY` in your `.env`.

### 3. Deploy

```bash
cp docker-compose.prod.yml docker-compose.yml
docker compose up -d --build
```

The first deploy will build the image (installs dependencies, compiles frontend assets) and obtain a Let's Encrypt certificate via Cloudflare DNS — this takes about 30 seconds.

### 4. Run migrations

```bash
docker compose exec app php artisan migrate --force
```

### Deploying updates

```bash
git pull
docker compose up -d --build
docker compose exec app php artisan migrate --force
```

### Adding subdomains (e.g. bar.foo.com)

Add a DNS CNAME record in Cloudflare pointing `bar` to `foo.com` (proxied).  
No changes needed to the Docker or Caddy config — the wildcard cert (`*.foo.com`) already covers it.  
Handle the subdomain routing inside Laravel using route groups.

---

## Useful commands

```bash
# View logs
docker compose logs -f

# Open a shell in the app container
docker compose exec app bash

# Run artisan commands
docker compose exec app php artisan <command>

# Clear caches (prod)
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan optimize
```

---

## File structure

```
your-project/
├── Dockerfile                      # Production image build
├── .dockerignore
├── docker-compose.dev.yml          # Copy to docker-compose.yml for dev
├── docker-compose.prod.yml         # Copy to docker-compose.yml for prod
├── vite.config.js                  # Modified for Docker HMR support
├── .env.example                    # Template — copy to .env and fill in values
├── DOCKER.md                       # This file
└── docker/
    └── frankenphp/
        ├── Caddyfile.dev           # Caddy config for dev (self-signed cert)
        └── Caddyfile.prod          # Caddy config for prod (Let's Encrypt + Cloudflare)
```
