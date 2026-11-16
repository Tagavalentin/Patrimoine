# Image de déploiement SnapDeploy — auth-service (php artisan serve).
# Build context : ce dossier (services/auth-service).
FROM php:8.3-cli-alpine

RUN set -e; \
    for i in 1 2 3 4 5; do \
        apk add --no-cache \
            postgresql-dev icu-dev libzip-dev oniguruma-dev \
            $PHPIZE_DEPS && break || sleep 15; \
    done; \
    docker-php-ext-install -j"$(nproc)" \
        pdo pdo_pgsql intl bcmath zip opcache; \
    for i in 1 2 3 4 5; do \
        pecl install redis && break || sleep 15; \
    done; \
    docker-php-ext-enable redis; \
    apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction --prefer-dist

COPY . .

RUN mkdir -p storage/framework/sessions storage/framework/views \
        storage/framework/cache storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www/html

USER www-data
EXPOSE 8000

# Au démarrage : découverte des packages, migrations, seed optionnel
# (SEED_ON_BOOT=1), puis serveur HTTP intégré Laravel.
CMD ["sh", "-c", "php artisan package:discover && php artisan migrate --force && if [ \"$SEED_ON_BOOT\" = \"1\" ]; then php artisan db:seed --force; fi && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
