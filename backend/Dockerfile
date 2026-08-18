# ============ Dependencies ============
FROM php:8.3-fpm-alpine AS builder

# Instalar extensiones necesarias
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo_pgsql zip bcmath

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copiar archivos de dependencias y lo mínimo necesario para los hooks de Composer.
# artisan requiere bootstrap/app.php para ejecutar package:discover en post-autoload-dump.
COPY composer.json composer.lock artisan ./
COPY bootstrap bootstrap

# Instalar dependencias sin scripts (el .env no está disponible en build).
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copiar resto del código
COPY . .

# Regenerar autoloader y ejecutar hooks de Composer con todo el código presente.
RUN composer dump-autoload --optimize

# ============ Runner ============
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    nginx \
    supervisor \
    && docker-php-ext-install pdo_pgsql zip bcmath \
    && apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .phpize-deps

# Crear usuario no-root
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

WORKDIR /var/www

# Copiar aplicación desde builder
COPY --from=builder --chown=www:www /var/www /var/www

# Copiar configuración de Nginx, Supervisor y entrypoint
COPY docker/nginx/api.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
