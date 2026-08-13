<?php
/**
 * pelanggan/tambah_keranjang.php
 * Endpoint bersama untuk menambahkan produk ready stock ke keranjang.
 *
 * Dipakai oleh dua tempat:
 *   - ready_stock.php    : tombol pintasan pada setiap kartu produk
 *   - detail_produk.php  : tombol "Tambah ke Keranjang" beserta jumlahnya
 *
 * Menyediakan dua bentuk balasan:
 *   - format=json : untuk permintaan JavaScript, tanpa memuat ulang halaman
 *   - selain itu  : alur biasa dengan pesan flash lalu pengalihan, sehingga
 *                   tetap berfungsi ketika JavaScript dimatikan.
 */

require_once __DIR__ . '/../includes/init.php';

$jsonMode = ($_POST['format'] ?? '') === 'json';

if ($jsonMode) {
    header('Content-Type: application/json');
}

/** Membalas permintaan sesuai bentuk yang diminta, lalu menghentikan skrip. */
function balas($jsonMode, $sukses, $pesan, $tujuan, $tambahan = [])
{
    if ($jsonMode) {
        echo json_encode(array_merge([
            'sukses' => $sukses,
            'pesan'  => $pesan,
        ], $tambahan));
        exit;
    }

    set_flash($sukses ? 'success' : 'danger', $pesan);
    redirect($tujuan);
}

$produkId = (int) ($_POST['produk_id'] ?? 0);
$qty      = (int) ($_POST['qty'] ?? 1);

// Halaman asal ditentukan lewat penanda tetap, bukan URL bebas, agar
// tidak bisa dipakai mengalihkan pengunjung ke situs luar.
$tujuan = ($_POST['kembali'] ?? '') === 'katalog'
    ? BASE_URL . '/pelanggan/ready_stock.php'
    : BASE_URL . '/pelanggan/detail_produk.php?id=' . $produkId;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/pelanggan/ready_stock.php');
}

// --- Wajib login --------------------------------------------------------

if (!is_login()) {
    if ($jsonMode) {
        // Penanda khusus supaya JavaScript bisa mengarahkan ke halaman login
        // alih-alih menampilkan pesan galat yang membingungkan.
        echo json_encode([
            'sukses'      => false,
            'perlu_login' => true,
            'pesan'       => 'Silakan login terlebih dahulu untuk berbelanja.',
            'url_login'   => BASE_URL . '/auth/login.php',
        ]);
        exit;
    }
    set_flash('warning', 'Silakan login terlebih dahulu untuk berbelanja.');
    redirect(BASE_URL . '/auth/login.php');
}

if (is_admin()) {
    redirect(BASE_URL . '/admin/dashboard.php');
}

$user = user_aktif();

// --- Validasi produk ----------------------------------------------------

$stmt = $pdo->prepare("SELECT id, nama_produk, stok FROM produk WHERE id = ? AND status = 'aktif'");
$stmt->execute([$produkId]);
$produk = $stmt->fetch();

if (!$produk) {
    balas($jsonMode, false, 'Produk tidak ditemukan atau sudah tidak tersedia.', BASE_URL . '/pelanggan/ready_stock.php');
}

if ($qty < 1) {
    balas($jsonMode, false, 'Jumlah pembelian minimal 1.', $tujuan);
}

if ($produk['stok'] < 1) {
    balas($jsonMode, false, 'Stok ' . $produk['nama_produk'] . ' sedang habis.', $tujuan);
}

if ($qty > $produk['stok']) {
    balas($jsonMode, false, 'Jumlah melebihi stok yang tersedia (' . $produk['stok'] . ').', $tujuan);
}

// --- Simpan ke keranjang ------------------------------------------------

// Bila produk sudah ada di keranjang, jumlahnya ditambahkan.
$cek = $pdo->prepare("SELECT id, qty FROM keranjang WHERE user_id = ? AND produk_id = ?");
$cek->execute([$user['id'], $produk['id']]);
$itemLama = $cek->fetch();

if ($itemLama) {
    $qtyBaru = $itemLama['qty'] + $qty;

    if ($qtyBaru > $produk['stok']) {
        balas(
            $jsonMode,
            false,
            'Total jumlah di keranjang melebihi stok yang tersedia (' . $produk['stok'] . ').',
            $tujuan
        );
    }

    $pdo->prepare("UPDATE keranjang SET qty = ? WHERE id = ?")
        ->execute([$qtyBaru, $itemLama['id']]);
} else {
    $pdo->prepare("INSERT INTO keranjang (user_id, produk_id, qty) VALUES (?, ?, ?)")
        ->execute([$user['id'], $produk['id'], $qty]);
}

// Jumlah baris keranjang, dipakai memperbarui penanda pada tombol Keranjang.
$hitung = $pdo->prepare("SELECT COUNT(*) FROM keranjang WHERE user_id = ?");
$hitung->execute([$user['id']]);
$jumlahItem = (int) $hitung->fetchColumn();

balas(
    $jsonMode,
    true,
    $produk['nama_produk'] . ' ditambahkan ke keranjang.',
    $tujuan,
    ['jumlah_item' => $jumlahItem]
);
