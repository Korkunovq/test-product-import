FROM composer:2 AS vendor
WORKDIR /app
COPY backend/composer.json backend/composer.lock* ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --ignore-platform-reqs

FROM php:8.4-fpm-alpine AS runtime
RUN apk add --no-cache postgresql-dev rabbitmq-c-dev libzip-dev $PHPIZE_DEPS \
    && pecl install amqp \
    && docker-php-ext-enable amqp \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && apk del $PHPIZE_DEPS

WORKDIR /var/www
COPY backend/ .
COPY --from=vendor /app/vendor ./vendor

RUN mkdir -p storage/imports storage/images \
    && chown -R www-data:www-data storage \
    && sed -i 's/listen = 127.0.0.1:9000/listen = 9000/' /usr/local/etc/php-fpm.d/www.conf

EXPOSE 9000
CMD ["php-fpm"]
