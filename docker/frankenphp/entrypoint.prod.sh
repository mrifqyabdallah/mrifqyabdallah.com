#!/bin/sh
set -e

if [ ! -d "public/storage" ]; then
    php artisan storage:link
fi

php artisan optimize:clear
php artisan optimize

# Start FrankenPHP in foreground
exec frankenphp run --config /etc/frankenphp/Caddyfile
