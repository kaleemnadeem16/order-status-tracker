FROM composer:2 AS dependencies
WORKDIR /build
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist

FROM php:8.3-apache
RUN docker-php-ext-install pdo_mysql && a2enmod rewrite headers
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/apache.conf /etc/apache2/conf-available/tracker.conf
RUN a2enconf tracker && sed -i 's|CustomLog ${APACHE_LOG_DIR}/access.log combined|CustomLog /dev/null combined|' /etc/apache2/sites-available/000-default.conf
COPY . /var/www/html/
COPY --from=dependencies /build/vendor /var/www/html/vendor
