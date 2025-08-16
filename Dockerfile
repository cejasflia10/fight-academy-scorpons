# PHP + Apache
FROM php:8.2-apache

# Paquetes del sistema necesarios para extensiones y HTTPS
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libzip-dev \
    ca-certificates \
  && rm -rf /var/lib/apt/lists/*

# Extensiones PHP
RUN docker-php-ext-install mysqli curl

# Módulos de Apache
RUN a2enmod rewrite headers

# Ajustes de PHP para upload (20 MB; cambia si querés)
RUN { \
      echo "file_uploads=On"; \
      echo "upload_max_filesize=20M"; \
      echo "post_max_size=20M"; \
      echo "max_execution_time=120"; \
      echo "memory_limit=256M"; \
    } > /usr/local/etc/php/conf.d/uploads.ini

# Docroot y código
WORKDIR /var/www/html
COPY . /var/www/html/

# Carpeta para subidas (fallback) y permisos
RUN mkdir -p /var/www/html/uploads \
 && chown -R www-data:www-data /var/www/html

# Apache ya escucha en 80; Render funciona bien así
EXPOSE 80
CMD ["apache2-foreground"]
