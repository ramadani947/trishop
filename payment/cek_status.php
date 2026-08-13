<?php
/**
 * payment/cek_status.php
 * Pengecekan status transaksi secara aktif ke Midtrans, dipanggil dari
 * browser pelanggan setelah popup Snap selesai (onSuccess/onPending).
 *
 * Webhook payment/callback.php tidak dapat menjangkau server yang
 * berjalan di localhost/jaringan lokal, sehingga status pesanan tidak
 * pernah diperbarui otomatis meski pembayaran sudah sukses di Midtrans.
 * Endpoint ini melengkapi webhook dengan query langsung memakai
 * Transaction::status(), agar status tetap ter-update walau notifikasi
 * server-to-server tidak sampai.
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/midtrans.php';

header('Content-Type: application/json');

if (!is_login()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'pesan' => 'Silakan login terlebih dahulu.']);
    exit;
}

$user      = user_aktif();
$pesananId = (int) ($_GET['pesanan'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT p.status AS status_pesanan, b.order_id_midtrans
     FROM pesanan p
     JOIN pembayaran b ON b.pesanan_id = p.id
     WHERE p.id = ? AND p.user_id = ?"
);
$stmt->execute([$pesananId, $user['id']]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'pesan' => 'Pesanan tidak ditemukan.']);
    exit;
}

// Sudah final sebelumnya, tidak perlu tanya Midtrans lagi.
if ($row['status_pesanan'] !== 'menunggu_pembayaran') {
    echo json_encode(['status' => 'ok', 'status_pesanan' => $row['status_pesanan']]);
    exit;
}

try {
    $trx = \Midtrans\Transaction::status($row['order_id_midtrans']);

    $statusPesananBaru = midtrans_terapkan_status(
        $pdo,
        $row['order_id_midtrans'],
        $trx->transaction_status ?? '',
        $trx->fraud_status ?? null,
        $trx->payment_type ?? null,
        $trx->transaction_time ?? null,
        json_encode($trx)
    );

    echo json_encode([
        'status'         => 'ok',
        'status_pesanan' => $statusPesananBaru ?? $row['status_pesanan'],
    ]);

} catch (Exception $ex) {
    // Transaksi mungkin belum tercatat di Midtrans (mis. baru saja dibuat).
    // Bukan kegagalan fatal, biarkan status apa adanya.
    echo json_encode(['status' => 'ok', 'status_pesanan' => $row['status_pesanan']]);
}
