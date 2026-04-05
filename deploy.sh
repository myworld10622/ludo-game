#!/bin/bash
# ── RoxLudo Deploy Script ── (git pull based - only changed files)
set -e

TEMP_DIR="/tmp/ludo_deploy"
TARGET_DIR="/www/wwwroot/ludo_game"
REPO_URL="https://github.com/myworld10622/ludo-game.git"

echo "▶ Fetching latest code..."

if [ -d "$TEMP_DIR/.git" ]; then
  # Already cloned — just pull latest
  cd $TEMP_DIR
  git fetch origin main
  git reset --hard origin/main
else
  # First time — clone
  rm -rf $TEMP_DIR
  git clone --depth=1 $REPO_URL $TEMP_DIR
fi

echo "▶ Copying only changed files..."
rsync -av \
  --exclude='.env' \
  --exclude='storage/logs/' \
  --exclude='storage/framework/cache/' \
  --exclude='storage/framework/sessions/' \
  --exclude='storage/framework/views/' \
  --exclude='vendor/' \
  $TEMP_DIR/backend_laravel/ $TARGET_DIR/

echo "▶ Clearing views & cache..."
cd $TARGET_DIR
php artisan view:clear
php artisan cache:clear

echo "✅ Deploy complete!"
