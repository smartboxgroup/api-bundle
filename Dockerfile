FROM composer:1 AS composer

FROM php:7.0-fpm

MAINTAINER Andrey Moretti <andrey.moretti@smartbox.com>

COPY --from=composer /usr/bin/composer /usr/bin/composer

#Installing and enabling features and PHP extension needed
RUN apt-get update -y \
  && apt-get install -y libxml2-dev libmcrypt-dev git zip \
  && apt-get clean -y \
  && docker-php-ext-install soap mcrypt

#RUN echo 'memory_limit = -1' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini;