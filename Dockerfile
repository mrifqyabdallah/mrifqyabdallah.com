# =============================================================================
# Runtime stage: base image with PHP extensions + Node
# Used directly in dev (via volume mount) and as base for prod build
# =============================================================================
FROM dunglas/frankenphp:1-php8.5 AS runtime

WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    curl \
    gnupg \
    supervisor \
    cron \
    && curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    opcache \
    intl \
    zip \
    pcntl \
    bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# =============================================================================
# Dev stage: runtime + a user that matches your machine's uid/gid (1000:1000)
# =============================================================================
FROM runtime AS dev
ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
        sudo \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions pcov

RUN groupadd --gid ${GID} devuser \
    && useradd --uid ${UID} --gid ${GID} --create-home --shell /bin/bash devuser \
    && echo "devuser ALL=(root) NOPASSWD: /bin/rm -f /var/run/cron*.pid, /usr/sbin/service cron start" \
        > /etc/sudoers.d/devuser-root \
    && chmod 0440 /etc/sudoers.d/devuser-root

COPY docker/cron/dev /etc/cron.d/laravel
RUN chmod 0644 /etc/cron.d/laravel

USER devuser

# =============================================================================
# Build stage: install dependencies and compile assets for production
# =============================================================================
FROM runtime AS builder

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP dependencies (no dev)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Install Node dependencies and build frontend assets
COPY package.json package-lock.json ./
RUN npm ci

# Copy the rest of the application
COPY . .

# Finalise composer autoloader and run post-install scripts
RUN composer dump-autoload --optimize && \
    composer run-script post-autoload-dump || true

# Build frontend assets
RUN chmod -R 775 bootstrap/cache storage && \
    cp .env.example .env && \
    php artisan key:generate && \
    npm run build && \
    rm -rf node_modules .env

# =============================================================================
# Production stage: copy built app into runtime image
# =============================================================================
FROM runtime AS production

# Copy built application from builder stage
COPY --from=builder /app /app

# Set correct permissions for Laravel writable directories
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/public /data && \
    chmod -R 775 /app/storage /app/bootstrap/cache

# Copy FrankenPHP/Caddy and supervisord config
COPY docker/frankenphp/Caddyfile.prod /etc/frankenphp/Caddyfile
COPY docker/supervisor/supervisord.prod.conf /etc/supervisor/conf.d/supervisord.conf

# Copy prod entrypoint
COPY docker/frankenphp/entrypoint.prod.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Copy OPcache configuration
COPY docker/php/php.ini docker/php/opcache.ini /usr/local/etc/php/conf.d/

# Copy cronjob files
COPY docker/cron/prod /etc/cron.d/laravel
RUN chmod 0644 /etc/cron.d/laravel

EXPOSE 80 443

ENTRYPOINT ["/entrypoint.sh"]
