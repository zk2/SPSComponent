FROM php:7.4-fpm-alpine

RUN apk add --no-cache \
        sudo \
        acl \
        icu-dev \
        postgresql-dev \
        libmcrypt-dev \
        git \
    && docker-php-ext-install \
        pdo_pgsql \
        pdo_mysql \
        bcmath \
        opcache \
        intl

ENV COMPOSER_ALLOW_SUPERUSER 1
RUN curl -sS https://getcomposer.org/installer | php -- \
      --install-dir=/usr/local/bin \
      --filename=composer

COPY entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]