<?php
/**
 * pelanggan/detail_produk.php
 * Menampilkan informasi lengkap satu produk ready stock
 * dan menyediakan aksi menambahkan produk ke keranjang belanja.
 */

require_once __DIR__ . '/../includes/init.php';

if (is_admin()) {
    redirect(BASE_URL . '/admin/dashboard.php');
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM produk WHERE id = ? AND status = 'aktif'");
$stmt->execute([$id]);
$produk = $stmt->fetch();

if (!$produk) {
    // Dikembalikan ke katalog, bukan ke beranda: pengunjung sedang mencari
    // produk, jadi daftar produk lain adalah tempat yang paling berguna.
    set_flash('danger', 'Produk tidak ditemukan atau sudah tidak tersedia.');
    redirect(BASE_URL . '/pelanggan/ready_stock.php');
}

// Penambahan ke keranjang ditangani pelanggan/tambah_keranjang.php,
// yang dipakai bersama dengan tombol pintasan pada katalog ready stock.
// Setelah berhasil, pelanggan TIDAK lagi dialihkan ke halaman keranjang;
// mereka tetap di halaman produk dan menerima umpan balik berupa animasi.

// Produk lain sebagai penutup halaman.
$stmt = $pdo->prepare(
    "SELECT id, nama_produk, harga, stok, gambar
     FROM produk
     WHERE status = 'aktif' AND id <> ?
     ORDER BY created_at DESC
     LIMIT 4"
);
$stmt->execute([$id]);
$lainnya = $stmt->fetchAll();

$judulHalaman = $produk['nama_produk'];
$menuAktif    = 'ready_stock';

require_once __DIR__ . '/../includes/header.php';
?>

<a href="<?= BASE_URL ?>/pelanggan/ready_stock.php" class="tautan-kembali">&larr; Kembali ke katalog</a>

<div class="row g-4 g-lg-5 mt-1">

    <div class="col-lg-6">
        <div class="bingkai-detail">
            <?php if ($produk['gambar']): ?>
                <img src="<?= BASE_URL ?>/uploads/produk/<?= e($produk['gambar']) ?>"
                     alt="<?= e($produk['nama_produk']) ?>">
            <?php else: ?>
                <span class="tanpa-gambar">Tanpa gambar</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="ps-lg-4">

            <span class="kategori-produk">Ready Stock</span>

            <h1 class="judul-produk"><?= e($produk['nama_produk']) ?></h1>

            <div class="harga-detail"><?= rupiah($produk['harga']) ?></div>

            <div class="mb-4">
                <?php if ($produk['stok'] > 0): ?>
                    <span class="chip-stok">Stok tersedia: <?= (int) $produk['stok'] ?> pcs</span>
                <?php else: ?>
                    <span class="chip-stok habis">Stok habis</span>
                <?php endif; ?>
            </div>

            <?php if ($produk['deskripsi']): ?>
                <h2 class="judul-kaki">Deskripsi</h2>
                <p class="teks-deskripsi"><?= e($produk['deskripsi']) ?></p>
            <?php endif; ?>

            <?php if ($produk['stok'] > 0): ?>
                <form method="post" class="row g-2 align-items-end mt-4" style="max-width: 380px;"
                      action="<?= BASE_URL ?>/pelanggan/tambah_keranjang.php"
                      data-tambah-keranjang>
                    <input type="hidden" name="produk_id" value="<?= (int) $produk['id'] ?>">
                    <input type="hidden" name="kembali" value="detail">
                    <div class="col-5">
                        <label for="qty" class="form-label">Jumlah</label>
                        <input type="number" class="form-control" id="qty" name="qty"
                               value="1" min="1" max="<?= (int) $produk['stok'] ?>" required>
                    </div>
                    <div class="col-7">
                        <button type="submit" class="btn btn-primary w-100">Tambah ke Keranjang</button>
                    </div>
                </form>
            <?php else: ?>
                <button class="btn btn-secondary mt-4" disabled>Stok Habis</button>
            <?php endif; ?>

            <div class="catatan-produk">
                Butuh jumlah banyak dengan desain sendiri?
                <a href="<?= BASE_URL ?>/pelanggan/custom_order.php">Buat custom order</a>
                mulai <?= MIN_QTY_CUSTOM ?> pcs.
            </div>

        </div>
    </div>

</div>

<?php if ($lainnya): ?>
    <section class="mt-5 pt-5">

        <div class="judul-bagian">
            <span class="rintik">Ready Stock</span>
            <h2>Produk Lainnya</h2>
        </div>

        <div class="row g-4 g-lg-5">
            <?php $aksiKartu = false; ?>
            <?php foreach ($lainnya as $p): ?>
                <div class="col-lg-3 col-sm-6">
                    <?php require __DIR__ . '/../includes/kartu_produk.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>

    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
