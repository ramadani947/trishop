<?php
/**
 * includes/format.php
 * Fungsi bantu tampilan dan navigasi umum: format angka, validasi,
 * anti-XSS, redirect, alamat aset, pesan flash, dan kontak toko.
 * Dimuat otomatis oleh includes/init.php.
 */

// ---------------------------------------------------------------------------
// Tampilan dan navigasi
// ---------------------------------------------------------------------------

/** Format angka menjadi format Rupiah. */
function rupiah($angka)
{
    return 'Rp' . number_format((float) $angka, 0, ',', '.');
}

/** Format email valid? Dipakai bersama oleh register, profil pelanggan,
 *  dan pengaturan toko supaya aturan validasinya tidak diketik ulang. */
function email_valid($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** Mencegah XSS pada output ke halaman. */
function e($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

/** Redirect ke URL tertentu lalu hentikan eksekusi. */
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

/**
 * Alamat berkas aset lokal beserta penanda versinya.
 *
 * Penanda diambil dari waktu ubah berkasnya. Peramban tetap memakai salinan
 * simpanannya selama berkas tidak berubah, lalu mengambil yang baru begitu
 * berkasnya benar-benar diubah.
 *
 * Ini bukan hiasan: ketika tata letak sidebar dipindahkan dari <style>
 * sisipan ke assets/css/style.css, peramban yang masih memegang style.css
 * lama menampilkan panel admin tanpa tata letak sama sekali, dan satu-satunya
 * jalan keluar adalah menekan Ctrl+F5. Dengan penanda ini hal itu tidak
 * mungkin terulang untuk perubahan CSS maupun JS berikutnya.
 */
function aset($path)
{
    $path   = ltrim($path, '/');
    $berkas = __DIR__ . '/../' . $path;
    $versi  = is_file($berkas) ? filemtime($berkas) : null;

    return BASE_URL . '/' . $path . ($versi ? '?v=' . $versi : '');
}

/** Simpan pesan flash untuk ditampilkan pada halaman berikutnya. */
function set_flash($tipe, $pesan)
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

/** Ambil sekaligus hapus pesan flash. */
function get_flash()
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// ---------------------------------------------------------------------------
// Kontak toko
// ---------------------------------------------------------------------------

/** Alamat profil Instagram toko. */
function tautan_instagram()
{
    return 'https://www.instagram.com/' . IG_USERNAME . '/';
}

/**
 * Mengubah nomor telepon Indonesia menjadi format yang diterima wa.me,
 * yaitu kode negara tanpa tanda plus: 081234567890 menjadi 6281234567890.
 *
 * Mengembalikan null bila nomornya kosong atau terlalu pendek untuk
 * dianggap nomor yang sah, sehingga pemanggil dapat menyembunyikan tautan.
 */
function nomor_whatsapp($noTelp)
{
    $angka = preg_replace('/\D+/', '', (string) $noTelp);

    if ($angka === '') {
        return null;
    }

    if (strpos($angka, '0') === 0) {
        $angka = '62' . substr($angka, 1);
    } elseif (strpos($angka, '62') !== 0) {
        // Nomor lokal tanpa awalan 0, misalnya 81234567890.
        $angka = '62' . $angka;
    }

    // 62 + minimal 9 digit. Lebih pendek dari itu pasti bukan nomor ponsel.
    return strlen($angka) >= 11 ? $angka : null;
}

/**
 * Alamat chat WhatsApp toko, lengkap dengan pesan pembuka opsional.
 * Mengembalikan null bila nomor toko belum diisi pada Pengaturan Toko.
 */
function tautan_whatsapp($noTelp, $pesan = '')
{
    $nomor = nomor_whatsapp($noTelp);

    if ($nomor === null) {
        return null;
    }

    return 'https://wa.me/' . $nomor
        . ($pesan !== '' ? '?text=' . rawurlencode($pesan) : '');
}
