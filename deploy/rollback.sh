#!/usr/bin/env bash
# Rollback to the previous PayEase release.
#
# Usage:
#   deploy/rollback.sh

set -euo pipefail

APP_NAME="payease"
APP_DIR="/var/www/${APP_NAME}"
RELEASES_DIR="${APP_DIR}/releases"
CURRENT_LINK="${APP_DIR}/current"

CURRENT_RELEASE=$(readlink -f "${CURRENT_LINK}" || true)
if [ -z "${CURRENT_RELEASE}" ]; then
    echo "Error: no current release found."
    exit 1
fi

PREVIOUS_RELEASE=$(ls -1t "${RELEASES_DIR}" | grep -v "$(basename "${CURRENT_RELEASE}")" | head -n 1)
if [ -z "${PREVIOUS_RELEASE}" ]; then
    echo "Error: no previous release available for rollback."
    exit 1
fi

echo "==> Rolling back from $(basename "${CURRENT_RELEASE}") to ${PREVIOUS_RELEASE}..."
ln -sfn "${RELEASES_DIR}/${PREVIOUS_RELEASE}" "${CURRENT_LINK}"

cd "${CURRENT_LINK}"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan horizon:terminate || true
sudo supervisorctl restart "${APP_NAME}-horizon:*" || true
sudo supervisorctl restart "${APP_NAME}-scheduler" || true

echo "==> Rollback complete. Active release: ${PREVIOUS_RELEASE}"
