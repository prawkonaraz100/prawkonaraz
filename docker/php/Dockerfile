FROM php:8.3-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS \
        ffmpeg \
        git \
        unzip \
        libicu-dev \
        libpq-dev \
        libsqlite3-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        opcache \
        pdo_pgsql \
        pdo_sqlite \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/php/conf.d/performance.ini /usr/local/etc/php/conf.d/zz-performance.ini
COPY docker/php/fpm-pool.conf /usr/local/etc/php-fpm.d/zz-www.conf

COPY . /var/www/html

RUN mkdir -p \
        bootstrap/cache \
        database \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
    && chown -R www-data:www-data \
        /var/www/html/bootstrap/cache \
        /var/www/html/database \
        /var/www/html/storage
