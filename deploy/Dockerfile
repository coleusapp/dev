FROM composer:2.8 AS composer

COPY deploy/composer.json deploy/composer.lock /app/

RUN \
    cd /app && \
    composer install --no-interaction --no-progress --no-scripts --no-dev --ignore-platform-reqs --optimize-autoloader

FROM node:20-slim AS base
ENV PNPM_HOME="/pnpm"
ENV PATH="$PNPM_HOME:$PATH"
RUN corepack enable
COPY . /app
WORKDIR /app

FROM base AS prod-deps
RUN --mount=type=cache,id=pnpm,target=/pnpm/store pnpm install --prod --frozen-lockfile

FROM dunglas/frankenphp:php8.4-alpine AS builder

ENV SERVER_NAME=coleus.app

RUN install-php-extensions pdo_mysql pcntl

COPY . /app

COPY --from=composer /app /app
COPY --from=prod-deps /app/node_modules /app/node_modules

COPY deploy/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENV APP_ENV=production
ENV APP_DEBUG=false

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

