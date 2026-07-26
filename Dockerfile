# syntax=docker/dockerfile:1.7
#
# Board — multi-stage build.
# Result: a slim php-fpm image without build tools, running as a non-root user.
# The same image is reused for the app, reverb, queue-worker and scheduler
# services; the command differs per service (see docker-compose.yml).

############################
# Stage 1 — Composer deps  #
############################
FROM composer:2.8 AS vendor

WORKDIR /app

# Copy only what composer needs first, so dependency installs stay cached.
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

# Now copy the full source and build the optimized autoloader.
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

############################
# Stage 2 — Frontend build #
############################
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund

# Vendor is needed because the Ziggy/Inertia helpers are resolved from it.
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN npm run build

############################
# Stage 3 — Runtime image  #
############################
FROM php:8.4-fpm-alpine AS runtime

# Runtime OS packages + PHP extension build deps (removed after install).
RUN apk add --no-cache \
        bash \
        postgresql-client \
        icu-libs \
        libzip \
        libpng \
        libjpeg-turbo \
        freetype \
        oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        postgresql-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
        linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        intl \
        zip \
        gd \
        bcmath \
        exif \
        pcntl \
        opcache \
    && apk del .build-deps

# Sensible production PHP + opcache defaults.
COPY docker/php/php.ini /usr/local/etc/php/conf.d/board.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-board.conf

# Non-root runtime user. www-data already exists in the base image; we reuse it.
WORKDIR /var/www/html

# Application code + built artifacts.
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

# The VERSION file is written at build time from the git tag (see docker-compose
# build arg) and surfaced in the UI footer and /api/health.
ARG APP_VERSION=dev
RUN echo "${APP_VERSION}" > /var/www/html/VERSION

# Entrypoint prepares the app (APP_KEY/APP_SECRET, wait for db, migrate).
COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/entrypoint
COPY --chmod=0755 docker/wait-for-postgres.sh /usr/local/bin/wait-for-postgres

# Writable dirs for the non-root user.
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

USER www-data

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
