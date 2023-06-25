FROM php:8.2.7-fpm-alpine3.18

RUN apk add --no-cache libzip-dev libxslt-dev libpng-dev icu-dev oniguruma-dev
RUN docker-php-ext-install pdo pdo_mysql zip xsl gd intl opcache exif mbstring

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


WORKDIR /var/www/drinkarena

COPY . .

RUN composer install

RUN php bin/console doctrine:database:create; \
    chmod 777 var/data.db; \
    php bin/console doctrine:schema:update --force; \
    php bin/console doctrine:fixtures:load -n; \
    php bin/console lexik:jwt:generate-keypair