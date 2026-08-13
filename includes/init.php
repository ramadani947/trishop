<?php
/**
 * includes/init.php
 * Titik masuk tunggal setiap halaman: konfigurasi, koneksi database (PDO),
 * fungsi bantu, dan pengelolaan sesi.
 *
 * Setiap halaman cukup memuat berkas ini:
 *   require_once __DIR__ . '/../includes/init.php';
 *
 * Halaman yang berurusan dengan pembayaran memuat includes/midtrans.php
 * sebagai tambahan.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'tri_shop_souvenir');
define('DB_USER', 'root');
define('DB_PASS', '');

define('BASE_URL', 'http://localhost/trishop');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

/** Batas minimum pemesanan custom order sesuai batasan masalah. */
define('MIN_QTY_CUSTOM', 20);

/** Nama pengguna Instagram toko, tanpa tanda @. */
define('IG_USERNAME', 'tri.shop_souvenir');

date_default_timezone_set('Asia/Jakarta');

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die('Koneksi database gagal: ' . $e->getMessage());
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
