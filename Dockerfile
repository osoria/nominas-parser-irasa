FROM php:7.2.18-apache-stretch

# xdebug, mysqli, pdo, pdo_mysql
RUN pecl install xdebug-2.6.0 \
    && docker-php-ext-enable xdebug \
    && docker-php-ext-configure mysqli --with-mysqli=mysqlnd \
    && docker-php-ext-install mysqli pdo pdo_mysql
ARG XDEBUG_INI=/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.remote_enable = on" >> ${XDEBUG_INI}
RUN echo "xdebug.idekey = phpstorm" >> ${XDEBUG_INI}
RUN echo "xdebug.remote_host = docker.for.mac.localhost" >> ${XDEBUG_INI}
RUN echo "xdebug.remote_autostart = on" >> ${XDEBUG_INI}

# intl
RUN apt-get -y update \
    && apt-get install -y libicu-dev\
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl

# git
RUN apt-get update && \
    apt-get upgrade -y --allow-unauthenticated && \
    apt-get install -y git

# opcache
RUN docker-php-ext-install opcache
RUN docker-php-ext-enable opcache

# composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# localtime
RUN rm /etc/localtime
RUN echo "Europe/Madrid" > /etc/timezone
RUN ln -s /usr/share/zoneinfo/Europe/Madrid /etc/localtime
RUN dpkg-reconfigure -f noninteractive tzdata

# php.ini configuration
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

# zip
RUN apt-get install -y libzip-dev && pecl install zlib zip
RUN echo "extension=zip.so" >> /usr/local/etc/php/php.ini
RUN apt-get install -y git

# apache configuration
RUN sed -ri -e 's!/var/www/html!/var/www/html/nominasparser/public!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/var/www/html/nominasparser/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN a2enmod rewrite

# remove composer-setup.php
RUN php -r "if (file_exists('installer')) unlink('installer');"

# symfony cli
RUN curl -sS https://get.symfony.com/cli/installer | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

# Pdftk - para cortar PDFs
RUN apt-get install -y pdftk

# restart apache
RUN service apache2 restart
