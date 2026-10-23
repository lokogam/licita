# ---------------------------------------------------------------------------
# Backend: PHP 8.2 + Apache serving the REST API
# ---------------------------------------------------------------------------
FROM php:8.2-apache AS base

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN install-php-extensions bcmath pdo_mysql zip gd \
    && a2enmod rewrite headers

COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache/security.conf /etc/apache2/conf-available/zz-security.conf
COPY docker/php/app.ini /usr/local/etc/php/conf.d/zz-app.ini

RUN a2enconf zz-security

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# Development: source code is bind-mounted by Compose; pcov enables coverage
# ---------------------------------------------------------------------------
FROM base AS development

RUN install-php-extensions pcov

# ---------------------------------------------------------------------------
# Production: dependencies and source baked into the image
# ---------------------------------------------------------------------------
FROM base AS production

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --optimize-autoloader

COPY . .

RUN php bin/openapi-build.php     && chown -R www-data:www-data storage
