FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libicu-dev \
    default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql mbstring intl zip opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php-fpm/zz-listen.conf /usr/local/etc/php-fpm.d/zz-listen.conf
COPY docker/php-fpm/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

COPY laravel/composer.json laravel/composer.lock* ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    --no-scripts \
    || composer install --prefer-dist --no-interaction --no-scripts

COPY laravel/ ./

# Ensure runtime writable directories and public storage symlink exist in image
RUN mkdir -p storage/app/public bootstrap/cache \
    && rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php bootstrap/cache/routes-v7.php \
    && chown -R www-data:www-data storage bootstrap/cache \
    && ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage

CMD ["php-fpm"]