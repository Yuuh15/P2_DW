FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /var/www/html/uploads/destinos

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-meridiano.ini

WORKDIR /var/www/html
