#!/bin/bash
# ── RoxLudo Deploy Script ──
# Server par run karo: bash /www/wwwroot/deploy.sh

set -e

REPO_URL="https://github.com/myworld10622/skill-games.git"
TEMP_DIR="/tmp/ludo_deploy"
TARGET_DIR="/www/wwwroot/ludo_game"

echo "▶ Pulling latest code..."
rm -rf $TEMP_DIR
git clone --depth=1 $REPO_URL $TEMP_DIR

echo "▶ Syncing backend_laravel → $TARGET_DIR ..."
rsync -av --delete \
  --exclude='.env' \
  --exclude='storage/logs/' \
  --exclude='storage/framework/cache/' \
  --exclude='storage/framework/sessions/' \
  --exclude='storage/framework/views/' \
  --exclude='vendor/' \
  $TEMP_DIR/backend_laravel/ $TARGET_DIR/

echo "▶ Installing dependencies..."
cd $TARGET_DIR
composer install --no-dev --optimize-autoloader

echo "▶ Clearing & caching..."
php artisan view:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache

echo "▶ Running migrations..."
php artisan migrate --force

echo "✅ Deploy complete! roxludo.com is live."
