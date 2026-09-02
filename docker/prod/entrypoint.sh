#!/bin/sh
set -e

# Ensure storage and bootstrap directories exist
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage symlink
php /var/www/html/artisan storage:link --force 2>/dev/null || true

# Production optimization caching (if database/app is ready)
if [ -f /var/www/html/.env ]; then
    echo "Caching Laravel configuration and routes for production..."
    php /var/www/html/artisan config:cache 2>/dev/null || true
    php /var/www/html/artisan route:cache 2>/dev/null || true
    php /var/www/html/artisan view:cache 2>/dev/null || true
    php /var/www/html/artisan event:cache 2>/dev/null || true
fi

# Execute passed command (default: supervisord)
exec "$@"
