---
title: FrankenPHP Classic Mode for Laravel
creator: mrifqyabdallah
tags: [laravel, docker, frankenphp, php]
excerpt: FrankenPHP gets a lot of attention for its worker mode, but the classic mode is a perfectly valid choice that gives you Caddy's benefits without touching your bootstrap code.
---

> This post is part of a series documenting how I built and deployed [mrifqyabdallah.com](https://mrifqyabdallah.com). The full source is on [GitHub](https://github.com/mrifqyabdallah/mrifqyabdallah.com).

# FrankenPHP Classic Mode for Laravel

## What is FrankenPHP?

FrankenPHP is a PHP runtime built on top of Caddy. Instead of running a separate web server (Nginx, Apache) alongside PHP-FPM, FrankenPHP bundles everything into a single binary: the web server, the PHP runtime, and automatic HTTPS via Let's Encrypt.

It supports two modes

- Worker mode: Boots your Laravel app once and keeps it in memory, handling requests in a persistent process. This can significantly improve performance but requires you to be careful about shared state between requests and memory leaks.
- Classic mode: Works just like traditional PHP-FPM where each request boots the application from scratch. No shared state concerns, no bootstrap rewrite needed.

Most tutorials focus on worker mode because it's the headline feature. Classic mode doesn't get much attention, but it's a legitimate choice, especially if you want the Caddy benefits (automatic HTTPS, HTTP/2, HTTP/3 out of the box) without modifying your existing application.

## Why Classic Mode

The practical reason is simple: classic mode is a drop-in replacement for PHP-FPM + Nginx/Apache. Your existing Laravel app works as-is. No need to think about what gets shared between requests, no need to reset state after each cycle, no risk of one request's data leaking into the next.

You still get everything Caddy brings to the table. Automatic TLS certificate provisioning and renewal, HTTP/2 and HTTP/3 support, and a clean single-binary deployment. For a personal site or a project where you want to learn containerization without also learning FrankenPHP's worker model, classic mode is a solid starting point. You can always migrate to worker mode later once you're comfortable.

## Dev Setup

The Dockerfile uses a multi-stage build. The base stage (`runtime`) pulls from `dunglas/frankenphp:1-php8.5` and installs the system packages and PHP extensions the app needs (Laravel 12 with cron for scheduler and npm for React/Inertia):

```dockerfile
FROM dunglas/frankenphp:1-php8.5 AS runtime

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev curl gnupg supervisor cron \
    && curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions \
    pdo_pgsql pgsql opcache intl zip pcntl bcmath
```

The `dev` stage extends `runtime` and adds a non-root user (`devuser`) with a matching UID/GID so files created inside the container aren't owned by root on your host machine:

```dockerfile
FROM runtime AS dev

ARG UID=1000
ARG GID=1000

RUN groupadd --gid ${GID} devuser \
    && useradd --uid ${UID} --gid ${GID} --create-home --shell /bin/bash devuser \
    && echo "devuser ALL=(root) NOPASSWD: /bin/rm -f /var/run/cron*.pid, /usr/sbin/service cron start" \
    > /etc/sudoers.d/devuser-root
```

The codebase is volume-mounted into the container, so the app code lives on your host and the container just runs it. This is why the dev entrypoint lazily installs composer and npm dependencies on first run rather than baking them in: the `vendor/` and `node_modules/` directories don't exist yet when the image is built.

```sh
if [ ! -d "vendor" ]; then
    composer install
fi

if [ ! -d "node_modules" ]; then
    npm install
fi
```

The Caddyfile for dev is intentionally minimal: HTTP only, pointing to `public/`, no TLS configuration needed locally.

```
http://{$APP_HOST:mrifqyabdallah.test} {
    root * /app/public
    php_server
    file_server
}
```

Notice there's no `worker` directive anywhere. That's all it takes to run in classic mode. `php_server` handles PHP requests the traditional way, `file_server` serves static assets directly.

In dev, supervisord manages three processes: FrankenPHP, the Vite dev server, and the queue worker. All three run inside the same container.

## Prod Setup

Production uses two more stages on top of `runtime`. The `builder` stage installs all dependencies, compiles frontend assets, then strips out everything that isn't needed at runtime:

```dockerfile
FROM runtime AS builder

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN composer dump-autoload --optimize && \
    composer run-script post-autoload-dump || true

RUN chmod -R 775 bootstrap/cache storage && \
    cp .env.example .env && \
    php artisan key:generate && \
    npm run build && \
    rm -rf node_modules .env
```

The `production` stage then copies the built app from `builder` into a clean `runtime` image. No build tools, no dev dependencies, no Node:

```dockerfile
FROM runtime AS production

COPY --from=builder /app /app

RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/public /data && \
    chmod -R 775 /app/storage /app/bootstrap/cache
```

The prod Caddyfile is almost identical to dev but adds automatic HTTPS via Let's Encrypt, driven by the `ACME_EMAIL` environment variable:

```
{
    admin off
    email {$ACME_EMAIL}
}

{$APP_HOST}, www.{$APP_HOST} {
    root * /app/public
    php_server
    file_server
}
```

Still no `worker` directive. Classic mode, Caddy handling TLS automatically.

> Since my VPS sits behind Cloudflare (set to Full SSL mode), Cloudflare handles the TLS that users actually see. So whether Caddy is actively provisioning its own cert behind that or not, is something I haven't fully verified.

In prod, supervisord manages two processes (no Vite): FrankenPHP running as `www-data`, and the queue worker also running as `www-data`. The container itself runs as root so the cron daemon can start, but the actual application processes drop to `www-data` via supervisord's per-program `user=` directive.

The prod entrypoint handles the boot sequence before handing off to supervisord:

```sh
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize
php artisan queue:restart

cron

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
```

OPcache is also configured for production via a dedicated `opcache.ini` copied into the image, which significantly reduces PHP's per-request overhead by caching compiled bytecode. Additionally, `php.ini` enables JIT and disable `zend.assertions`.

## What to Put in `.dockerignore`

`.dockerignore` controls what gets sent to the Docker build context. People often skip this and end up with slow builds or, worse, a local `.env` accidentally included in the image.

Key entries to include:

```
node_modules/
vendor/
.env
.git/
storage/logs/
storage/app/
```

None of these belong in the image. `vendor/` and `node_modules/` get reinstalled during the build anyway, `.env` should never be baked in, and `.git/` just bloats the context. A smaller build context also means faster `docker build` runs.

## Gotchas

- **`www-data` ownership.** Laravel needs write access to `storage/` and `bootstrap/cache/`. In the production stage, `chown -R www-data:www-data` covers those directories plus `public/` and `/data` (/data belongs to Caddy), and `chmod -R 775` ensures `storage/` and `bootstrap/cache/` are writable.
- **The `/data` directory.** FrankenPHP's underlying Caddy server stores TLS certificates and other persistent data in `/data`. In production, this is mounted as a named volume (`caddy_data`) so certificates survive container restarts. Without this, the container would request a new certificate on every restart and you'd quickly hit Let's Encrypt's rate limits.
- **UID/GID in dev.** Without matching the `devuser` UID/GID to your host machine's user (typically `1000:1000`), files created inside the container (like `storage/logs/` entries, cached views, or vendor and node_modules directory) end up owned by root on your host, which makes them annoying to edit or delete. The `ARG UID` and `ARG GID` in the dev stage, passed through from docker-compose, solve this.

# Wrapping Up

Classic mode won't win any benchmarks against worker mode, but it's the right call when you want the Caddy ecosystem without the overhead of rethinking your application lifecycle. It's also much easier to reason about, which matters when you're still getting comfortable with containerization.

The full setup is available in the [repo](https://github.com/mrifqyabdallah/mrifqyabdallah.com). Give it a try and let know how you do it!
