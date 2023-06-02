FROM php:fpm

RUN apt update \
    && apt-get update \
    && apt install git -y \
    && apt install -y librabbitmq-dev libssh-dev

# install standard extenstions
RUN apt-get install -y $PHPIZE_DEPS zip unzip

# install xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# install redis
RUN mkdir -p /usr/src/php/ext/redis \
    && curl -fsSL https://github.com/phpredis/phpredis/archive/5.3.4.tar.gz | tar xvz -C /usr/src/php/ext/redis --strip 1 \
    && docker-php-ext-install redis

# install composer
RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer
