FROM php:8.2-apache

# Ekstensi PHP yang dibutuhkan (PDO MySQL untuk koneksi database)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Pastikan hanya satu MPM (prefork) yang aktif -- mod_php mengharuskan
# prefork, dan mengaktifkan modul lain di beberapa image dasar bisa
# memicu mpm_event ikut aktif bersamaan, menyebabkan Apache gagal start
# dengan error "More than one MPM loaded".
RUN a2dismod mpm_event mpm_worker 2>/dev/null; \
    a2enmod mpm_prefork rewrite

WORKDIR /var/www/html

# Salin seluruh source code project
COPY . /var/www/html/

# Pastikan folder uploads bisa ditulis oleh Apache (www-data)
RUN chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

# Script entrypoint yang menyesuaikan port Apache dengan variabel $PORT
# dari Railway saat container dijalankan (bukan saat build).
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8080

CMD ["/usr/local/bin/docker-entrypoint.sh"]
