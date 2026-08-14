FROM php:8.2-apache

# Ekstensi PHP yang dibutuhkan (PDO MySQL untuk koneksi database)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Pastikan hanya SATU MPM (prefork) yang aktif. mod_php mengharuskan
# prefork (non-threaded). Image dasar Debian bisa punya mpm_event
# ikut ter-enable, yang menyebabkan Apache gagal start dengan error
# "More than one MPM loaded". Hapus langsung symlink-nya (lebih pasti
# daripada a2dismod/a2enmod yang kadang no-op diam-diam), lalu pasang
# ulang prefork secara eksplisit.
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load \
          /etc/apache2/mods-enabled/mpm_event.conf \
          /etc/apache2/mods-enabled/mpm_worker.load \
          /etc/apache2/mods-enabled/mpm_worker.conf \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load \
    && ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf \
    && a2enmod rewrite

# Verifikasi konfigurasi Apache valid SAAT BUILD, supaya kalau masih ada
# masalah (misal MPM ganda), build langsung gagal dengan jelas -- tidak
# perlu menunggu deploy jalan dulu baru ketahuan error di log runtime.
RUN apache2ctl configtest

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
