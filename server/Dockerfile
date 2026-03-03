############################################
# Dockerfile for deploying the Symfony API #
############################################

# Use the official PHP 8.4 base image with Composer pre‑installed
FROM composer:2 AS composer-base

WORKDIR /app

############################################
# 1. Install PHP dependencies without dev  #
############################################
COPY server/composer.json server/composer.lock ./

# Install dependencies into the vendor directory.  The flags
# `--no-dev` and `--optimize-autoloader` produce a lean, production‑ready
# vendor folder.
RUN composer install --no-dev --optimize-autoloader

############################################
# 2. Prepare runtime image                 #
############################################
FROM php:8.4-cli

WORKDIR /app

# Install system packages required by Symfony and its dependencies
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev zlib1g-dev libpng-dev libxml2-dev \
    && docker-php-ext-install intl pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy installed PHP dependencies from the previous stage
COPY --from=composer-base /app/vendor /app/vendor

# Copy the application source code
COPY server /app

############################################
# 3. Expose the Symfony application        #
############################################
# The built‑in PHP server will listen on the port provided by Railway via
# the PORT environment variable.  Fall back to 8080 if not set.
ENV APP_ENV=prod
ENV PORT=8080

CMD ["sh", "-c", "php -d variables_order=EGPCS -S 0.0.0.0:${PORT} -t public"]
