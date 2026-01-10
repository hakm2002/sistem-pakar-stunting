# Menggunakan image PHP 8.2 dengan Apache
FROM php:8.2-apache

# 1. Install dependencies sistem yang dibutuhkan Laravel
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

# 2. Aktifkan modul Apache Rewrite untuk routing
RUN a2enmod rewrite

# 3. Atur Document Root Apache ke folder 'public' Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Set working directory
WORKDIR /var/www/html

# 5. Copy seluruh file project ke dalam container
COPY . .

# 6. Install Composer (Untuk manajemen library PHP)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 7. Jalankan install dependencies (tanpa library dev untuk memperkecil ukuran image)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# 8. Berikan izin akses folder storage dan cache (Sangat Penting di Laravel)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 9. Expose port 80
EXPOSE 80

# 10. Jalankan Apache di foreground
CMD ["apache2-foreground"]
