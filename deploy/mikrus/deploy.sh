#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/prawkobit/current}"
SITEMAP_GROUP="${SITEMAP_GROUP:-www-data}"
SEO_RELEASE="${SEO_RELEASE:-0}"
SEO_BASE_URL="${SEO_BASE_URL:-https://prawkonaraz.pl}"
REQUIRE_NEWSROOM_PUBLIC="${REQUIRE_NEWSROOM_PUBLIC:-0}"

if [[ "$SEO_RELEASE" != "0" && "$SEO_RELEASE" != "1" ]]; then
  echo "SEO_RELEASE musi miec wartosc 0 albo 1." >&2
  exit 1
fi

if [[ "$REQUIRE_NEWSROOM_PUBLIC" != "0" && "$REQUIRE_NEWSROOM_PUBLIC" != "1" ]]; then
  echo "REQUIRE_NEWSROOM_PUBLIC musi miec wartosc 0 albo 1." >&2
  exit 1
fi

cd "$APP_DIR"

if [ ! -f public/build/manifest.json ]; then
  echo "Brakuje public/build/manifest.json. Zbuduj assety przed deployem albo dostarcz gotowy build."
  exit 1
fi

if [[ "$SEO_RELEASE" == "1" ]]; then
  if ! command -v nginx >/dev/null 2>&1; then
    echo "SEO_RELEASE=1 wymaga nginx w PATH, aby zweryfikowac aktywna konfiguracje originu." >&2
    exit 1
  fi

  if [ ! -f scripts/production-seo-delivery-smoke.sh ]; then
    echo "Brakuje scripts/production-seo-delivery-smoke.sh wymaganego przez SEO_RELEASE=1." >&2
    exit 1
  fi
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

if [[ "$SEO_RELEASE" == "1" ]]; then
  echo "SEO release: refresh and audit generated sitemap artifacts."
  php artisan seo:refresh-sitemaps
  php artisan seo:audit-sitemaps
fi

php artisan up
trap - EXIT

if [[ "$SEO_RELEASE" == "1" ]]; then
  echo "SEO release: validate active Nginx syntax and public crawler delivery."
  nginx -t
  REQUIRE_NEWSROOM_FEED="$REQUIRE_NEWSROOM_PUBLIC" \
    bash scripts/production-seo-delivery-smoke.sh "$SEO_BASE_URL"
  echo "SEO_RELEASE_OK"
fi
