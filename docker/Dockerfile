# ─────────────────────────────────────────────────────────────
# Stage 1 – Composer dependencies (tiny image, no runtime bloat)
# ─────────────────────────────────────────────────────────────
FROM composer:2.7 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --ignore-platform-reqs \
        --quiet

COPY . .
RUN composer dump-autoload --optimize --no-dev --quiet

# ─────────────────────────────────────────────────────────────
# Stage 2 – Production PHP-FPM image
# ─────────────────────────────────────────────────────────────
FROM php:8.2-fpm

# ── System packages ───────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        curl \
        unzip \
        git \
        supervisor \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        bcmath \
        gd \
        zip \
        xml \
        opcache \
        pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove git \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# ── PHP config ────────────────────────────────────────────────
COPY docker/php/php.ini     /usr/local/etc/php/conf.d/app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# ── Supervisor config ─────────────────────────────────────────
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ── Application ───────────────────────────────────────────────
WORKDIR /var/www

COPY --from=vendor /app/vendor ./vendor
COPY . .

# ── Permissions ───────────────────────────────────────────────
RUN mkdir -p storage/framework/{cache/data,sessions,views,testing} \
             storage/logs \
             bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 755 storage bootstrap/cache

# ── Entrypoint ────────────────────────────────────────────────
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]

# supervisord starts php-fpm + queue worker + scheduler in one container.
# Override CMD with ["php-fpm"] if you prefer separate containers.
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
