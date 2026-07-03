#!/bin/sh
# First-run bootstrap so a single `docker compose up -d` fully prepares the app:
# creates .env, installs dependencies, generates the key and runs migrations,
# then hands off to the FrankenPHP server. All steps are idempotent.
set -e

cd /app

echo "[entrypoint] preparing application..."

# 1. Environment file
if [ ! -f .env ]; then
    echo "[entrypoint] .env not found -> copying from .env.example"
    cp .env.example .env
fi

# 2. PHP dependencies (bind-mounted volume: install only when missing)
if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] vendor/ missing -> composer install"
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# 3. Application key
if ! grep -q '^APP_KEY=base64:' .env; then
    echo "[entrypoint] generating APP_KEY"
    php artisan key:generate --force
fi

# 4. Database migrations (postgres is already healthy via compose depends_on).
#    Guarded by a Postgres advisory lock (app:migrate) so multiple app replicas
#    starting together don't race. Set APP_MIGRATE=false to run migrations as a
#    separate, controlled deploy step instead.
if [ "${APP_MIGRATE:-true}" = "true" ]; then
    echo "[entrypoint] running migrations"
    php artisan app:migrate
else
    echo "[entrypoint] APP_MIGRATE=false -> skipping migrations"
fi

# 5. Optional demo data: set APP_SEED=true in the environment to enable
if [ "${APP_SEED:-false}" = "true" ]; then
    echo "[entrypoint] seeding demo data"
    php artisan db:seed --force
fi

echo "[entrypoint] starting FrankenPHP"
exec frankenphp run --config /etc/caddy/Caddyfile
