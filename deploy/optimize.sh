#!/usr/bin/env bash
# PayEase production deployment/optimization script.
# Run this after pulling new code and installing dependencies.

set -e

APP_DIR="/var/www/payease/current"

cd "$APP_DIR" || exit 1

echo "==> Optimizing application caches..."
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Creating storage link..."
php artisan storage:link

echo "==> Restarting Horizon..."
php artisan horizon:terminate

echo "==> Deployment optimization complete."
