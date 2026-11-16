# Image de déploiement SnapDeploy — frontend (Blade + Livewire).
# Build context : ce dossier (frontend). Les assets Vite sont compilés
# dans une étape Node puis copiés dans l'image PHP finale.

FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund || npm install --no-audit --no-fund
COPY . .
RUN npm run build

FROM php:8.3-cli-alpine
RUN apk add --no-cache \
        postgresql-dev libzip-dev oniguruma-dev curl-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" \
        pdo pdo_sqlite pdo_pgsql bcmath zip curl opcache \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p storage/framework/sessions storage/framework/views \
        storage/framework/cache storage/logs bootstrap/cache database \
    && touch database/database.sqlite \
    && chown -R www-data:www-data /var/www/html

USER www-data
EXPOSE 8000

CMD ["sh", "-c", "php artisan package:discover && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
