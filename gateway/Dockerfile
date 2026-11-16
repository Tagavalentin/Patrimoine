# Image de déploiement SnapDeploy — API Gateway (nginx).
# Build context : ce dossier (gateway).
# La configuration est templatisée : les URLs des services sont injectées
# par variables d'environnement (voir gateway.conf.template).
FROM nginx:1.27-alpine

# Render injecte PORT ; 80 en repli pour un usage local.
ENV PORT=80

RUN rm -f /etc/nginx/conf.d/default.conf
COPY gateway.conf.template /etc/nginx/templates/gateway.conf.template
