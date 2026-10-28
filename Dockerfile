FROM php:8.4.27-apache

# PHP 8.4 sobre Debian (Bookworm/Trixie). Instalamos las dependencias necesarias.
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        libicu-dev \
        git \
        libzip-dev \
        unzip \
        pdftk-java \
        && \
    docker-php-ext-install intl && \
    docker-php-ext-install pdo_mysql && \
    docker-php-ext-install zip && \
    rm -rf /var/lib/apt/lists/*

# xdebug (compatible con PHP 8.4)
RUN apt-get update && \
    apt-get install -y --no-install-recommends $PHPIZE_DEPS && \
    pecl install xdebug && docker-php-ext-enable xdebug && \
    apt-get purge -y --auto-remove $PHPIZE_DEPS && \
    rm -rf /var/lib/apt/lists/*
RUN echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.idekey=phpstorm" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Composer 2.x
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# localtime
RUN rm /etc/localtime && echo "Europe/Madrid" > /etc/timezone && \
    ln -s /usr/share/zoneinfo/Europe/Madrid /etc/localtime && \
    dpkg-reconfigure -f noninteractive tzdata

# php.ini
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini && \
    echo 'opcache.enable=0' >> /usr/local/etc/php/php.ini && \
    echo 'opcache.enable_cli=0' >> /usr/local/etc/php/php.ini

# apache
RUN sed -ri -e 's!/var/www/html!/var/www/html/nominasparser/public!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!/var/www/html/nominasparser/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf && \
    a2enmod rewrite

RUN php -r "if (file_exists('installer')) unlink('installer');"
