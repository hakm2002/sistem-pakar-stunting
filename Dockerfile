FROM php:8.2-apache

# Mengatur Document Root
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# BAGIAN INI YANG DIUBAH:
# 1. Mengambil script installer binary (ringan & cepat)
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# 2. Install Dependencies & Extensions
# Perhatikan: Kita TIDAK PERLU lagi install libpng-dev, libjpeg-dev, dll secara manual.
# Script install-php-extensions akan mengurus dependency itu otomatis.
RUN apt-get update && apt-get install -y \
    zip unzip git \
    && install-php-extensions gd pdo_mysql zip \
    && a2enmod rewrite \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Ambil composer dari image resmi
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project
COPY . .

# Konfigurasi Laravel & Permission
RUN git config --global --add safe.directory /var/www/html \
    && cp .env.example .env || true \
    && composer install --no-interaction --optimize-autoloader --no-dev --no-scripts --prefer-dist \
    && rm -f bootstrap/cache/*.php \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80
CMD ["apache2-foreground"]
