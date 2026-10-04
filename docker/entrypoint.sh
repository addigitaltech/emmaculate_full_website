#!/usr/bin/env sh
set -eu

PORT="${PORT:-80}"
case "$PORT" in
    ''|*[!0-9]*)
        echo "PORT must be a numeric TCP port; received: $PORT" >&2
        exit 1
        ;;
esac

printf 'Listen %s\n' "$PORT" > /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

cd /var/www/html
php artisan migrate --force

if [ ! -e public/storage ] && [ ! -L public/storage ]; then
    php artisan storage:link
fi

php artisan school:init

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
