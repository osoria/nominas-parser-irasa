FROM php:7.4-apache

# PHP 7.4 sobre Debian Buster. Instalamos todas las dependencias necesarias.
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        libicu-dev \
        git \
        libzip-dev \
        pdftk \
        && \
    docker-php-ext-configure intl && docker-php-ext-install intl && \
    docker-php-ext-install mysqli pdo pdo_mysql && \
    docker-php-ext-install opcache && docker-php-ext-enable opcache && \
    docker-php-ext-install zip && \
    rm -rf /var/lib/apt/lists/*

# xdebug
RUN pecl install xdebug-3.1.6 && docker-php-ext-enable xdebug
RUN echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.idekey=phpstorm" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Composer 2.x (el lockfile usa plugin-api 2.x tras la migración a Composer 2)
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

RUN curl -sS https://get.symfony.com/cli/installer | bash && \
    mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

RUN service apache2 restart