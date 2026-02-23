#!/bin/sh
set -e

# Install node dependencies if node_modules is missing
if [ ! -d "node_modules" ]; then
    echo "Installing node dependencies..."
    npm install
fi

# Start Vite dev server in the background
npm run dev &

# Start FrankenPHP in the foreground
exec frankenphp run --config /etc/frankenphp/Caddyfile
