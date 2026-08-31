# Laravel 13 exige PHP ^8.3 (vendor/laravel/framework/composer.json).
# Se usa 8.4 por ser la estable vigente y coincidir con el PHP del host.
FROM php:8.4-fpm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        procps \
        default-mysql-client \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# mbstring ya viene compilada en la imagen base: reinstalarla rompe el build.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# El usuario del contenedor comparte UID/GID con el del host para que los
# archivos escritos en el bind mount del codigo no queden como root.
RUN groupmod -o -g "${GID}" www-data \
    && usermod -o -u "${UID}" -g "${GID}" www-data

# storage/ debe existir en la imagen y pertenecer a www-data ANTES de que Docker
# cree el volumen "archivos-storage": al inicializarlo, Docker copia contenido y
# permisos desde este path. Si aqui es de root, el volumen nace de root y el
# contenedor (que corre como UID 1000) no puede escribir en storage/app.
# /var/www es ademas el HOME de www-data; sin permisos ahi, tinker falla al
# intentar escribir su cache en /var/www/.config/psysh.
RUN mkdir -p /var/www/html/storage/app/public \
             /var/www/html/storage/app/private \
             /var/www/html/storage/framework/cache \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www

WORKDIR /var/www/html
