FROM php:8.4-cli AS builder
WORKDIR /app

# Dépendances + extensions (gd, intl, zip, pdo_mysql)
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip \
        libicu-dev \
        libzip-dev \
        zlib1g-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd intl pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 1) Installer vendor SANS scripts (bin/console pas encore copié)
COPY composer.json composer.lock ./
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# 2) Copier le code
COPY . /app

# (Optionnel) ré-optimiser l’autoloader après copie
RUN composer dump-autoload -o --no-interaction


FROM php:8.4-cli
WORKDIR /app

# Extensions runtime (mêmes qu’en builder)
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev \
        libzip-dev \
        zlib1g-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd intl pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY . /app
COPY --from=builder /app/vendor /app/vendor

# Installer les assets (API Platform, etc.) dans /public
RUN php bin/console assets:install --no-interaction || true
RUN php bin/console cache:clear --env=prod --no-debug || true

ENV APP_ENV=prod
ENV PORT=8080


CMD ["sh", "-c", "php bin/console doctrine:migrations:migrate --no-interaction || true; php -d variables_order=EGPCS -S 0.0.0.0:${PORT} -t public public/router.php"]
