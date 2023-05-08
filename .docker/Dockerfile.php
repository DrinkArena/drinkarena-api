FROM php:8.2-fpm

RUN apt update \
    && apt install -y zlib1g-dev g++ git libicu-dev zip libzip-dev libpq-dev \
    && docker-php-ext-install intl opcache pdo pdo_pgsql \
    && pecl install apcu \
    && docker-php-ext-enable pdo pdo_pgsql apcu \
    && docker-php-ext-configure zip \
    && docker-php-ext-install zip

WORKDIR /var/www

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN groupadd -g 2000 drinkarena && useradd -m -u 2001 -g drinkarena drinkarena 

RUN mkdir drinkarena

WORKDIR /var/www/drinkarena
COPY . .
USER root
RUN chmod -R 775 ./
RUN chown -R drinkarena:drinkarena ./
USER drinkarena

RUN composer install

# RUN curl -sS https://get.symfony.com/cli/installer | bash
# USER root
# RUN cp /home/drinkarena/.symfony5/bin/symfony /usr/local/bin/symfony

RUN php bin/console doctrine:database:create && \
    php bin/console doctrine:schema:update --force && \
    php bin/console lexik:jwt:generate-keypair --overwrite && \
    php bin/console doctrine:fixtures:load -n

