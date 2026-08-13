<?php
/**
 * payment/callback.php
 * Endpoint webhook penerima notifikasi status transaksi dari Midtrans.
 *
 * Sesuai Activity Diagram Proses Pembayaran (Gambar 3.5):
 *   pembayaran berhasil -> status pesanan menjadi Diproses
 *   pembayaran expired  -> status pesanan menjadi Canceled
 *
 * URL endpoint ini didaftarkan pada dashboard Midtrans,
 * menu Settings > Configuration > Payment Notification URL.
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/midtrans.php';

header('Content-Type: application/json');

// Midtrans mengirim notifikasi dalam bentuk JSON melalui metode POST.
$raw    = file_get_contents('php://input');
$notif  = json_decode($raw, true);

if (!is_array($notif) || empty($notif['order_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'pesan' => 'Payload tidak valid.']);
    exit;
}

$orderId     = $notif['order_id'];
$statusCode  = $notif['status_code']        ?? '';
$grossAmount = $notif['gross_amount']       ?? '';
$signature   = $notif['signature_key']      ?? '';
$trxStatus   = $notif['transaction_status'] ?? '';
$fraudStatus = $notif['fraud_status']       ?? null;
$metode      = $notif['payment_type']       ?? null;
$waktuBayar  = $notif['transaction_time']   ?? null;

// Verifikasi keaslian notifikasi. Tanpa langkah ini siapa pun dapat
// mengirim permintaan palsu untuk mengubah status pesanan.
if (!midtrans_verifikasi_signature($orderId, $statusCode, $grossAmount, $signature)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'pesan' => 'Signature tidak valid.']);
    exit;
}

try {
    // Mengembalikan null bila order_id tidak dikenali sistem ini.
    $status = midtrans_terapkan_status($pdo, $orderId, $trxStatus, $fraudStatus, $metode, $waktuBayar, $raw);

    if ($status === null) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'pesan' => 'Pesanan tidak ditemukan.']);
        exit;
    }

    echo json_encode(['status' => 'ok']);

} catch (PDOException $ex) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'pesan' => 'Gagal memperbarui data.']);
}
