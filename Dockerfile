# Dockerfile (raíz del repo)
FROM php:8.2-apache

# Extensión mysqli para MySQL
RUN docker-php-ext-install mysqli

# Habilitar módulos útiles
RUN a2enmod headers rewrite

# Copiar el código del repo al docroot
WORKDIR /var/www/html
COPY . /var/www/html

# Permisos básicos
RUN chown -R www-data:www-data /var/www/html

# Puerto (Render lo ignora, pero es correcto)
EXPOSE 8080

# Iniciar Apache
CMD ["apache2-foreground"]
