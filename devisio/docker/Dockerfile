FROM php:8.2-apache

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Installation des extensions PHP
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration Apache
RUN a2enmod rewrite
COPY ./docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Définition du répertoire de travail
WORKDIR /var/www/html

# Copie des fichiers de dépendances et installation
COPY composer.json composer.lock ./
RUN composer install --optimize-autoloader --no-scripts

# Copie du reste de l'application
COPY . .

# Finalisation de composer (scripts post-install)
RUN composer run-script post-install-cmd --no-interaction || true

# Permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]