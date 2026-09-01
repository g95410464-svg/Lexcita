FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring xml pcntl

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Tiempo extra para composer y --prefer-dist
ENV COMPOSER_PROCESS_TIMEOUT=600

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# ── VITE (públicas, build-time) ────────────────────────────────
# Vite inyecta import.meta.env.VITE_* durante `npm run build`.
# Railway debe suministrarlas como Build Variables del servicio.
# SOLO variables públicas; jamás REVERB_APP_SECRET/APP_KEY/paypal/db aquí.
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT
ARG VITE_REVERB_SCHEME

RUN npm install && npm run build

# ── Caches ─────────────────────────────────────────────────────
# NO cachear config en build: config/broadcasting.php y config/reverb.php
# dependen de variables que Railway inyecta en RUNTIME y que difieren por
# servicio (web / reverb / queue). Cachearlas con valores dummy las hornearía
# y rompería la señalización en producción.
# route:cache y view:cache son seguros: no dependen de esas variables.
RUN php artisan route:cache && php artisan view:cache

EXPOSE 8000

# Web: migra (política actual), limpia una posible config cache vieja, y sirve.
# Sin db:seed automático (evita reseed en cada restart).
CMD /bin/sh -c "php artisan migrate --force && php artisan config:clear && exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"
