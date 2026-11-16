# Déploiement sur Render

Guide pour déployer la pile complète sur [Render](https://render.com) :
7 web services Docker + 1 PostgreSQL managé, orchestrés par le blueprint
`render.yaml` à la racine du dépôt.

## Pourquoi Render plutôt qu'un PaaS à conteneurs unitaires

Render fournit un **vrai environnement par service** : URL publique stable
`https://<nom>.onrender.com`, health checks, variables d'environnement,
PostgreSQL managé et blueprint YAML. Chaque service Docker écoute sur le
`PORT` injecté par Render.

## Étape 1 — Blueprint (recommandé)

1. Push le dépôt sur GitHub (déjà fait : `main`).
2. Render Dashboard → **New → Blueprint** → connecter le repo
   `Tagavalentin/Patrimoine` → Render lit `render.yaml` et propose de
   créer les 8 ressources (7 web services + 1 base).
3. Renseigner les variables `sync: false` demandées puis **Apply**.

Alternative manuelle : créez chaque web service un par un avec les mêmes
paramètres (`runtime: docker`, `dockerfilePath`/`dockerContext` du
blueprint, `healthCheckPath`).

## Étape 2 — Créer les 5 bases logiques

Le Postgres Render fournit **une** base (`patrimoine`). L'application en
attend cinq. Une fois la base provisionnée, connectez-vous avec l'URL
externe (dashboard → base → *Connect*) et exécutez :

```sql
CREATE DATABASE auth_db;
CREATE DATABASE asset_db;
CREATE DATABASE maintenance_db;
CREATE DATABASE inventory_db;
CREATE DATABASE reporting_db;
```

Les services se connectent tous avec le **même user/mot de passe** ; seul
`DB_DATABASE` change (déjà configuré dans le blueprint).

## Étape 3 — Variables à renseigner (`sync: false`)

### Secrets (identiques sur les 5 services)

| Variable | Valeur |
|---|---|
| `JWT_SECRET` | une longue chaîne aléatoire, **identique partout** |
| `APP_KEY` | une par service : `php artisan key:generate --show` |
| `SEED_USER_PASSWORD` (auth) | `password` pour la démo |

### URLs croisées (après le 1er déploiement des services)

Render donne à chaque service l'URL `https://<nom>.onrender.com` :

| Variable | Valeur |
|---|---|
| `ASSET_SERVICE_URL` (maintenance, inventory, reporting) | `https://asset-service.onrender.com` |
| `MAINTENANCE_SERVICE_URL` (reporting) | `https://maintenance-service.onrender.com` |
| `INVENTORY_SERVICE_URL` (reporting) | `https://inventory-service.onrender.com` |
| `AUTH_SERVICE_URL` … `REPORTING_SERVICE_URL` (api-gateway) | les 5 URLs `https://*.onrender.com` |
| `GATEWAY_URL` (frontend) | `https://api-gateway.onrender.com` |

Astuce : Render permet de référencer un autre service dans une variable
d'environnement (*Add from another service → host*), ce qui donne le nom
de service — préfixez mentalement `https://` et `.onrender.com` si besoin.

## Étape 4 — Ordre de déploiement

1. **Base** `patrimoine-db` (auto via blueprint) + créer les 5 bases (étape 2)
2. **Les 5 services** : auth → asset → maintenance → inventory → reporting
   - au boot : `migrate --force` puis `db:seed` (`SEED_ON_BOOT=1`)
   - les seeders sont idempotents : les relancer ne casse rien
3. **api-gateway** : renseigner les 5 `*_SERVICE_URL`
4. **frontend** : renseigner `GATEWAY_URL`

## Vérification

- `https://api-gateway.onrender.com/` → JSON de santé de la gateway
- `https://frontend.onrender.com/login` → page de connexion
- Login : `admin@patrimoine.local` / `password` (ou `SEED_USER_PASSWORD`)

## Limites du plan gratuit Render

- Les web services **se mettent en veille** après ~15 min d'inactivité :
  le premier appel réveille l'app (~1 min de cold start).
- Le Postgres gratuit a une **durée de vie limitée** (voir la doc Render
  du moment) — exportez les données si vous gardez la démo longtemps.
- 7 web services × toujours actifs = coût non négligeable ; le plan
  starter reste raisonnable pour une démo ponctuelle.

## Dépannage

| Symptôme | Cause probable |
|---|---|
| Build KO « port not detected » | le service n'écoute pas sur `$PORT` — nos CMD utilisent `${PORT:-8000}`, OK |
| 502 sur un service | variables `sync:false` non renseignées ou DB inaccessible |
| Login OK mais pages vides | `GATEWAY_URL`/`*_SERVICE_URL` incorrectes ou services endormis |
| Erreur Redis | ajouter `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync` (déjà dans le blueprint) |
