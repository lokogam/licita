# ---------------------------------------------------------------------------
# Stage 1: build the Vue SPA
# ---------------------------------------------------------------------------
FROM node:22-alpine AS frontend-build

WORKDIR /app

COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY frontend/ ./
RUN npm run build

# ---------------------------------------------------------------------------
# Stage 2: PHP 8.2 + Apache runtime shared by development and production
# ---------------------------------------------------------------------------
FROM php:8.2-apache AS base

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN install-php-extensions pdo_mysql zip gd \
    && a2enmod rewrite headers

COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache/security.conf /etc/apache2/conf-available/zz-security.conf
COPY docker/php/app.ini /usr/local/etc/php/conf.d/zz-app.ini

RUN a2enconf zz-security

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# Stage 3: development image (source code is bind-mounted by Compose)
# ---------------------------------------------------------------------------
FROM base AS development

RUN install-php-extensions pcov

# ---------------------------------------------------------------------------
# Stage 4: production image (API + compiled SPA in one container)
# ---------------------------------------------------------------------------
FROM base AS production

COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --optimize-autoloader

COPY backend/ ./
COPY --from=frontend-build /app/dist/ ./public/

RUN chown -R www-data:www-data storage
