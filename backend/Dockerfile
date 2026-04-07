FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libicu-dev \
    default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql mbstring intl zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php-fpm/zz-listen.conf /usr/local/etc/php-fpm.d/zz-listen.conf

WORKDIR /var/www/html

CMD ["php-fpm"]