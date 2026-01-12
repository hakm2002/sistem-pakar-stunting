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

# 5. Konfigurasi Git untuk menghindari error "dubious ownership"
RUN git config --global --add safe.directory /var/www/html

# 6. Siapkan file .env (menggunakan example jika tidak ada)
RUN cp .env.example .env || true

# 7. Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 8. Install Dependencies (Hanya production, mengabaikan paket dev seperti Clockwork)
RUN composer install --no-interaction --optimize-autoloader --no-dev --no-scripts

# 9. MEMBERSIHKAN CACHE & DISCOVER PACKAGES
# Menghapus file cache lama yang mungkin terbawa saat COPY . .
RUN rm -f bootstrap/cache/config.php bootstrap/cache/services.php bootstrap/cache/packages.php \
    && php artisan package:discover --ansi

# 10. Atur Izin Akses Folder (Permission)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 11. Expose port 80 dan jalankan Apache
EXPOSE 80
CMD ["apache2-foreground"]
