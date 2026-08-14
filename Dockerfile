FROM php:8.2-cli

# Ekstensi PHP yang dibutuhkan (koneksi database)
RUN docker-php-ext-install pdo pdo_mysql mysqli

WORKDIR /var/www/html

# Salin seluruh source code project
COPY . /var/www/html/

# Pastikan folder uploads bisa ditulis
RUN chmod -R 775 /var/www/html/uploads

EXPOSE 8080

# Menggunakan PHP built-in web server, bukan Apache.
# Project ini tidak memakai .htaccess / URL rewriting apa pun,
# jadi Apache sebenarnya tidak diperlukan -- dan ini menghindari
# masalah konfigurasi MPM Apache yang berulang kali gagal di
# lingkungan Railway sebelumnya. Port menyesuaikan variabel $PORT
# yang disuntikkan Railway secara dinamis saat runtime.
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /var/www/html"]
