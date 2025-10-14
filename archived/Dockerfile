FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends nano less ca-certificates \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

# Installe pdo_mysql (pas besoin de sqlite ici)
RUN docker-php-ext-install pdo pdo_mysql

COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf
# COPY docker/entrypoint.sh /entrypoint.sh
# RUN chmod +x /entrypoint.sh

# ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
