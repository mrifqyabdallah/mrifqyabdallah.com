#!/bin/sh
set -e

# Run Laravel optimization on every startup
php artisan optimize

# Start FrankenPHP in foreground
exec frankenphp run --config /etc/frankenphp/Caddyfile
