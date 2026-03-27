#!/bin/sh
set -e

if [ ! -d "public/storage" ]; then
    php artisan storage:link
fi

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan optimize
php artisan queue:restart

rm -f /var/run/cron*.pid
cron

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf