############################################
# Builder: PHP 8.4 + extensions + Composer #
############################################
FROM php:8.4-cli AS builder

WORKDIR /app

# Dépendances système + extensions PHP nécessaires (dont gd)
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

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Installer les dépendances PHP (maintenant gd est dispo => OK)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

############################
# Runtime: PHP 8.4         #
############################
FROM php:8.4-cli

WORKDIR /app

# Même set d'extensions en runtime (sinon l'app plantera à l'exécution)
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

# Code + vendor
COPY . /app
COPY --from=builder /app/vendor /app/vendor

ENV APP_ENV=prod
ENV PORT=8080

CMD ["sh", "-c", "php -d variables_order=EGPCS -S 0.0.0.0:${PORT} -t public"]