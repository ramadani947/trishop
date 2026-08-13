<?php
/**
 * admin/produk.php
 * Daftar produk ready stock beserta aksi hapus.
 * Memenuhi kebutuhan fungsional nomor 2 pada Tabel 3.1.
 */

require_once __DIR__ . '/../includes/init.php';

wajib_admin();

// --- Proses hapus produk ------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    hapus_master($pdo, 'produk', (int) ($_POST['id'] ?? 0));
    redirect(BASE_URL . '/admin/produk.php');
}

// --- Ambil data produk --------------------------------------------------

$cari = trim($_GET['cari'] ?? '');

if ($cari !== '') {
    $stmt = $pdo->prepare(
        "SELECT * FROM produk WHERE nama_produk LIKE ? ORDER BY created_at DESC"
    );
    $stmt->execute(['%' . $cari . '%']);
} else {
    $stmt = $pdo->query("SELECT * FROM produk ORDER BY created_at DESC");
}

$daftarProduk = $stmt->fetchAll();

$judulHalaman = 'Produk Ready Stock';
$menuAktif    = 'produk';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- flex-wrap dan gap menjaga kotak pencarian dan tombol Tambah tetap utuh
     di layar ponsel; tanpanya keduanya berdesakan sampai terpotong. -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">

    <form method="get" class="d-flex flex-wrap gap-2" style="max-width: 340px;">
        <input type="text" name="cari" class="form-control form-control-sm"
               placeholder="Cari nama produk" value="<?= e($cari) ?>">
        <button class="btn btn-sm btn-outline-secondary">Cari</button>
        <?php if ($cari !== ''): ?>
            <a href="<?= BASE_URL ?>/admin/produk.php" class="btn btn-sm btn-link">Reset</a>
        <?php endif; ?>
    </form>

    <a href="<?= BASE_URL ?>/admin/produk_form.php" class="btn btn-primary btn-sm">
        Tambah Produk
    </a>

</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 80px;">Gambar</th>
                    <th>Nama Produk</th>
                    <th class="text-end">Harga</th>
                    <th class="text-center">Stok</th>
                    <th class="text-center">Status</th>
                    <th class="text-end" style="min-width: 160px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($daftarProduk)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <?= $cari !== '' ? 'Produk tidak ditemukan.' : 'Belum ada produk. Klik "Tambah Produk" untuk memulai.' ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($daftarProduk as $p): ?>
                    <tr>
                        <td><?= thumb_berkas('produk', $p['gambar'], 56) ?></td>

                        <td>
                            <div><?= e($p['nama_produk']) ?></div>
                            <?php if ($p['deskripsi']): ?>
                                <div class="small text-muted">
                                    <?= e(mb_strimwidth($p['deskripsi'], 0, 70, '...')) ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <td class="text-end"><?= rupiah($p['harga']) ?></td>

                        <td class="text-center">
                            <?php if ($p['stok'] > 0): ?>
                                <?= (int) $p['stok'] ?>
                            <?php else: ?>
                                <span class="text-danger">0</span>
                            <?php endif; ?>
                        </td>

                        <td class="text-center"><?= badge_status($p['status']) ?></td>

                        <td>
                            <div class="aksi-tabel">
                                <a href="<?= BASE_URL ?>/admin/produk_form.php?id=<?= (int) $p['id'] ?>"
                                   class="btn btn-sm btn-outline-primary">Edit</a>
                                <?= tombol_hapus($p['id'], 'Hapus produk ' . $p['nama_produk'] . '?') ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
