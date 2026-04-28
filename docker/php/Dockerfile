FROM php:latest AS base

RUN apt-get update -qq && apt-get install -y --no-install-recommends \
    libicu-dev libonig-dev libzip-dev zip unzip \
    && docker-php-ext-install pdo_mysql intl mbstring opcache zip \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

WORKDIR /var/www/app
