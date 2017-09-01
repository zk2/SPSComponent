FROM php:7.1

MAINTAINER Evgeniy Budanov <budanov.ua@gmail.com>

RUN echo "deb http://ftp.de.debian.org/debian stretch main" >> /etc/apt/sources.list

RUN apt-get update \
    && apt-get -y install \
          git \
          openssl \
          unzip \
          wget \
          libicu-dev \
          libpq-dev

RUN docker-php-ext-install \
        pdo \
        pgsql \
        mysqli \
        pdo_pgsql \
        pdo_mysql \
        bcmath \
        opcache \
        intl

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/ \
    && rm -f /usr/local/bin/composer \
    && ln -s /usr/local/bin/composer.phar /usr/local/bin/composer

ADD conf.ini /usr/local/etc/php/conf.d/
RUN chmod +r /usr/local/etc/php/conf.d/conf.ini

WORKDIR /var/www
