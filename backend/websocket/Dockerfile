FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --prefer-dist --no-interaction || composer install --prefer-dist --no-interaction

COPY . .

EXPOSE 8081
CMD ["php", "-d", "display_errors=0", "-d", "error_reporting=E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED", "server.php"]