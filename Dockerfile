FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts

FROM vendor AS wayfinder
COPY . .
RUN php artisan package:discover --ansi \
    && php artisan wayfinder:generate --with-form

FROM node:22-bookworm-slim AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
COPY --from=wayfinder /app/resources/js/actions ./resources/js/actions
COPY --from=wayfinder /app/resources/js/routes ./resources/js/routes
COPY --from=wayfinder /app/resources/js/wayfinder ./resources/js/wayfinder
ENV SKIP_WAYFINDER_GENERATE=1
# Gera o bundle do navegador (public/build) e o do renderer (bootstrap/ssr) a partir do mesmo código.
RUN npm run build:ssr

# Renderer Inertia SSR: processo Node separado, sem PHP, segredos ou porta publicada.
# Uso: `docker build --target ssr`; o PHP o alcança por INERTIA_SSR_URL na rede interna.
FROM node:22-bookworm-slim AS ssr
ENV NODE_ENV=production
WORKDIR /app
COPY package*.json ./
RUN npm ci --omit=dev --ignore-scripts && npm cache clean --force
COPY --from=frontend /app/bootstrap/ssr ./bootstrap/ssr
COPY docker/ssr-server.mjs docker/ssr-healthcheck.mjs ./docker/
USER node
EXPOSE 13714
HEALTHCHECK --interval=15s --timeout=5s --start-period=10s --retries=3 CMD ["node", "docker/ssr-healthcheck.mjs"]
STOPSIGNAL SIGTERM
CMD ["node", "docker/ssr-server.mjs"]

# Estágio final (padrão do `docker compose build`): PHP-FPM, worker e scheduler.
FROM php:8.4-fpm
RUN apt-get update && apt-get install -y --no-install-recommends git curl libpng-dev libonig-dev libxml2-dev libzip-dev zip unzip && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction && mkdir -p /opt/public-build && chown -R www-data:www-data storage bootstrap/cache
COPY --from=frontend /app/public/build /opt/public-build
# O PHP só envia a página ao renderer quando detecta o bundle (inertia.ssr.ensure_bundle_exists).
COPY --from=frontend /app/bootstrap/ssr ./bootstrap/ssr
COPY docker-entrypoint.sh /usr/local/bin/infra-entrypoint
RUN chmod +x /usr/local/bin/infra-entrypoint
ENTRYPOINT ["infra-entrypoint"]
CMD ["php-fpm", "-F"]
