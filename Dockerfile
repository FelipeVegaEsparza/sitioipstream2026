FROM php:8.2-fpm-alpine

RUN apk add --no-cache nginx \
    && docker-php-ext-install pdo pdo_mysql

COPY . /var/www/html

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

RUN mkdir -p /var/www/html/uploads/news /var/www/html/uploads/plans \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 755 /var/www/html/uploads

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
