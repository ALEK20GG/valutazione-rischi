# Base image PHP + Apache
FROM php:8.2-apache

# Install MySQL server + utilities
RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y \
        default-mysql-server \
        supervisor && \
    rm -rf /var/lib/apt/lists/*

# Enable PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application code
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# Copy MySQL init script
COPY init.sql /docker-entrypoint-initdb.d/

# Copy supervisord configuration
COPY supervisord.conf /etc/supervisor/supervisord.conf

# Configure Apache to listen on port 10000 (required by Render)
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf && \
    sed -i 's/:80/:10000/' /etc/apache2/sites-available/000-default.conf

# Prepare MySQL directories and initialize MariaDB
RUN mkdir -p /var/run/mysqld /var/lib/mysql && \
    chown -R mysql:mysql /var/run/mysqld /var/lib/mysql && \
    mariadb-install-db --user=mysql --datadir=/var/lib/mysql

# Expose port for Render
EXPOSE 10000

# Start supervisor (which starts MySQL + Apache)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]