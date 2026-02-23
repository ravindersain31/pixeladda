FROM composer:2.8 AS composer

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./

RUN composer install \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts \
    --no-progress \
    --ignore-platform-req=ext-imagick \
    --ignore-platform-req=ext-gd \
    --ignore-platform-req=ext-soap

RUN which composer;

FROM node:lts-alpine AS node-build

WORKDIR /app

COPY package*.json ./

COPY yarn.lock ./

COPY --from=composer /app/vendor vendor/

RUN apk add --no-cache git

RUN yarn install --force

COPY . .

RUN yarn build

FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

RUN apk update \
    && apk add --no-cache \
       $PHPIZE_DEPS \
       curl \
       nginx \
       zip \
       zlib-dev \
       imagemagick \
       imagemagick-dev \
       pcre-dev \
       libzip-dev \
       libxml2-dev \
       icu-dev \
       libwebp-dev \
       freetype-dev \
       libpng-dev \
       jpeg-dev \
       libjpeg-turbo-dev \
       oniguruma-dev \
       php-soap \
       ghostscript \
       libtool

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

RUN pecl download imagick \
    && tar -xzf imagick-*.tgz \
    && cd imagick-* \
    && phpize \
    && ./configure CPPFLAGS='-Dphp_strtolower=zend_str_tolower' \
    && make -j$(nproc) \
    && make install \
    && cd .. \
    && rm -rf imagick-*

RUN pecl install oauth redis

RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN docker-php-ext-install mbstring zip intl xml

RUN docker-php-ext-install gd bcmath soap

RUN docker-php-ext-enable oauth imagick soap redis

COPY docker/nginx.conf /etc/nginx/nginx.conf

COPY docker/conf.d /etc/nginx/conf.d

COPY docker/ImageMagick-Policy.xml /etc/ImageMagick-6/policy.xml

COPY docker/php.ini /usr/local/etc/php/conf.d/app-php.ini

COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf

COPY docker/health.php /var/www/html/public/health.php

COPY . .

COPY --from=composer /app/vendor vendor/

COPY --from=node-build /app/public/build /var/www/html/public/build

COPY entrypoint.sh /usr/bin/

EXPOSE 8080

RUN set -xe \
    && chmod +x /var/www/html/bin/console \
    && mkdir -p /var/www/html/var/cache/prod/vich_uploader \
    /var/www/html/var/invoices \
    && chmod -R 777 /var/www/html/var

RUN mkdir -p /var/www/nginx/client_body_temp
RUN chown -R www-data:www-data /var/www/nginx/client_body_temp
RUN chmod -R 777 /var/www/nginx/client_body_temp

# Let supervisord start nginx & php-fpm
ENTRYPOINT ["sh", "/usr/bin/entrypoint.sh"]