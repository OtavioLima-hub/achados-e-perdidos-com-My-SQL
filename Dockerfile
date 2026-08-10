FROM php:8.2-apache

# Install dependencies and extensions for MongoDB & Redis
RUN apt-get update && apt-get install -y \
    libssl-dev \
    pkg-config \
    unzip \
    git \
    && pecl install mongodb redis \
    && docker-php-ext-enable mongodb redis \
    && a2enmod rewrite

# Configure Apache DocumentRoot to point to /var/www/html/public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory
WORKDIR /var/www/html

EXPOSE 80
