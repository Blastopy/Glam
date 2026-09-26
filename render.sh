#!/usr/bin/env bash

set -o errexit

composer install --no-dev --optimize-autoloader

php artisan config:clear

touch database/database.sqlite

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache
