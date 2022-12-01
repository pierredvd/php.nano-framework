FROM        debian:bullseye-slim
MAINTAINER  Pierre DAVID <pierre_dvd@msn.com>
EXPOSE      80 443 5432

ENV		    DEBIAN_FRONTEND noninteractive
ENV 	    LANG C.UTF-8

RUN     set -eux; \
        apt-get update; \
        apt-get install --yes --force-yes \
            software-properties-common \
            apt-transport-https \
            lsb-release \
            ca-certificates

# APACHE 2.24 + PHP 7.4 INSTALL
COPY    ./.docker/php/php.gpg /tmp/deb.sury.org-php.gpg
RUN     set -eux; \
#        apt-key add /tmp/deb.sury.org-php.gpg; \
        sh -c 'echo "deb [signed-by=/tmp/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'; \
        apt-get update; \
        apt-get upgrade -y; \
        apt-get install -y --no-install-recommends \
            apache2 \
            php7.4 \
            php7.4-cli \
            php7.4-common \
            libapache2-mod-php7.4 \
            php7.4-json \
            php7.4-opcache \
            php7.4-zip \
            libpq5 php7.4-pgsql \
            php7.4-mysql; \
        service apache2 restart

## APACHE 2.24 + PHP 7.4 CONFIGURATION
COPY    ./.docker/php/apache2.conf /etc/apache2/apache2.conf
COPY    ./.docker/php/ports.conf /etc/apache2/ports.conf
COPY    ./.docker/php/php.ini /etc/php/7.4/apache2/php.ini
COPY    ./.docker/php/sites-available/localhost.conf /tmp/localhost.conf
RUN     set -eux; \
        a2dissite 000-default.conf; \
        rm -rf /etc/apache2/sites-enabled/*; \
        rm -rf /etc/apache2/sites-available/*; \
        cp /tmp/localhost.conf /etc/apache2/sites-available/localhost.conf; \
        a2ensite localhost.conf; \
        openssl req -new -x509 -days 3650 -nodes -out /etc/ssl/certs/apache-selfsigned.pem -keyout /etc/ssl/certs/apache-selfsigned.key -subj "/C=FR/ST=Here/L=Here/O=Dev/OU=Dev/CN=localhost"; \
        openssl req -new -x509 -days 3650 -nodes -out /etc/ssl/certs/apache-selfsigned.pem -keyout /etc/ssl/certs/apache-selfsigned.key -subj "/C=FR/ST=Here/L=Here/O=Dev/OU=Dev/CN=localhost"; \
        a2enmod ssl rewrite headers; \
        phpenmod pdo_pgsql pdo_mysql; \
        rm -rf ./var/www/*; \
        mkdir /var/www/tmp; \
        chgrp -R www-data /var/www; \
        chmod -R 0775 /var/www; \
        chmod g+s /var/www

# POSTGRES INSTALL
RUN     set -eux; \
        apt-get install -y --no-install-recommends \
            postgresql \
            postgresql-contrib

## POSTGRES CONFIGURATION
COPY ./.docker/postgres/pg_hba.conf /etc/postgresql/13/main/pg_hba.conf
RUN chown postgres /etc/postgresql/13/main/pg_hba.conf; \
    chgrp postgres /etc/postgresql/13/main/pg_hba.conf; \
    chmod 0644 /etc/postgresql/13/main/pg_hba.conf
COPY ./.docker/postgres/postgresql.conf /etc/postgresql/13/main/postgresql.conf
RUN chown postgres /etc/postgresql/13/main/postgresql.conf; \
    chgrp postgres /etc/postgresql/13/main/postgresql.conf; \
    chmod 0644 /etc/postgresql/13/main/postgresql.conf; \
    service postgresql restart

# NodeJS For postgres bridger
RUN     set -eux; \
        apt-get install -y --no-install-recommends \
            nodejs
COPY    ./.docker/postgres/bridger.js /etc/postgresql/13/bridger.js
RUN     chmod 0775 /etc/postgresql/13/bridger.js

# Clean up
RUN     set -eux; \
        rm -rf /var/lib/apt/lists/*; \
        rm -rf /tmp/*

# VOLUME
RUN chmod -R 2775 /var/www
VOLUME ["/var/www", "/var/lib/postgresql/data"]

# BOOTLOAD
COPY ./.docker/init.sh /usr/sbin/init.sh
RUN   chmod 0775 /usr/sbin/init.sh
CMD  ["/usr/sbin/init.sh"]