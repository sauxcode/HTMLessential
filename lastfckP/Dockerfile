FROM php:8.2-apache

# Instalar dependencias del sistema necesarias para SQLite en PHP
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Copiar los archivos de la aplicación al servidor web
COPY . /var/www/html/

# Dar permisos correctos a la carpeta web
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Exponer el puerto 80
EXPOSE 80
