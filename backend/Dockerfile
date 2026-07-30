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

# Copiar archivos de dependencias
COPY composer.json composer.lock ./

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copiar resto del código
COPY . .

# Optimizar Laravel
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# ============ Runner ============
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    nginx \
    supervisor \
    && docker-php-ext-install pdo_pgsql zip bcmath

# Crear usuario no-root
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

WORKDIR /var/www

# Copiar aplicación desde builder
COPY --from=builder --chown=www:www /var/www /var/www

# Copiar configuración de Nginx y Supervisor
COPY docker/nginx/api.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor.conf /etc/supervisor/conf.d/supervisord.conf

# Permisos
RUN chown -R www:www /var/www/storage /var/www/bootstrap/cache

USER www

EXPOSE 8000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
