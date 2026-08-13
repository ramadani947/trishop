<?php
/**
 * includes/midtrans.php
 * Konfigurasi integrasi payment gateway Midtrans menggunakan SDK resmi
 * midtrans-php (https://github.com/Midtrans/midtrans-php), diletakkan
 * pada lib/midtrans-php/.
 *
 * CATATAN TEKNIS:
 * File Midtrans.php pada SDK memuat berkas lain dengan cara
 * `require_once 'Midtrans/Config.php';` tanpa awalan __DIR__. Baris ini
 * hanya berhasil ditemukan bila direktori SDK sudah ada pada include_path
 * PHP. Oleh karena itu direktori lib/midtrans-php/ ditambahkan ke
 * include_path terlebih dahulu, sebelum SDK dimuat.
 */

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../lib/midtrans-php');
require_once __DIR__ . '/../lib/midtrans-php/Midtrans.php';

use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;

// --- Kunci API ------------------------------------------------------------
// Ganti dengan kunci milik akun Midtrans Anda.
// Ambil dari dashboard Midtrans, menu Settings > Access Keys,
// pastikan Environment yang aktif adalah Sandbox.
define('MIDTRANS_SERVER_KEY',    'Mid-server-FTGkpIh_ue02EYmJH7WY_Acl');
define('MIDTRANS_CLIENT_KEY',    'Mid-client-KDKL2G1Pl6fqXAGB');
define('MIDTRANS_IS_PRODUCTION', false);

// --- Endpoint Snap.js untuk sisi frontend ----------------------------------
define('MIDTRANS_SNAP_JS', MIDTRANS_IS_PRODUCTION
    ? 'https://app.midtrans.com/snap/snap.js'
    : 'https://app.sandbox.midtrans.com/snap/snap.js');

// --- Alamat webhook (Payment Notification URL) ------------------------------
// Midtrans mengirim notifikasi status pembayaran ke alamat ini secara
// server-to-server. Selama pengembangan di localhost, alamat lokal tidak
// terjangkau dari internet sehingga dipakai terowongan ngrok.
//
// Bila dikosongkan, Midtrans memakai alamat yang terdaftar pada dashboard
// (menu Settings > Configuration). Alamat di sini menimpanya khusus untuk
// transaksi yang dibuat sistem ini melalui header X-Override-Notification,
// sehingga dashboard tidak perlu diubah.
//
// Akun ngrok ini memakai domain statis, jadi alamatnya tetap sama setiap
// kali ngrok dinyalakan ulang dan baris ini tidak perlu diubah lagi.
// Kosongkan hanya bila ingin menonaktifkan webhook.
define('MIDTRANS_NOTIF_URL', 'https://unfailing-stamp-humongous.ngrok-free.dev/trishop/payment/callback.php');

// --- Konfigurasi SDK --------------------------------------------------------
MidtransConfig::$serverKey    = MIDTRANS_SERVER_KEY;
MidtransConfig::$clientKey    = MIDTRANS_CLIENT_KEY;
MidtransConfig::$isProduction = MIDTRANS_IS_PRODUCTION;

if (MIDTRANS_NOTIF_URL !== '') {
    MidtransConfig::$overrideNotifUrl = MIDTRANS_NOTIF_URL;
}
// Sanitasi bawaan SDK: memotong panjang field dan membuang karakter
// yang tidak diizinkan Midtrans pada item_details dan customer_details.
MidtransConfig::$isSanitized  = true;

/**
 * Meminta Snap Token ke Midtrans melalui SDK resmi.
 *
 * Bentuk kembalian disamakan dengan implementasi cURL manual sebelumnya
 * agar pelanggan/checkout.php tidak perlu diubah sama sekali.
 *
 * @param  array $payload Data transaksi sesuai format Snap API.
 * @return array ['sukses' => bool, 'token' => string|null, 'pesan' => string]
 */
