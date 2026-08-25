FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring xml

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Set dummy env vars for build-time to prevent package:discover failures
# when broadcasting config is loaded without real credentials
ENV BROADCAST_DRIVER=null \
    REVERB_APP_KEY=dummy \
    REVERB_APP_SECRET=dummy \
    REVERB_APP_ID=dummy \
    REVERB_HOST=127.0.0.1 \
    REVERB_PORT=8080 \
    REVERB_SCHEME=http \
    PUSHER_APP_KEY=dummy \
    PUSHER_APP_SECRET=dummy \
    PUSHER_APP_ID=dummy \
    PUSHER_APP_CLUSTER=dummy \
    ABLY_KEY=dummy

# Aumentamos el timeout y agregamos --prefer-dist
ENV COMPOSER_PROCESS_TIMEOUT=600
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

RUN npm install && npm run build
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache

EXPOSE 8000

CMD php artisan migrate --force && php artisan db:seed --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}