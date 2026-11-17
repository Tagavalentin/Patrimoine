# Image monolithe SnapDeploy — toute la plateforme dans UN conteneur.
# frontend (8000), auth (8001), asset (8002), maintenance (8003),
# inventory (8004), reporting (8005) + nginx sur ${PORT}.
# L'image PHP officielle fournit déjà pdo_sqlite, mbstring, curl, etc.
# Build context : racine du repo.

FROM node:20-alpine AS assets
WORKDIR /app
COPY frontend/package.json frontend/package-lock.json* ./
RUN npm ci --no-audit --no-fund || npm install --no-audit --no-fund
COPY frontend/ .
RUN npm run build

FROM php:8.3-cli-alpine
RUN apk add --no-cache nginx unzip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/app

COPY services/auth-service/composer.json services/auth-service/composer.lock ./auth/
COPY services/asset-service/composer.json services/asset-service/composer.lock ./asset/
COPY services/maintenance-service/composer.json services/maintenance-service/composer.lock ./maintenance/
COPY services/inventory-service/composer.json services/inventory-service/composer.lock ./inventory/
COPY services/reporting-service/composer.json services/reporting-service/composer.lock ./reporting/
COPY frontend/composer.json frontend/composer.lock ./frontend/
RUN set -e; \
    for app in auth asset maintenance inventory reporting frontend; do \
        (cd "$app" && composer install --no-dev --no-scripts \
            --optimize-autoloader --no-interaction --prefer-dist); \
    done

COPY services/auth-service/ ./auth/
COPY services/asset-service/ ./asset/
COPY services/maintenance-service/ ./maintenance/
COPY services/inventory-service/ ./inventory/
COPY services/reporting-service/ ./reporting/
COPY frontend/ ./frontend/
COPY --from=assets /app/public/build ./frontend/public/build

RUN set -e; \
    for app in auth asset maintenance inventory reporting frontend; do \
        mkdir -p "/var/www/app/$app/database" \
            "/var/www/app/$app/storage/framework/sessions" \
            "/var/www/app/$app/storage/framework/views" \
            "/var/www/app/$app/storage/framework/cache" \
            "/var/www/app/$app/storage/logs" \
            "/var/www/app/$app/bootstrap/cache"; \
        touch "/var/www/app/$app/database/$app.sqlite"; \
    done; \
    mkdir -p /var/log/nginx /var/lib/nginx/tmp /run/nginx; \
    chown -R www-data:www-data /var/www/app

COPY monolith/nginx.conf /etc/nginx/nginx.conf
COPY monolith/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 8080
CMD ["/usr/local/bin/start.sh"]
