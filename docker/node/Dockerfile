# Vite corre aparte de PHP-FPM, pero el plugin de Wayfinder invoca
# "php artisan wayfinder:generate" durante el arranque del dev server.
# Sin un PHP-CLI en esta imagen, Vite falla con "php: not found".
FROM node:22-alpine

RUN apk add --no-cache \
        php84 \
        php84-phar \
        php84-openssl \
        php84-mbstring \
        php84-tokenizer \
        php84-ctype \
        php84-fileinfo \
        php84-session \
        php84-dom \
        php84-xml \
        php84-xmlwriter \
        php84-simplexml \
        php84-pdo \
        php84-pdo_mysql \
        php84-curl \
        php84-iconv \
        php84-sodium \
    && ln -sf /usr/bin/php84 /usr/local/bin/php

WORKDIR /var/www/html
