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
RUN npm run build

FROM php:8.4-fpm
RUN apt-get update && apt-get install -y --no-install-recommends git curl libpng-dev libonig-dev libxml2-dev libzip-dev zip unzip && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip opcache && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction && mkdir -p /opt/public-build && chown -R www-data:www-data storage bootstrap/cache
COPY --from=frontend /app/public/build /opt/public-build
COPY docker-entrypoint.sh /usr/local/bin/infra-entrypoint
RUN chmod +x /usr/local/bin/infra-entrypoint
ENTRYPOINT ["infra-entrypoint"]
CMD ["php-fpm", "-F"]
