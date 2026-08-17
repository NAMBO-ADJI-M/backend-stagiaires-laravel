FROM php:8.3-fpm

# ------------------------------------------------------------------
# Dépendances système + extensions PHP nécessaires à Laravel + MySQL
# ------------------------------------------------------------------
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ------------------------------------------------------------------
# Dépendances PHP (installées avant de copier tout le code pour
# profiter du cache Docker sur les rebuilds)
# ------------------------------------------------------------------
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# ------------------------------------------------------------------
# Code de l'application
# ------------------------------------------------------------------
COPY . .

RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# ------------------------------------------------------------------
# Config Nginx + Supervisor
# ------------------------------------------------------------------
COPY docker/nginx/default.conf /etc/nginx/sites-available/default
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 8080

CMD ["/usr/local/bin/start.sh"]