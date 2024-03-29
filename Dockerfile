FROM composer:2
FROM php:7.4-fpm

# Packages install
RUN export DEBIAN_FRONTEND=noninteractive \
    && apt-get clean \
    && apt-get -y update \
    && apt-get install -y \
        git \
        unzip \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN usermod -u 1000 www-data
RUN groupmod -g 1000 www-data

RUN mkdir -p /app && chown -R www-data:www-data /app

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN composer self-update 1.10.19

USER www-data

WORKDIR /app