FROM php:8.4-fpm-alpine AS builder

RUN \
    apk update && \
    ( curl -sSLf https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - || echo 'return 1' ) | sh -s sockets bcmath pcntl pdo_mysql openswoole opcache zip intl && \
    rm -rf /tmp/pear

FROM composer:2.8 AS composer

COPY composer.json composer.lock /var/www/

RUN \
    cd /var/www && \
    composer install --no-interaction --no-progress --no-scripts --no-dev --ignore-platform-reqs
## Stage 2: Production environment
#FROM php:8.4-fpm
#
## Install only runtime libraries needed in production
## libfcgi-bin and procps are required for the php-fpm-healthcheck script
#RUN apt-get update && apt-get install -y --no-install-recommends \
#    libpq-dev \
#    libicu-dev \
#    libzip-dev \
#    libfcgi-bin \
#    procps \
#    && apt-get autoremove -y && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
#
## Download and install php-fpm health check script
#RUN curl -o /usr/local/bin/php-fpm-healthcheck \
#    https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck \
#    && chmod +x /usr/local/bin/php-fpm-healthcheck
#
## Copy the initialization script
#COPY ./deploy/entrypoint.sh /usr/local/bin/entrypoint.sh
#RUN chmod +x /usr/local/bin/entrypoint.sh
#
## Copy the initial storage structure
#COPY ./storage /var/www/storage-init
#
## Copy PHP extensions and libraries from the builder stage
#COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
#COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
#COPY --from=builder /usr/local/bin/docker-php-ext-* /usr/local/bin/
#
## Use the recommended production PHP configuration
## -----------------------------------------------------------
## PHP provides development and production configurations.
## Here, we replace the default php.ini with the production
## version to apply settings optimized for performance and
## security in a live environment.
## -----------------------------------------------------------
#RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
#
## Enable PHP-FPM status page by modifying zz-docker.conf with sed
#RUN sed -i '/\[www\]/a pm.status_path = /status' /usr/local/etc/php-fpm.d/zz-docker.conf
## Update the variables_order to include E (for ENV)
##RUN sed -i 's/variables_order = "GPCS"/variables_order = "EGPCS"/' "$PHP_INI_DIR/php.ini"
#
## Copy the application code and dependencies from the build stage
#COPY --from=builder /var/www /var/www
#
## Set working directory
#WORKDIR /var/www
#
## Ensure correct permissions
#RUN chown -R www-data:www-data /var/www
#
## Switch to the non-privileged user to run the application
#USER www-data
#
## Change the default command to run the entrypoint script
#ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
#
## Expose port 9000 and start php-fpm server
#EXPOSE 9000
#CMD ["php-fpm"]
