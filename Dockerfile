# =============================================================================
# Runtime stage: lean image with extensions — used as base for both dev and prod
# =============================================================================
FROM dunglas/frankenphp:1-php8.5 AS runtime

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

# =============================================================================
# Build stage: install composer & node dependencies, build frontend assets
# =============================================================================
FROM runtime AS builder

# Install system dependencies needed for Node.js and Composer
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    gnupg \
    git \
    unzip \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

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
# Production stage: copy built app into runtime image
# =============================================================================
FROM runtime AS production

# Copy built application from builder stage
COPY --from=builder /app /app

# Set correct permissions for Laravel writable directories
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache && \
    chmod -R 775 /app/storage /app/bootstrap/cache

# Copy FrankenPHP/Caddy server config
COPY docker/frankenphp/Caddyfile.prod /etc/frankenphp/Caddyfile

# Use non-root user for security
USER www-data

EXPOSE 80 443

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
