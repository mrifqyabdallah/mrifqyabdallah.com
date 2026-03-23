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

echo "* * * * * www-data cd /app && php artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/laravel
chmod 644 /etc/cron.d/laravel
cron

# Start supervisord (manages FrankenPHP + queue worker)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf