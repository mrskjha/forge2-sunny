FROM php:8.4-apache

RUN docker-php-ext-install pdo_mysql
RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY . /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends unzip git \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --optimize-autoloader --no-interaction \
    && rm -rf /var/lib/apt/lists/*

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD sh -c 'sed -i "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf && sed -i "s/*:80/*:${PORT:-80}/" /etc/apache2/sites-available/000-default.conf && apache2-foreground'
