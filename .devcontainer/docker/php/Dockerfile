FROM composer:2 AS composer

FROM php:latest AS base

RUN apt-get update -qq && apt-get install -y --no-install-recommends \
    libicu-dev libzip-dev zip unzip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql intl zip \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY docker/php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY docker/php/conf.d/error_reporting.ini /usr/local/etc/php/conf.d/error_reporting.ini
COPY docker/php/conf.d/upload.ini /usr/local/etc/php/conf.d/upload.ini

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /workspaces/farmers-store-inventory

