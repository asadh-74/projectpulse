#!/usr/bin/env bash
# Scaffolds a fresh Laravel app and layers the ProjectPulse code on top.
# Requires: PHP >= 8.2, Composer, (SQLite or MySQL).
set -euo pipefail

APP_DIR="${1:-projectpulse}"
HERE="$(cd "$(dirname "$0")" && pwd)"

composer create-project laravel/laravel "$APP_DIR" --no-interaction
cd "$APP_DIR"

# Sanctum + routes/api.php + bootstrap wiring
php artisan install:api --no-interaction

# Overlay the application code (routes, models, controllers, views, tests ...)
cp -R "$HERE/overlay/." .
rm -f tests/Feature/ExampleTest.php tests/Unit/ExampleTest.php   # stock tests assume "/" returns 200
mkdir -p .github/workflows && cp "$HERE/.github/workflows/ci.yml" .github/workflows/
cp "$HERE/docker-compose.yml" . && cp "$HERE/README.md" ./README.md

# Pakistan time zone
sed -i 's/^APP_TIMEZONE=.*/APP_TIMEZONE=Asia\/Karachi/' .env 2>/dev/null || true
grep -q '^APP_TIMEZONE=' .env || echo 'APP_TIMEZONE=Asia/Karachi' >> .env

php artisan migrate:fresh --seed --no-interaction
php artisan test

echo
echo "Done. Start the app with:  cd $APP_DIR && php artisan serve"
echo "Login: demo@projectpulse.test / password"
