#!/bin/bash
set -euo pipefail

APP_DIR="/var/www/payease"
RELEASE_DIR="${APP_DIR}/releases/$(date +%Y%m%d%H%M%S)"
SHARED_DIR="${APP_DIR}/shared"
CURRENT_DIR="${APP_DIR}/current"
KEEP_RELEASES=5

echo "=== PayEase Deployment: $(date) ==="

# 1. Prepare release directory
mkdir -p "${RELEASE_DIR}"

# 2. Clone / checkout code
git clone --depth=1 git@github.com:your-org/payease.git "${RELEASE_DIR}"

# 3. Symlink shared assets
ln -sf "${SHARED_DIR}/.env" "${RELEASE_DIR}/.env"
ln -sf "${SHARED_DIR}/storage" "${RELEASE_DIR}/storage"
ln -sf "${SHARED_DIR}/public/uploads" "${RELEASE_DIR}/public/uploads"

# 4. Install dependencies
cd "${RELEASE_DIR}"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci --omit=dev
npm run build

# 5. Environment & config
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Run migrations
php artisan migrate --force

# 7. Symlink current release
ln -sfn "${RELEASE_DIR}" "${CURRENT_DIR}"

# 8. Restart Horizon
php "${CURRENT_DIR}/artisan" horizon:terminate

# 9. Restart PHP-FPM
sudo systemctl reload php8.3-fpm || true

# 10. Cleanup old releases
cd "${APP_DIR}/releases"
ls -1t | tail -n +$((KEEP_RELEASES + 1)) | xargs -I {} rm -rf {} 2>/dev/null || true

echo "=== Deployment complete: ${RELEASE_DIR} ==="
