# PHP + Apache
FROM php:8.3-apache

# Зависимости для pdo_sqlite и curl (для healthcheck)
RUN apt-get update \
 && apt-get install -y --no-install-recommends libsqlite3-dev curl \
 && rm -rf /var/lib/apt/lists/*

# Модули Apache/PHP
RUN a2enmod rewrite headers \
 && docker-php-ext-install pdo pdo_sqlite

# PROD php.ini
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Разрешить .htaccess (если используешь) и снять лимит тела запроса на уровне Apache
RUN sed -ri "s/AllowOverride\s+None/AllowOverride All/i" /etc/apache2/apache2.conf \
 && printf "\n<Directory /var/www/html>\n    LimitRequestBody 0\n</Directory>\n" \
      > /etc/apache2/conf-available/limitrequestbody.conf \
 && a2enconf limitrequestbody


COPY docker/php-custom.ini /usr/local/etc/php/conf.d/zzz-custom.ini

WORKDIR /var/www/html
COPY . /var/www/html

# Права
RUN chown -R www-data:www-data /var/www/html \
 && find /var/www/html -type d -exec chmod 0755 {} \; \
 && find /var/www/html -type f -exec chmod 0644 {} \;

EXPOSE 80
