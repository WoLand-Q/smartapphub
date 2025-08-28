# PHP + Apache под простые PHP-проекты
FROM php:8.3-apache

# Модули Apache/PHP
RUN a2enmod rewrite headers \
 && docker-php-ext-install pdo pdo_sqlite

# Prod php.ini
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# AllowOverride All (чтобы .htaccess работал при надобности)
RUN sed -ri "s/AllowOverride\s+None/AllowOverride All/i" /etc/apache2/apache2.conf

# Код
WORKDIR /var/www/html
COPY . /var/www/html

# Права (базовые)
RUN chown -R www-data:www-data /var/www/html \
 && find /var/www/html -type d -exec chmod 0755 {} \; \
 && find /var/www/html -type f -exec chmod 0644 {} \;

EXPOSE 80
