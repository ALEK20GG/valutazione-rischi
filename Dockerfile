FROM php:8.2-apache

# Abilita mod_rewrite
RUN a2enmod rewrite

# Installa estensioni PHP necessarie (PDO MySQL)
RUN docker-php-ext-install pdo pdo_mysql

# Imposta la document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

# Configura Apache per ascoltare sulla porta 10000 (richiesta da Render)
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf && \
    sed -i 's/:80/:10000/' /etc/apache2/sites-available/000-default.conf

# Copia il codice dell'applicazione
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# Espone la porta per Render
EXPOSE 10000

# Avvia Apache in foreground (processo principale del container)
CMD ["apache2-foreground"]