<?php
/**
 * includes/functions.php
 * Titik muat seluruh modul fungsi bantu, supaya includes/init.php cukup
 * memuat satu berkas seperti sebelumnya.
 *
 * Isi fungsinya sendiri sudah dipindahkan ke berkas terpisah per tanggung
 * jawab, sehingga masing-masing lebih mudah ditelusuri dan tidak lagi
 * tercampur dalam satu berkas besar:
 *
 *   format.php           - format angka, validasi, anti-XSS, redirect,
 *                           alamat aset, pesan flash, kontak toko
 *   pesanan_helpers.php   - keranjang, perhitungan harga, status pesanan
 *   upload.php            - validasi dan penyimpanan berkas unggahan
 *   admin_data.php        - data master (produk/model/bahan/desain) dan
 *                           gambar bagian keunggulan beranda
 *   ui_helpers.php        - potongan HTML admin yang berulang
 *
 * Urutan di bawah mengikuti urutan ketergantungan: admin_data.php memakai
 * fungsi dari upload.php dan format.php, jadi keduanya dimuat lebih dulu.
 */

require_once __DIR__ . '/format.php';
require_once __DIR__ . '/pesanan_helpers.php';
require_once __DIR__ . '/upload.php';
require_once __DIR__ . '/admin_data.php';
require_once __DIR__ . '/ui_helpers.php';