function midtrans_buat_snap_token(array $payload)
{
    try {
        $response = Snap::createTransaction($payload);

        if (!empty($response->token)) {
            return ['sukses' => true, 'token' => $response->token, 'pesan' => ''];
        }

        return ['sukses' => false, 'token' => null, 'pesan' => 'Midtrans tidak mengembalikan token.'];

    } catch (Exception $ex) {
        // Pesan dari SDK resmi menyertakan isi respons API Midtrans,
        // sehingga lebih rinci dibanding versi cURL manual sebelumnya.
        return ['sukses' => false, 'token' => null, 'pesan' => $ex->getMessage()];
    }
}

/**
 * Menutup transaksi di sisi Midtrans ketika pesanan dibatalkan pelanggan.
 *
 * Tanpa langkah ini, kode QR atau tautan pembayaran yang terlanjur terbit
 * masih bisa dibayar meskipun pesanannya sudah dibatalkan di sistem kita.
 *
 * @return array ['sukses' => bool, 'sudah_dibayar' => bool, 'pesan' => string]
 */
function midtrans_batalkan_transaksi($orderId)
{
    if ($orderId === '' || $orderId === null) {
        return ['sukses' => true, 'sudah_dibayar' => false, 'pesan' => 'Tidak ada transaksi yang perlu ditutup.'];
    }

    // Status ditanyakan lebih dulu. Tanpa langkah ini, pesanan yang baru saja
    // dibayar tetapi notifikasinya belum tiba bisa ikut terbatalkan.
    try {
        $trx    = \Midtrans\Transaction::status($orderId);
        $status = $trx->transaction_status ?? '';

    } catch (Exception $ex) {
        // 404 berarti Snap Token terbit tetapi metode pembayaran belum pernah
        // dipilih, sehingga tidak ada transaksi yang perlu ditutup.
        if (strpos($ex->getMessage(), '404') !== false) {
            return ['sukses' => true, 'sudah_dibayar' => false, 'pesan' => 'Belum ada transaksi di Midtrans.'];
        }

        return ['sukses' => false, 'sudah_dibayar' => false, 'pesan' => $ex->getMessage()];
    }

    if ($status === 'settlement' || $status === 'capture') {
        return [
            'sukses'        => false,
            'sudah_dibayar' => true,
            'pesan'         => 'Pembayaran untuk pesanan ini sudah berhasil.',
        ];
    }

    if ($status === 'expire' || $status === 'cancel' || $status === 'deny') {
        return ['sukses' => true, 'sudah_dibayar' => false, 'pesan' => 'Transaksi sudah tertutup sebelumnya.'];
    }

    // Sisanya berstatus pending. Midtrans membedakan dua endpoint:
    //   - cancel : untuk transaksi kartu yang belum diselesaikan
    //   - expire : untuk yang menunggu pembayaran seperti QRIS dan VA
    try {
        \Midtrans\Transaction::cancel($orderId);
        return ['sukses' => true, 'sudah_dibayar' => false, 'pesan' => 'Transaksi dibatalkan di Midtrans.'];

    } catch (Exception $ex) {
        // Tidak bisa di-cancel; ditutup lewat expire.
    }

    try {
        \Midtrans\Transaction::expire($orderId);
        return ['sukses' => true, 'sudah_dibayar' => false, 'pesan' => 'Transaksi ditutup di Midtrans.'];

    } catch (Exception $ex) {
        return ['sukses' => false, 'sudah_dibayar' => false, 'pesan' => $ex->getMessage()];
    }
}

/**
 * Memverifikasi keaslian notifikasi webhook dari Midtrans.
 * Signature dihitung dengan SHA-512 atas gabungan order_id, status_code,
 * gross_amount, dan server key. Fungsi ini independen dari SDK karena
 * payment/callback.php membaca payload mentah secara langsung.
 */
function midtrans_verifikasi_signature($orderId, $statusCode, $grossAmount, $signatureKey)
{
    $hitung = hash('sha512', $orderId . $statusCode . $grossAmount . MIDTRANS_SERVER_KEY);

    return hash_equals($hitung, (string) $signatureKey);
}

