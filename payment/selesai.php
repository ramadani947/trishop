<?php
/**
 * payment/selesai.php
 * Halaman tujuan pengalihan (finish redirect) setelah pelanggan
 * menyelesaikan pembayaran di luar popup Snap.
 *
 * Metode berbasis pengalihan seperti DANA dan ShopeePay memindahkan
 * pelanggan ke halaman penyedia dana. Browser meninggalkan halaman
 * checkout, sehingga callback JavaScript onSuccess pada
 * pelanggan/checkout.php tidak pernah dijalankan. Alamat halaman ini
 * didaftarkan sebagai callbacks.finish saat Snap Token dibuat.
 *
 * Halaman ini hanyalah lapis pelengkap. Sumber kebenaran utama adalah
 * webhook payment/callback.php, yang menerima notifikasi langsung dari
 * Midtrans dan biasanya sudah lebih dulu memperbarui status pesanan.
 *
 * Parameter pada URL TIDAK dipercaya, karena mudah dipalsukan siapa pun.
 * Parameter hanya dipakai untuk mengenali pesanan; status yang sebenarnya
 * selalu ditanyakan ulang ke Midtrans memakai server key.
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/midtrans.php';

// Tanpa sesi tidak ada yang bisa ditampilkan maupun diverifikasi
// kepemilikannya. Status pesanan tetap aman karena diperbarui webhook.
if (!is_login()) {
    redirect(BASE_URL . '/auth/login.php');
}

$user   = user_aktif();
$tujuan = BASE_URL . '/pelanggan/pesanan_saya.php';

$orderId = trim($_GET['order_id'] ?? '');

// Midtrans tidak selalu menyertakan order_id pada URL pengalihan; metode
// e-wallet seperti DANA kerap memulangkan pelanggan tanpa parameter apa pun.
// Dalam keadaan itu dipakai pembayaran terakhir milik pelanggan ini.
if ($orderId === '') {
    $stmt = $pdo->prepare(
        "SELECT b.order_id_midtrans
         FROM pembayaran b
         JOIN pesanan p ON p.id = b.pesanan_id
         WHERE p.user_id = ?
         ORDER BY b.id DESC
         LIMIT 1"
    );
    $stmt->execute([$user['id']]);
    $orderId = (string) ($stmt->fetchColumn() ?: '');
}

// Pastikan pembayaran memang milik pelanggan yang sedang login, sekaligus
// mencegah order_id milik orang lain dipakai untuk mengintip status.
$stmt = $pdo->prepare(
    "SELECT p.status
     FROM pembayaran b
     JOIN pesanan p ON p.id = b.pesanan_id
     WHERE b.order_id_midtrans = ? AND p.user_id = ?"
);
$stmt->execute([$orderId, $user['id']]);
$statusPesanan = $stmt->fetchColumn();

if ($statusPesanan === false) {
    // Tidak ada yang bisa diperiksa. Pelanggan tidak perlu ditakut-takuti:
    // webhook Midtrans tetap memperbarui status pesanan tanpa halaman ini.
    redirect($tujuan);
}

try {
    $trx = \Midtrans\Transaction::status($orderId);

    $statusPesanan = midtrans_terapkan_status(
        $pdo,
        $orderId,
        $trx->transaction_status ?? '',
        $trx->fraud_status ?? null,
        $trx->payment_type ?? null,
        $trx->transaction_time ?? null,
        json_encode($trx)
    );

} catch (Exception $ex) {
    // Transaksi bisa saja belum tercatat pada Core API Midtrans. Status yang
    // sudah tersimpan di basis data tetap dipakai, karena webhook umumnya
    // sudah memperbaruinya lebih dulu.
}

if ($statusPesanan === 'diproses') {
    set_flash('success', 'Pembayaran berhasil. Pesanan Anda sedang diproses.');
} elseif ($statusPesanan === 'canceled') {
    set_flash('danger', 'Pembayaran dibatalkan atau kedaluwarsa.');
} elseif ($statusPesanan === 'selesai') {
    // Pesanan ready stock mencapai status ini langsung setelah pembayaran,
    // karena tidak melewati tahap produksi.
    set_flash('success', 'Pembayaran berhasil. Pesanan Anda telah selesai.');
} else {
    set_flash('info', 'Pembayaran belum dikonfirmasi. Status akan diperbarui otomatis begitu Midtrans mengonfirmasinya.');
}

redirect($tujuan);
