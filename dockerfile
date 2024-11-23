# Usa una imagen oficial de PHP con Apache
FROM php:8.2-apache

# Instalar dependencias necesarias para Slim y Composer
RUN apt-get update && apt-get install -y \
    unzip git libzip-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar el código del servicio a la imagen
COPY . /var/www/html/



# Copiar la configuración personalizada de Apache
COPY custom-rewrite.conf /etc/apache2/conf-available/custom-rewrite.conf

# Habilitar la configuración personalizada
RUN a2enconf custom-rewrite


# Establecer el directorio de trabajo
WORKDIR /var/www/html/

# Asegurar que los permisos sean correctos
RUN chown -R www-data:www-data /var/www/html

# Instalar las dependencias del proyecto
RUN composer install --no-dev --optimize-autoloader

# Exponer el puerto por defecto de Apache
EXPOSE 80

# Comando por defecto para iniciar Apache
CMD ["apache2-foreground"]