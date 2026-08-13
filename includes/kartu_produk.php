<?php
/**
 * includes/kartu_produk.php
 * Satu kartu produk ready stock pada katalog.
 * Dipakai bersama oleh pelanggan/ready_stock.php dan
 * pelanggan/detail_produk.php agar keduanya tidak pernah tampil berbeda.
 *
 * Sebelum menyertakan berkas ini, sediakan:
 *   $p         - baris produk (id, nama_produk, harga, stok, gambar)
 *   $aksiKartu - true bila kartu perlu tombol "Lihat Detail" dan "+ Keranjang"
 */

$tautanProduk = BASE_URL . '/pelanggan/detail_produk.php?id=' . (int) $p['id'];
?>
<div class="kartu-produk h-100 d-flex flex-column">

    <a href="<?= $tautanProduk ?>" class="bingkai-gambar d-block">
        <?php if ($p['gambar']): ?>
            <img src="<?= BASE_URL ?>/uploads/produk/<?= e($p['gambar']) ?>"
                 alt="<?= e($p['nama_produk']) ?>" loading="lazy">
        <?php else: ?>
            <span class="tanpa-gambar">Tanpa gambar</span>
        <?php endif; ?>
    </a>

    <span class="kategori-produk">
        <?= $p['stok'] > 0 ? 'Tersedia &middot; ' . (int) $p['stok'] . ' pcs' : 'Stok Habis' ?>
    </span>

    <a href="<?= $tautanProduk ?>" class="nama-produk"><?= e($p['nama_produk']) ?></a>

    <div class="harga-produk"><?= rupiah($p['harga']) ?></div>

    <?php if ($aksiKartu): ?>
        <div class="aksi-produk mt-auto">
            <a href="<?= $tautanProduk ?>" class="btn btn-sm btn-outline-primary flex-fill">
                Lihat Detail
            </a>

            <?php if ($p['stok'] > 0): ?>
                <!-- Pintasan menambahkan 1 pcs langsung dari katalog. Tanpa
                     JavaScript, form ini terkirim biasa dan pengguna kembali
                     ke katalog dengan pesan flash. -->
                <form method="post" class="flex-fill"
                      action="<?= BASE_URL ?>/pelanggan/tambah_keranjang.php"
                      data-tambah-keranjang>
                    <input type="hidden" name="produk_id" value="<?= (int) $p['id'] ?>">
                    <input type="hidden" name="qty" value="1">
                    <input type="hidden" name="kembali" value="katalog">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        + Keranjang
                    </button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
