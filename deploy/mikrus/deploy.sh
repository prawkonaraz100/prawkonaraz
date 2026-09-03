#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/prawkobit/current}"
SITEMAP_GROUP="${SITEMAP_GROUP:-www-data}"

cd "$APP_DIR"

if [ ! -f public/build/manifest.json ]; then
  echo "Brakuje public/build/manifest.json. Zbuduj assety przed deployem albo dostarcz gotowy build."
  exit 1
fi

php artisan down --retry=60 || true

cleanup() {
  php artisan up || true
}

trap cleanup EXIT

composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan storage:link || true

# The scheduler runs as www-data and writes sitemap files atomically by creating
# temporary files next to their targets. Keep only the two required directories
# group-writable so a root-run deploy does not break the nightly refresh.
install -d -o root -g "$SITEMAP_GROUP" -m 2775 public/sitemaps
chgrp "$SITEMAP_GROUP" public
chmod 2775 public

php artisan optimize:clear
php artisan config:cache
php artisan view:cache

php artisan up
trap - EXIT
