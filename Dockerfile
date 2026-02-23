FROM php:8.2-apache

# Aggiorna i pacchetti di sistema e installa librerie per PostgreSQL
RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y \
        libpq-dev && \
    rm -rf /var/lib/apt/lists/*

# Abilita mod_rewrite
RUN a2enmod rewrite

# Installa estensione PHP pdo_pgsql per PostgreSQL
RUN docker-php-ext-install pdo_pgsql

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