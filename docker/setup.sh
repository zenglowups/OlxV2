#!/bin/sh
set -eu
composer install --no-interaction --prefer-dist --no-progress
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache
# Clear compiled code only; preserve Redis data, sessions and rate limits.
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
php artisan migrate --force --no-interaction
