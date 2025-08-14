# PHP + Apache
FROM php:8.2-apache

# Extensiones y módulos necesarios
RUN docker-php-ext-install mysqli && a2enmod rewrite headers

# Docroot y copia del código
WORKDIR /var/www/html
COPY . /var/www/html/

# Permisos básicos
RUN chown -R www-data:www-data /var/www/html

EXPOSE 8080
CMD ["apache2-foreground"]
