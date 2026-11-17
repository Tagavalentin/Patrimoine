#!/bin/sh
# Démarrage monolithe SnapDeploy : génère les .env par application,
# lance les 6 serveurs Laravel (ports internes 8000-8005), puis nginx.
set -e

BASE=/var/www/app
PORT=${PORT:-8080}
PUBLIC_URL="https://patrimoine-app.containers.snapdeploy.app"

write_env() {
    # $1 = dossier app, $2 = APP_NAME, $3 = APP_KEY, $4 = base env commune
    cat > "$BASE/$1/.env" <<EOF
APP_NAME="$2"
APP_ENV=production
APP_KEY=$3
APP_DEBUG=false
APP_URL=$PUBLIC_URL
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
LOG_CHANNEL=stack
LOG_LEVEL=warning
DB_CONNECTION=sqlite
SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_STORE=file
QUEUE_CONNECTION=sync
$4
EOF
}

SERVICE_ENV='DB_HOST=127.0.0.1
DB_PORT=5432
JWT_SECRET=2a57af8710d0003e85ea7dcc73ef1bb41ef068a8f6a08134644493c51f88b0b9d6507384b0399eb6d1697d8cd5af7ddfb2192cccddbc776df9c151599517c057
JWT_ISSUER=patrimoine-auth-service
JWT_TTL=60
SEED_ON_BOOT=1'

write_env auth "Auth Service" "base64:vDBHlxBry8Q1GvZJaBXJi7bnaE0rf65mZ6oasoD+I/Y=" "DB_DATABASE=$BASE/auth/database/auth.sqlite
$SERVICE_ENV
SEED_USER_PASSWORD=password"

write_env asset "Asset Service" "base64:gxx5ygNGAIMbO/mt4V3RdLjc8+ZQSF/OpnPWODiCRfw=" "DB_DATABASE=$BASE/asset/database/asset.sqlite
$SERVICE_ENV"

write_env maintenance "Maintenance Service" "base64:Z56DcJudLrx67xiTmjy/Zsjh+2DPRFJUEafmDNrf+hw=" "DB_DATABASE=$BASE/maintenance/database/maintenance.sqlite
$SERVICE_ENV
ASSET_SERVICE_URL=http://127.0.0.1:8002"

write_env inventory "Inventory Service" "base64:Ut12VolawFJ7dl+v42um79iR/no13UhL2ITf7skg1c4=" "DB_DATABASE=$BASE/inventory/database/inventory.sqlite
$SERVICE_ENV
ASSET_SERVICE_URL=http://127.0.0.1:8002"

write_env reporting "Reporting Service" "base64:FhG8l2f9McluAh9R1ldw6wF/t58h+rLRHTQSNJKQvWc=" "DB_DATABASE=$BASE/reporting/database/reporting.sqlite
$SERVICE_ENV
ASSET_SERVICE_URL=http://127.0.0.1:8002
MAINTENANCE_SERVICE_URL=http://127.0.0.1:8003
INVENTORY_SERVICE_URL=http://127.0.0.1:8004"

cat > "$BASE/frontend/.env" <<EOF
APP_NAME="Gestion du Patrimoine"
APP_ENV=production
APP_KEY=base64:4ByED6yFD/dDlqlPsYMIVVhUdK/09Z7+0ahK4uzX8/o=
APP_DEBUG=false
APP_URL=$PUBLIC_URL
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
LOG_CHANNEL=stack
LOG_LEVEL=warning
DB_CONNECTION=sqlite
DB_DATABASE=$BASE/frontend/database/frontend.sqlite
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=sync
GATEWAY_URL=http://127.0.0.1:$PORT
EOF

# Démarrage des applications (migrations + seed optionnel + serveur Laravel)
start_app() {
    dir=$1; port=$2; seed=$3
    (
        cd "$BASE/$dir" || exit 1
        php artisan package:discover
        php artisan migrate --force
        if [ "$seed" = "1" ]; then php artisan db:seed --force; fi
        exec php artisan serve --host=127.0.0.1 --port="$port"
    ) &
}

start_app auth 8001 1
start_app asset 8002 1
start_app maintenance 8003 1
start_app inventory 8004 1
start_app reporting 8005 1
start_app frontend 8000 0

# Nginx sur le port exposé par la plateforme
sed -i "s/__PORT__/$PORT/g" /etc/nginx/nginx.conf
exec nginx -g 'daemon off;'
