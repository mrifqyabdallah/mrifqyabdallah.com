#!/bin/sh
set -e

if [ ! -d "vendor" ]; then
    echo "Installing PHP dependencies..."
    composer install
fi

if [ ! -d "node_modules" ]; then
    echo "Installing node dependencies..."
    npm install
fi

# Start Vite dev server in the background
npm run dev &

# Start FrankenPHP in the foreground
exec frankenphp run --config /etc/frankenphp/Caddyfile
