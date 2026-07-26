# syntax=docker/dockerfile:1.7
# License-server — slanke Blade-only Laravel-app (php-fpm), non-root.

FROM composer:2.8 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

FROM php:8.4-fpm-alpine AS runtime

RUN apk add --no-cache bash postgresql-client icu-libs libzip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev postgresql-dev linux-headers \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql intl zip opcache bcmath \
    && apk del .build-deps

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor

COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/entrypoint

RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

USER www-data
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
