<?php
/**
 * index.php
 * Beranda pelanggan: pengenalan toko dan pintu masuk custom order.
 * Katalog ready stock kini punya halaman sendiri di pelanggan/ready_stock.php.
 */

require_once __DIR__ . '/includes/init.php';

// Admin diarahkan ke dashboard, tidak perlu melihat katalog pelanggan.
if (is_admin()) {
    redirect(BASE_URL . '/admin/dashboard.php');
}

$judulHalaman = 'Beranda';
$menuAktif    = 'beranda';
$lebarPenuh   = true;   // tiap bagian di bawah mengatur wadahnya sendiri

require_once __DIR__ . '/includes/header.php';
?>

<div class="container pt-4">
    <?php require __DIR__ . '/includes/flash.php'; ?>
</div>

<section class="py-5 bagian-fitur">
    <div class="container-wide">

        <div class="judul-bagian">
            <span class="rintik">Kenapa Tri Shop Souvenir</span>
            <!-- Ditulis h1, bukan h2, karena inilah judul tertinggi di beranda
                 sejak hero dihapus. Setiap halaman perlu tepat satu h1 sebagai
                 penanda topiknya. Tampilannya tidak berubah: aturan
                 .judul-bagian pada style.css berlaku untuk h1 maupun h2. -->
            <h1>Kualitas yang Bisa Anda Percaya</h1>
        </div>

        <div class="baris-fitur">
            <div class="fitur-teks">
                <h2>Kualitas Premium</h2>
                <p>
                    Kami hanya menggunakan material terbaik, memastikan setiap tas
                    souvenir Anda tahan lama dan memiliki tampilan yang berkelas.
                </p>
            </div>
            <!-- Gambarnya diunggah lewat Pengaturan Toko di panel admin.
                 Selama belum ada, kolom gambarnya tidak dirender sama sekali
                 dan teks di sebelahnya memakai lebar penuh. Teks alt-nya
                 tersimpan di slot_gambar_beranda() supaya halaman ini dan
                 form admin memakai keterangan yang sama. -->
            <?= petak_fitur('kualitas-1') ?>
        </div>

        <div class="baris-fitur baris-fitur-terbalik">
            <div class="fitur-teks">
                <h2>Kustomisasi Tanpa Batas</h2>
                <p>
                    Kami menawarkan fleksibilitas penuh, dari pemilihan bahan hingga
                    teknik cetak. Pastikan tas souvenir Anda seratus persen
                    mencerminkan brand atau tema acara Anda.
                </p>
            </div>
            <?= petak_fitur('kustomisasi') ?>
        </div>

        <div class="baris-fitur">
            <div class="fitur-teks">
                <h2>Harga Terhitung Otomatis</h2>
                <p>
                    Begitu Anda memilih model dan bahan, totalnya langsung muncul
                    saat itu juga. Harga dihitung sistem dari harga bahan dikalikan
                    faktor model tas, jadi anggaran acara dapat disusun tanpa
                    menunggu penawaran dan tanpa tanya-jawab lebih dulu.
                </p>
            </div>
            <?= petak_fitur('harga-otomatis') ?>
        </div>

    </div>
</section>


<?php require_once __DIR__ . '/includes/footer.php'; ?>
