FROM php:7.3-alpine

ADD / /app/

RUN apk update && \
    apk upgrade && \
    apk add bash su-exec && \
    # install app
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    cd /app && \
    composer install --no-dev --no-interaction --no-progress && \
    composer dump-autoload --optimize --no-dev --classmap-authoritative && \
    composer clear-cache

ENTRYPOINT ["/run.sh", "php", "/app/bin/analytics-convert"]
