#!/usr/bin/env bash
set -euo pipefail

# Runs on each production container start (deploy). Requires DB reachable (e.g. depends_on pgsql healthy).
# Set RESET_DATABASE_ON_DEPLOY=false to skip (e.g. external DB you must not wipe).

if [ "${RESET_DATABASE_ON_DEPLOY:-true}" != "true" ]; then
    echo "prod-database-init: RESET_DATABASE_ON_DEPLOY is not true, skipping migrate:fresh + LeagueSeeder."
    exit 0
fi

if [ "${APP_ENV:-production}" != "production" ]; then
    echo "prod-database-init: APP_ENV is not production, skipping."
    exit 0
fi

echo "prod-database-init: migrate:fresh + Database\\Seeders\\LeagueSeeder"

INSIDER_BOOT_DB_RESET=1 /usr/bin/php /var/www/html/artisan migrate:fresh \
    --force \
    --seeder="Database\\Seeders\\LeagueSeeder"
