FROM php:8.5-fpm

# Paquets nécessaires
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev \
 && rm -rf /var/lib/apt/lists/*

# Extensions Symfony usuelles (sans opcache)
RUN docker-php-ext-install -j"$(nproc)" intl pdo_mysql zip

# Activer opcache si déjà dispo (ne pas échouer si déjà activé)
RUN docker-php-ext-enable opcache || true

# Config opcache (dev : tu peux ajuster)
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
