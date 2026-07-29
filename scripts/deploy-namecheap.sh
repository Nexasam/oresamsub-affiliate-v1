#!/usr/bin/env bash

set -Eeuo pipefail

APP_DIR="/home/emiprbyj/affiliate.emiplug.com"
BRANCH="main"
PHP_BIN="${PHP_BIN:-php}"
LOCK_FILE="$APP_DIR/storage/framework/namecheap-deploy.lock"

cd "$APP_DIR"
mkdir -p "$(dirname "$LOCK_FILE")" storage/logs bootstrap/cache
exec 9>"$LOCK_FILE"

if ! flock -n 9; then
    echo "Another deployment is already running."
    exit 0
fi

git fetch --quiet origin "$BRANCH"

CURRENT_COMMIT="$(git rev-parse HEAD)"
TARGET_COMMIT="$(git rev-parse "origin/$BRANCH")"

if [[ "$CURRENT_COMMIT" == "$TARGET_COMMIT" ]]; then
    echo "Already deployed: $CURRENT_COMMIT"
    exit 0
fi

if ! git merge-base --is-ancestor "$CURRENT_COMMIT" "$TARGET_COMMIT"; then
    echo "Refusing a non-fast-forward deployment."
    exit 1
fi

COMPOSER_CHANGED=0
if ! git diff --quiet "$CURRENT_COMMIT" "$TARGET_COMMIT" -- composer.json composer.lock; then
    COMPOSER_CHANGED=1
fi

COMPOSER_COMMAND=()
if command -v composer >/dev/null 2>&1; then
    COMPOSER_COMMAND=(composer)
elif [[ -x "$HOME/bin/composer" ]]; then
    COMPOSER_COMMAND=("$HOME/bin/composer")
elif [[ -f "$HOME/composer.phar" ]]; then
    COMPOSER_COMMAND=("$PHP_BIN" "$HOME/composer.phar")
fi

if [[ "$COMPOSER_CHANGED" -eq 1 && "${#COMPOSER_COMMAND[@]}" -eq 0 ]]; then
    echo "Composer files changed, but Composer is unavailable."
    echo "Deployment stopped before changing the live application."
    exit 1
fi

APP_IS_DOWN=0
bring_application_up() {
    if [[ "$APP_IS_DOWN" -eq 1 ]]; then
        "$PHP_BIN" artisan up || true
    fi
}
trap bring_application_up EXIT

"$PHP_BIN" artisan down --retry=60 || true
APP_IS_DOWN=1

git merge --ff-only "origin/$BRANCH"

if [[ "$COMPOSER_CHANGED" -eq 1 ]]; then
    "${COMPOSER_COMMAND[@]}" install \
        --no-dev \
        --prefer-dist \
        --no-interaction \
        --no-progress \
        --optimize-autoloader
fi

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chmod -R ug+rw storage bootstrap/cache

"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan storage:link || true
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

"$PHP_BIN" artisan up
APP_IS_DOWN=0

echo "Successfully deployed $TARGET_COMMIT"
