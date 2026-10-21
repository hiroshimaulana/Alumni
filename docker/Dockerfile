FROM php:8.3-apache

# Install PDO MySQL extension and common utilities
RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite

# Configure Apache document root
WORKDIR /var/www/html

# Expose HTTP port
EXPOSE 80