/**
 * Menerjemahkan transaction_status Midtrans menjadi status pesanan internal.
 * Mengembalikan null bila status belum final sehingga pesanan tidak diubah.
 *
 * $jenisPesanan menentukan status tujuan ketika pembayaran berhasil, lihat
 * status_lunas_pesanan().
 */
function midtrans_status_ke_pesanan($transactionStatus, $fraudStatus = null, $jenisPesanan = null)
{
    $statusLunas = status_lunas_pesanan($jenisPesanan);

    switch ($transactionStatus) {
        case 'capture':
            // Transaksi kartu kredit baru sah setelah lolos pemeriksaan fraud.
            return $fraudStatus === 'accept' ? $statusLunas : null;

        case 'settlement':
            return $statusLunas;

        case 'expire':
        case 'cancel':
        case 'deny':
            return 'canceled';

        case 'pending':
        default:
            return null;
    }
}

/**
 * Menerapkan status transaksi Midtrans ke tabel pembayaran & pesanan.
 * Dipakai bersama oleh payment/callback.php (webhook) dan
 * payment/cek_status.php (pengecekan aktif dari sisi klien), karena
 * webhook tidak dapat menjangkau server yang berjalan di localhost.
 *
 * @return string|null Status pesanan terkini setelah diproses, atau null bila order_id tidak ditemukan.
 */
function midtrans_terapkan_status(PDO $pdo, $orderId, $transactionStatus, $fraudStatus, $metode, $waktuBayar, $rawPayload)
{
    $stmt = $pdo->prepare(
        "SELECT b.id AS pembayaran_id, b.pesanan_id, p.status AS status_pesanan, p.jenis_pesanan
         FROM pembayaran b
         JOIN pesanan p ON p.id = b.pesanan_id
         WHERE b.order_id_midtrans = ?"
    );
    $stmt->execute([$orderId]);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    $statusPesananBaru = midtrans_status_ke_pesanan($transactionStatus, $fraudStatus, $row['jenis_pesanan']);

    $pdo->beginTransaction();
    try {
        // Catat status pembayaran beserta payload asli untuk keperluan audit.
        $pdo->prepare(
            "UPDATE pembayaran
             SET status_pembayaran = ?, metode_pembayaran = ?, waktu_bayar = ?, raw_notification = ?
             WHERE id = ?"
        )->execute([$transactionStatus, $metode, $waktuBayar, $rawPayload, $row['pembayaran_id']]);

        // Status pesanan hanya diperbarui bila status transaksi sudah final,
        // dan pesanan belum pernah diproses sebelumnya.
        if ($statusPesananBaru !== null && $row['status_pesanan'] === 'menunggu_pembayaran') {

            $pdo->prepare("UPDATE pesanan SET status = ? WHERE id = ?")
                ->execute([$statusPesananBaru, $row['pesanan_id']]);

            // Stok produk ready stock dikurangi setelah pembayaran dipastikan
            // berhasil. Status tujuannya 'selesai', bukan 'diproses', sehingga
            // pemeriksaannya memakai pesanan_sudah_dibayar().
            if (pesanan_sudah_dibayar($statusPesananBaru) && $row['jenis_pesanan'] === 'ready_stock') {

                $detail = $pdo->prepare("SELECT produk_id, qty FROM detail_pesanan WHERE pesanan_id = ?");
                $detail->execute([$row['pesanan_id']]);

                $kurangi = $pdo->prepare(
                    "UPDATE produk SET stok = GREATEST(stok - ?, 0) WHERE id = ?"
                );

                foreach ($detail->fetchAll() as $item) {
                    $kurangi->execute([$item['qty'], $item['produk_id']]);
                }
            }

            $row['status_pesanan'] = $statusPesananBaru;
        }

        $pdo->commit();
    } catch (PDOException $ex) {
        $pdo->rollBack();
        throw $ex;
    }

    return $row['status_pesanan'];
}
