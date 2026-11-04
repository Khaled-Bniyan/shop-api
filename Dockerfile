FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
        $PHPIZE_DEPS git unzip libzip-dev libpng-dev libonig-dev libxml2-dev libsqlite3-dev \
    && pecl install grpc-1.85.0 \
    && docker-php-ext-enable grpc \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring zip gd bcmath \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
