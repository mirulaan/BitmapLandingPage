#!/bin/sh
set -e

# Ensure storage and cache directories exist and have proper permissions
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create public storage symlink if not already linked
if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link --no-interaction || true
fi

# SQLite support: ensure database file and directory permissions exist
if [ "${DB_CONNECTION:-pgsql}" = "sqlite" ]; then
    mkdir -p /var/www/html/database
    if [ ! -f /var/www/html/database/database.sqlite ]; then
        touch /var/www/html/database/database.sqlite
    fi
    chown -R www-data:www-data /var/www/html/database
    chmod 775 /var/www/html/database
    chmod 664 /var/www/html/database/database.sqlite
fi

# Support dynamic port binding (e.g., Cloud Run, Heroku, Railway)
if [ -n "$PORT" ] && [ "$PORT" != "80" ]; then
    sed -i "s/listen 80 default_server;/listen ${PORT} default_server;/g" /etc/nginx/http.d/default.conf
    sed -i "s/listen \[::\]:80 default_server;/listen [::]:${PORT} default_server;/g" /etc/nginx/http.d/default.conf
fi

# Auto-generate and persist APP_KEY in shared storage volume if missing
if [ -z "$APP_KEY" ]; then
    KEY_FILE="/var/www/html/storage/app/.app_key"
    if [ -f "$KEY_FILE" ]; then
        APP_KEY="$(cat "$KEY_FILE")"
    else
        echo "Notice: APP_KEY not set. Auto-generating persistent application key..."
        APP_KEY="$(php artisan key:generate --show --no-interaction)"
        mkdir -p /var/www/html/storage/app
        echo "$APP_KEY" > "$KEY_FILE"
        chown www-data:www-data "$KEY_FILE"
    fi
    export APP_KEY
fi

# Run database migrations if enabled
if [ "${RUN_MIGRATIONS:-false}" = "true" ] || [ "${AUTO_MIGRATE:-false}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force --no-interaction
fi

# Seed database if requested or on first boot when users table is empty
if [ "${SEED_DATABASE:-false}" = "true" ]; then
    echo "Seeding database..."
    php artisan db:seed --force --no-interaction
elif [ "${SEED_IF_EMPTY:-false}" = "true" ]; then
    USER_COUNT=$(php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo App\Models\User::count();" 2>/dev/null || echo "0")
    if [ "$USER_COUNT" = "0" ]; then
        echo "Empty database detected. Seeding initial admin user & portal data..."
        php artisan db:seed --force --no-interaction
    fi
fi

# Optimize Laravel cache in production unless explicitly skipped
if [ "${APP_ENV:-production}" = "production" ] && [ "${SKIP_OPTIMIZATION:-false}" != "true" ]; then
    echo "Optimizing configuration, routes, and views..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
    php artisan event:cache || true
fi

# Execute the given command or default to supervisord
exec "$@"
