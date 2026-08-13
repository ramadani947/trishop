<?php
/**
 * pelanggan/ready_stock.php
 * Katalog produk ready stock: tas yang sudah tersedia di gudang dan
 * siap dikirim tanpa menunggu proses produksi.
 *
 * Sebelumnya bagian ini menyatu dengan index.php (#koleksi). Dipisah
 * menjadi halaman sendiri supaya Beranda bisa fokus sebagai halaman
 * pengenalan toko, sementara katalog punya alamat dan tab menunya sendiri.
 */

require_once __DIR__ . '/../includes/init.php';

// Admin diarahkan ke dashboard, tidak perlu melihat katalog pelanggan.
if (is_admin()) {
    redirect(BASE_URL . '/admin/dashboard.php');
}

$produk = $pdo->query(
    "SELECT id, nama_produk, harga, stok, gambar
     FROM produk
     WHERE status = 'aktif'
     ORDER BY created_at DESC"
)->fetchAll();

$judulHalaman = 'Ready Stock';
$menuAktif    = 'ready_stock';

require_once __DIR__ . '/../includes/header.php';
?>

<?php require __DIR__ . '/../includes/flash.php'; ?>

    <h1 class="judul-halaman">Ready Stock<span class="sub">Tas yang sudah tersedia di gudang dan siap dikirim, tanpa menunggu proses produksi.</span></h1>

<?php if (empty($produk)): ?>

    <div class="kosong">
        <div class="ikon">&#128717;</div>
        <p class="text-muted mb-0">Belum ada produk yang tersedia saat ini.</p>
    </div>

<?php else: ?>

    <div class="row g-4 g-lg-5">
        <?php $aksiKartu = true; ?>
        <?php foreach ($produk as $p): ?>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <?php require __DIR__ . '/../includes/kartu_produk.php'; ?>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
