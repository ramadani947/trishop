FROM php:8.2-apache

# Ekstensi PHP yang dibutuhkan (PDO MySQL untuk koneksi database)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Aktifkan mod_rewrite (jaga-jaga bila dibutuhkan untuk URL rewriting)
RUN a2enmod rewrite

WORKDIR /var/www/html

# Salin seluruh source code project
COPY . /var/www/html/

# Pastikan folder uploads bisa ditulis oleh Apache (www-data)
RUN chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 8080

# Railway menyuntikkan variabel PORT secara dinamis saat runtime (bukan saat
# build), jadi konfigurasi Apache diubah lewat shell command di CMD, bukan
# lewat RUN, agar nilai $PORT yang dipakai adalah yang aktual dari Railway.
CMD sh -c "sed -i \"s/Listen 80/Listen ${PORT:-8080}/\" /etc/apache2/ports.conf \
    && sed -i \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT:-8080}>/\" /etc/apache2/sites-available/000-default.conf \
    && apache2-foreground"
