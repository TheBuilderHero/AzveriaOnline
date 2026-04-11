FROM nginx:1.27-alpine

# Ship nginx config inside the image for remote-context portability
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy Laravel app so nginx can serve public assets and resolve paths consistently
COPY laravel/ /var/www/html/

RUN mkdir -p /var/www/html/storage/app/public \
    && ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage
