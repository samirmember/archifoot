FROM php:8.5-fpm

# Paquets nécessaires + compilation extensions PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    $PHPIZE_DEPS \
    libicu-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libzip-dev \
    unzip git \
    ripgrep \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install -j"$(nproc)" gd intl pdo_mysql zip \
  && docker-php-ext-enable opcache || true \
  && rm -rf /var/lib/apt/lists/*

# Config opcache
RUN { \
  echo "opcache.enable=1"; \
  echo "opcache.enable_cli=1"; \
  echo "opcache.validate_timestamps=1"; \
  echo "opcache.revalidate_freq=0"; \
  echo "opcache.memory_consumption=256"; \
  echo "opcache.interned_strings_buffer=16"; \
  echo "opcache.max_accelerated_files=20000"; \
} > /usr/local/etc/php/conf.d/99-opcache.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Upload limits
RUN { \
  echo "upload_max_filesize=20M"; \
  echo "post_max_size=20M"; \
  echo "max_file_uploads=20"; \
} > /usr/local/etc/php/conf.d/99-uploads.ini
