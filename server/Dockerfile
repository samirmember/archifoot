FROM composer:2 AS composer-base
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

FROM php:8.4-cli
WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev zlib1g-dev libpng-dev libxml2-dev \
    && docker-php-ext-install intl pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer-base /app/vendor /app/vendor
COPY . /app

ENV APP_ENV=prod
ENV PORT=8080

CMD ["sh", "-c", "php -d variables_order=EGPCS -S 0.0.0.0:${PORT} -t public"]