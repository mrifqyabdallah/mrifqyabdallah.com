# =============================================================================
# Build stage: install composer & node dependencies, build frontend assets
# =============================================================================
FROM dunglas/frankenphp:latest-php8.5 AS builder

WORKDIR /app

# Install system dependencies needed for PHP extensions and Node.js
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    gnupg \
    git \
    unzip \
    libpq-dev \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    opcache \
    intl \
    zip \
    pcntl \
    bcmath

# Copy composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

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
RUN npm run build && rm -rf node_modules

# =============================================================================
# Production stage: lean runtime image
# =============================================================================
FROM dunglas/frankenphp:latest-php8.5

WORKDIR /app

# Install runtime PHP extensions only
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
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

# Copy built application from builder stage
COPY --from=builder /app /app

# Set correct permissions for Laravel writable directories
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache && \
    chmod -R 775 /app/storage /app/bootstrap/cache

# Copy FrankenPHP/Caddy server config
COPY docker/frankenphp/Caddyfile.prod /etc/caddy/Caddyfile

# Use non-root user for security
USER www-data

EXPOSE 8080

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
