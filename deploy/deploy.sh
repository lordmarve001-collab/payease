#!/usr/bin/env bash
# Zero-downtime deployment script for PayEase.
#
# Assumes the server layout:
#   /var/www/payease/
#     current/   -> symlink to active release
#     releases/  -> timestamped release directories
#     shared/    -> persistent files (.env, storage/logs, storage/framework/cache, etc.)
#
# Usage:
#   deploy/deploy.sh [environment]
#
# Example:
#   deploy/deploy.sh production

set -euo pipefail

ENVIRONMENT="${1:-production}"
APP_NAME="payease"
DEPLOY_USER="www-data"
APP_DIR="/var/www/${APP_NAME}"
RELEASES_DIR="${APP_DIR}/releases"
SHARED_DIR="${APP_DIR}/shared"
CURRENT_LINK="${APP_DIR}/current"
KEEP_RELEASES=5

TIMESTAMP=$(date +%Y%m%d%H%M%S)
RELEASE_DIR="${RELEASES_DIR}/${TIMESTAMP}"

echo "==> Starting ${ENVIRONMENT} deploy: ${TIMESTAMP}"

# Ensure directories exist
mkdir -p "${RELEASES_DIR}"
mkdir -p "${SHARED_DIR}/storage/logs"
mkdir -p "${SHARED_DIR}/storage/framework/cache"
mkdir -p "${SHARED_DIR}/storage/framework/sessions"
mkdir -p "${SHARED_DIR}/storage/framework/views"
mkdir -p "${SHARED_DIR}/storage/app/public"

# Clone / copy code into release directory
echo "==> Creating release directory ${RELEASE_DIR}..."
if [ -d ".git" ]; then
    git clone --depth 1 --branch "${ENVIRONMENT}" "$(git remote get-url origin)" "${RELEASE_DIR}" 2>/dev/null || \
    git clone --depth 1 "$(git remote get-url origin)" "${RELEASE_DIR}"
else
    rsync -av --exclude='.git' --exclude='node_modules' --exclude='vendor' \
        "$(pwd)/" "${RELEASE_DIR}/"
fi

cd "${RELEASE_DIR}"

# Link shared files
echo "==> Linking shared files..."
ln -sf "${SHARED_DIR}/.env" .env
rm -rf storage
ln -sf "${SHARED_DIR}/storage" storage

# Install dependencies
echo "==> Installing PHP dependencies..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

echo "==> Installing Node dependencies and building assets..."
if [ -f "package.json" ]; then
    npm ci
    npm run build
fi

# Run optimization and migrations
echo "==> Optimizing application..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Running migrations..."
php artisan migrate --force

# Create storage link
echo "==> Creating storage link..."
php artisan storage:link

# Set permissions
echo "==> Setting permissions..."
chown -R "${DEPLOY_USER}:" "${RELEASE_DIR}"
chmod -R 755 "${RELEASE_DIR}"
chmod -R 775 "${SHARED_DIR}/storage"

# Atomically switch symlink
echo "==> Activating release ${TIMESTAMP}..."
ln -sfn "${RELEASE_DIR}" "${CURRENT_LINK}"

# Restart services
echo "==> Restarting services..."
php artisan horizon:terminate || true
sudo supervisorctl restart "${APP_NAME}-horizon:*" || true
sudo supervisorctl restart "${APP_NAME}-scheduler" || true

# Cleanup old releases
echo "==> Cleaning up old releases..."
cd "${RELEASES_DIR}"
ls -1t | tail -n +$((KEEP_RELEASES + 1)) | xargs -r rm -rf

echo "==> Deploy ${TIMESTAMP} complete."
echo "    Current release: ${RELEASE_DIR}"
echo "    To rollback: ln -sfn <previous_release> ${CURRENT_LINK} && supervisorctl restart ${APP_NAME}-horizon:*"
