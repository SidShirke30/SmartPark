
FROM php:8.2-apache

RUN docker-php-ext-install mysqli

RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/frontend

RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}/!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

COPY . /var/www/html

RUN echo '#!/bin/sh\nsed -i "s/Listen 80/Listen ${PORT:-10000}/" /etc/apache2/ports.conf\nsed -i "s/:80>/:${PORT:-10000}>/" /etc/apache2/sites-available/000-default.conf\nexec apache2-foreground' > /usr/local/bin/start-render.sh \
    && chmod +x /usr/local/bin/start-render.sh

CMD ["/usr/local/bin/start-render.sh"]