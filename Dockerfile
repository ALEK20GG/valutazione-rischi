FROM php:8.2-apache

# Aggiorna i pacchetti e installa MariaDB + supervisor
RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y \
        mariadb-server \
        supervisor && \
    rm -rf /var/lib/apt/lists/*

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

# Copia lo script di inizializzazione del DB
COPY init.sql /docker-entrypoint-initdb.d/init.sql

# Copia la configurazione di supervisord
COPY supervisord.conf /etc/supervisor/supervisord.conf

# Prepara directory di MariaDB
RUN mkdir -p /var/run/mysqld /var/lib/mysql && \
    chown -R mysql:mysql /var/run/mysqld /var/lib/mysql && \
    mariadb-install-db --user=mysql --datadir=/var/lib/mysql

# Espone la porta per Render
EXPOSE 10000

# Avvia supervisor (che gestisce sia MySQL che Apache)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]