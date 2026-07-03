# Link Shortener — FrankenPHP (Caddy-based) + PHP 8.4
FROM dunglas/frankenphp:1-php8.4

# System deps + PHP extensions (install-php-extensions ships with the image)
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    intl \
    zip \
    gd \
    bcmath \
    opcache \
    pcntl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Explicit Caddy/FrankenPHP config (roots to public/, HTTP only when SERVER_NAME=:8080)
COPY docker/Caddyfile /etc/caddy/Caddyfile

WORKDIR /app

# In dev the source is bind-mounted over /app and `composer install` is run there.
# For a standalone (non-mounted) build, uncomment:
# COPY . /app
# RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# FrankenPHP listens on the port encoded in SERVER_NAME (e.g. :8080)
EXPOSE 8080
