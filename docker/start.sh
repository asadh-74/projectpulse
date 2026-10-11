#!/bin/bash
set -e
cd /var/www/html

[ -f .env ] || cp .env.example .env

mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
touch database/database.sqlite

php artisan package:discover --ansi
[ -n "$APP_KEY" ] || php artisan key:generate --force

# Demo database is rebuilt on every start (free hosting has no persistent disk).
php artisan migrate:fresh --seed --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache database

PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
