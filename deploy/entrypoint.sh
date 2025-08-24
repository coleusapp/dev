#!/bin/sh
set -e

php artisan migrate --force
php artisan cache:clear
php artisan config:cache
php artisan route:cache

exec php artisan octane:frankenphp
