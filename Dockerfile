FROM php:8.3-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    postgresql-client \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP necesarias para CodeIgniter 4
RUN docker-php-ext-install \
    pdo_mysql \
    mysqli \
    zip \
    intl \
    gd \
    opcache \
    mbstring \
    exif \
    pcntl \
    bcmath \
    xml \
    pdo_pgsql \
    pgsql

# Habilitar mod_rewrite para Apache
RUN a2enmod rewrite headers

# Configurar el directorio de trabajo
WORKDIR /var/www/html

# Copiar el código fuente de la aplicación
COPY . /var/www/html/

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar dependencias de Composer (si usas Composer)
RUN if [ -f "composer.json" ]; then composer install --no-interaction --optimize-autoloader; fi

# Crear directorios writable con permisos adecuados
RUN mkdir -p writable/cache writable/logs writable/session writable/uploads \
    && chown -R www-data:www-data writable \
    && find writable -type d -exec chmod 755 {} \; \
    && find writable -type f -exec chmod 644 {} \;

# Configurar el Virtual Host de Apache
RUN echo "<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>" > /etc/apache2/sites-available/000-default.conf

# Exponer el puerto 80
EXPOSE 80

# Establecer www-data como propietario de todos los archivos
RUN chown -R www-data:www-data /var/www/html

# Iniciar Apache en primer plano
CMD ["apache2-foreground"]