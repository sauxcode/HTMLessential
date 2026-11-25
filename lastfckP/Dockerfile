# Usar una imagen oficial de PHP con Apache
FROM php:8.2-apache

# Habilitar extensiones necesarias si hicieran falta (SQLite viene integrado en PHP)
RUN docker-php-ext-install pdo pdo_sqlite

# Copiar los archivos de la aplicación a la carpeta pública del servidor web
COPY . /var/www/html/

# Dar permisos de escritura a la carpeta para que SQLite pueda crear y actualizar la base de datos
RUN ch_own -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Exponer el puerto 80 para la web
EXPOSE 80
