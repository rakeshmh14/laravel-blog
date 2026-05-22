#!/bin/sh
set -e

cd /var/www/html

# Create .env from example when missing
if [ ! -f .env ]; then
    if [ -f .env.docker.example ]; then
        cp .env.docker.example .env
        echo "Created .env from .env.docker.example"
    else
        cp .env.example .env
        echo "Created .env from .env.example"
    fi
fi

# Install PHP dependencies when vendor is missing (e.g. first bind-mount run)
if [ ! -f vendor/autoload.php ]; then
    echo "Installing Composer dependencies..."
    /usr/local/bin/composer install --no-interaction --prefer-dist
fi

# Application key
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force --no-interaction
fi

php artisan config:clear --no-interaction 2>/dev/null || true

# Wait for MySQL
echo "Waiting for MySQL..."
attempt=0
max_attempts=30
until php -r "
    \$host = getenv('DB_HOST') ?: 'mysql';
    \$port = getenv('DB_PORT') ?: '3306';
    \$db   = getenv('DB_DATABASE') ?: 'blog';
    \$user = getenv('DB_USERNAME') ?: 'laravel';
    \$pass = getenv('DB_PASSWORD') ?: 'secret';
    try {
        new PDO(\"mysql:host=\$host;port=\$port;dbname=\$db\", \$user, \$pass);
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge "$max_attempts" ]; then
        echo "MySQL did not become ready in time."
        exit 1
    fi
    sleep 2
done
echo "MySQL is ready."

php artisan migrate --force --no-interaction
php artisan storage:link --force --no-interaction 2>/dev/null || true

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

exec "$@"
