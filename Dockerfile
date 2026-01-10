FROM php:8.2-apache

# 1. Install dependencies sistem
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd zip

# 2. Aktifkan Apache Rewrite Module
RUN a2enmod rewrite

# 3. Konfigurasi Apache Document Root ke folder public Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# 4. Copy seluruh file project
COPY . .

# 5. Siapkan file .env (menggunakan example jika tidak ada)
RUN cp .env.example .env || true

# 6. Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 7. Install Dependencies (Tanpa dev-dependencies dan tanpa menjalankan script artisan otomatis)
RUN composer install --no-interaction --optimize-autoloader --no-dev --no-scripts

# 8. MEMBERSIHKAN CACHE & DISCOVER PACKAGES
# Ini langkah krusial untuk menghapus jejak Clockwork/dev-tools dari cache yang terikut ter-copy
RUN rm -f bootstrap/cache/config.php bootstrap/cache/services.php bootstrap/cache/packages.php \
    && php artisan package:discover --ansi

# 9. Atur Izin Akses Folder (Permission)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 10. Expose port 80 dan jalankan Apache
EXPOSE 80
CMD ["apache2-foreground"]
