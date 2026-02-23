#!/bin/sh
set -e

if [ ! -d "vendor" ]; then
    echo "Installing PHP dependencies..."
    composer install
fi

if [ -f ".env" ] && [ -z "$(grep '^APP_KEY=.\+' .env)" ]; then
    echo "Generating app key..."
    php artisan key:generate
fi

if [ ! -d "node_modules" ]; then
    echo "Installing node dependencies..."
    npm install
fi

# Start Vite dev server in the background
npm run dev &

# Start FrankenPHP in the foreground
exec frankenphp run --config /etc/frankenphp/Caddyfile
