# PHP 8.2 con Apache
FROM php:8.2-apache

# Habilitar módulos de Apache y extensiones de PHP
RUN a2enmod headers rewrite \
 && docker-php-ext-install mysqli

# Copiar el código
WORKDIR /var/www/html
COPY . /var/www/html

# Permisos básicos
RUN chown -R www-data:www-data /var/www/html

# Seguridad/CORS simples (igual que tu .htaccess)
# Si ya tenés .htaccess, dejalo: Apache lo respetará.
# Variables de entorno de DB se leen en conexion.php (getenv())

# Exponer puerto (Render ignora EXPOSE, pero queda prolijo)
EXPOSE 8080

# Apache usa el puerto interno (Render enrutará al 10000 por su lado)
CMD ["apache2-foreground"]
