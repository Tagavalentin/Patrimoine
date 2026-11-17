# Image de déploiement SnapDeploy — frontend (Blade + Livewire).
# Build context : la RACINE du dépôt. Ce Dockerfile permet à SnapDeploy de
# déployer le frontend même sans root directory configuré : il construit
# l'application dans frontend/ et la sert depuis /var/www/html.
# Les assets Vite sont compilés dans une étape Node puis copiés dans
# l'image PHP finale.

FROM node:20-alpine AS assets
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json* ./
RUN npm ci --no-audit --no-fund || npm install --no-audit --no-fund
COPY frontend/ ./
RUN npm run build

FROM php:8.3-cli-alpine
RUN set -e; \
    for i in 1 2 3 4 5; do \
        apk add --no-cache \
            postgresql-dev libzip-dev oniguruma-dev curl-dev sqlite-dev \
            $PHPIZE_DEPS && break || sleep 15; \
    done; \
    docker-php-ext-install -j"$(nproc)" \
        pdo pdo_sqlite pdo_pgsql bcmath zip curl opcache; \
    for i in 1 2 3 4 5; do \
        pecl install redis && break || sleep 15; \
    done; \
    docker-php-ext-enable redis; \
    apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY frontend/composer.json frontend/composer.lock* ./
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction --prefer-dist

COPY frontend/ ./
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p storage/framework/sessions storage/framework/views \
        storage/framework/cache storage/logs bootstrap/cache database \
    && touch database/database.sqlite \
    && chown -R www-data:www-data /var/www/html

USER www-data
EXPOSE 8000

CMD ["sh", "-c", "php artisan package:discover && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
