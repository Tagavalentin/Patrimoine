# Déploiement sur SnapDeploy

Ce document décrit comment déployer la stack réelle sur https://snapdeploy.dev/.

## Architecture déployée (7 conteneurs + 1 base managée)

SnapDeploy déploie **un conteneur par repo + branche + dossier racine** (monorepo).
Nous utilisons `php artisan serve` à la place de php-fpm + nginx interne, ce qui
ramène la stack de 15 à 7 conteneurs :

| # | Conteneur        | Root directory (repo)   | Rôle                          |
|---|------------------|-------------------------|-------------------------------|
| 1 | auth-service     | `services/auth-service` | API auth + JWT + RBAC         |
| 2 | asset-service    | `services/asset-service`| API biens/référentiels        |
| 3 | maintenance-service | `services/maintenance-service` | API maintenance     |
| 4 | inventory-service| `services/inventory-service`   | API inventaire          |
| 5 | reporting-service| `services/reporting-service`   | API rapports/audit      |
| 6 | gateway          | `gateway`               | Nginx : point d'entrée `/api/*` |
| 7 | frontend         | `frontend`              | Interface Blade + Livewire    |

Base de données : **add-on PostgreSQL managé** SnapDeploy (un serveur, 5 bases :
`auth_db`, `asset_db`, `maintenance_db`, `inventory_db`, `reporting_db`).
Redis n'est pas requis : `CACHE_STORE=file`, `SESSION_DRIVER=file`,
`QUEUE_CONNECTION=sync`.

## Étape 0 — Prérequis et coût

- Compte SnapDeploy (https://snapdeploy.dev/register — « Sign up with GitHub »
  recommandé pour connecter le dépôt `Tagavalentin/Patrimoine`).
- Le plan gratuit limite à **4 conteneurs** et fait dormir les apps après
  15 min d'inactivité. Il faut **7 conteneurs** → prévoir le plan
  Always-On Small (~$12/mois par conteneur) ou accepter le sommeil en démo.
- Chaque conteneur fait `php artisan migrate --force` au démarrage.
  `SEED_ON_BOOT=1` lance aussi les seeders de démo (idempotents).

## Étape 1 — Base PostgreSQL managée

1. Dashboard → **Add-ons** → ajouter **PostgreSQL**.
2. Noter host, port, user, password.
3. Créer les 5 bases (console SQL de l'add-on ou `psql`) :

```sql
CREATE DATABASE auth_db;
CREATE DATABASE asset_db;
CREATE DATABASE maintenance_db;
CREATE DATABASE inventory_db;
CREATE DATABASE reporting_db;
```

## Étape 2 — Générer les secrets

```bash
# Clé JWT commune aux 5 services (64 hex)
openssl rand -hex 32

# APP_KEY par service Laravel (autant de fois que nécessaire)
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

## Étape 3 — Déployer les 5 services (dans l'ordre)

Pour chaque service : Dashboard → **Deploy** → connecter GitHub → choisir le
dépôt, branche `main`, **root directory** = dossier du service, puis définir
les variables d'environnement ci-dessous et déployer.

### Variables communes aux 5 services

| Variable          | Valeur                                            |
|-------------------|---------------------------------------------------|
| `APP_ENV`         | `production`                                      |
| `APP_DEBUG`       | `false`                                           |
| `APP_KEY`         | `base64:...` (générée, une par service de préf.)  |
| `DB_CONNECTION`   | `pgsql`                                           |
| `DB_HOST`         | host de l'add-on Postgres                         |
| `DB_PORT`         | `5432`                                            |
| `DB_DATABASE`     | `auth_db` / `asset_db` / `maintenance_db` / `inventory_db` / `reporting_db` |
| `DB_USERNAME`     | user de l'add-on                                  |
| `DB_PASSWORD`     | mot de passe de l'add-on                          |
| `JWT_SECRET`      | **identique** sur les 5 services                  |
| `JWT_TTL`         | `60`                                              |
| `SEED_ON_BOOT`    | `1`                                               |
| `PORT`            | `8000` (optionnel, défaut)                        |

`JWT_ISSUER` : `patrimoine-auth-service`, `patrimoine-asset-service`,
`patrimoine-maintenance-service`, `patrimoine-inventory-service`,
`patrimoine-reporting-service` respectivement.

`APP_NAME` : nom lisible du service. `APP_URL` : l'URL publique du conteneur
(https://....containers.snapdeploy.app) une fois connu — peut être renseignée
après le premier déploiement puis redéployé.

### Variables supplémentaires

- **auth-service** : `SEED_USER_PASSWORD=password` (sinon refusé en production).
- **maintenance-service** : `ASSET_SERVICE_URL=https://<url-du-conteneur-asset>`
- **inventory-service** : `ASSET_SERVICE_URL=https://<url-du-conteneur-asset>`
- **reporting-service** : `ASSET_SERVICE_URL`, `MAINTENANCE_SERVICE_URL`,
  `INVENTORY_SERVICE_URL` (URLs publiques https des conteneurs correspondants)

> Les services s'appellent entre eux via leurs **URL publiques** avec le JWT
> de l'utilisateur (pas de réseau privé requis).

## Étape 4 — Gateway

Root directory : `gateway`. Variables :

| Variable                    | Valeur                                        |
|-----------------------------|-----------------------------------------------|
| `AUTH_SERVICE_URL`          | `https://<url-auth>`                          |
| `ASSET_SERVICE_URL`         | `https://<url-asset>`                         |
| `MAINTENANCE_SERVICE_URL`   | `https://<url-maintenance>`                   |
| `INVENTORY_SERVICE_URL`     | `https://<url-inventory>`                     |
| `REPORTING_SERVICE_URL`     | `https://<url-reporting>`                     |

Le Dockerfile (`gateway/Dockerfile`) + `gateway.conf.template` montent un nginx
qui réécrit `/api/<service>/...` vers `/api/...` du service cible (comme la
gateway locale).

## Étape 5 — Frontend

Root directory : `frontend`. Variables :

| Variable             | Valeur                                  |
|----------------------|-----------------------------------------|
| `APP_ENV`            | `production`                            |
| `APP_DEBUG`          | `false`                                 |
| `APP_KEY`            | `base64:...`                            |
| `APP_URL`            | `https://<url-frontend>`                |
| `GATEWAY_URL`        | `https://<url-gateway>`                 |
| `SESSION_DRIVER`     | `file`                                  |
| `SESSION_SECURE_COOKIE` | `true`                              |
| `CACHE_STORE`        | `file`                                  |
| `QUEUE_CONNECTION`   | `sync`                                  |

Le Dockerfile compile les assets Vite (`npm run build`) dans une étape Node.

## Étape 6 — Vérification

```bash
# Login
curl -X POST https://<gateway>/api/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@patrimoine.local","password":"password"}'

# Biens (avec le token retourné)
curl https://<gateway>/api/assets/assets \
  -H 'Authorization: Bearer <token>'
```

Comptes de démo : voir README (5 comptes, mot de passe `password`).

## Limites connues

- **Cold start** : les conteneurs gratuits dorment après 15 min — le premier
  appel peut prendre ~1 min. En Always-On, `php artisan serve` est mono-processus
  : suffisant pour une démo, pas pour la production.
- **Base de données** : si l'add-on Postgres n'autorise qu'une seule base,
  il faut 5 add-ons ou demander à SnapDeploy de créer les bases.
- **Volumes** : le système de fichiers des conteneurs est éphémère — les
  uploads/storage Laravel ne persistent pas entre redéploiements.
