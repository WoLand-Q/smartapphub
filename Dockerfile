# PHP + Apache
FROM php:8.3-apache

# Ставим системные зависимости для pdo_sqlite и curl (нужен healthcheck'у)
RUN apt-get update \
 && apt-get install -y --no-install-recommends libsqlite3-dev curl \
 && rm -rf /var/lib/apt/lists/*

# Включаем нужные модули Apache и расширения PHP
RUN a2enmod rewrite headers \
 && docker-php-ext-install pdo pdo_sqlite

# PROD php.ini
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Разрешаем .htaccess при необходимости
RUN sed -ri "s/AllowOverride\s+None/AllowOverride All/i" /etc/apache2/apache2.conf

# Код
WORKDIR /var/www/html
COPY . /var/www/html

# Права
RUN chown -R www-data:www-data /var/www/html \
 && find /var/www/html -type d -exec chmod 0755 {} \; \
 && find /var/www/html -type f -exec chmod 0644 {} \;

EXPOSE 80
