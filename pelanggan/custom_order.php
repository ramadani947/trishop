<?php
/**
 * pelanggan/custom_order.php
 * Pintu masuk custom order: menjelaskan alurnya lebih dulu.
 *
 * Halaman inilah yang dituju menu "Custom Order" pada navigasi. Formulir
 * pemesanannya sendiri berdiri sebagai halaman terpisah di
 * pelanggan/buat_custom_order.php, dicapai lewat tombol di bawah.
 *
 * Alamat berkas ini tidak diubah meski isinya berganti, supaya seluruh
 * tautan yang sudah menunjuk ke sini - navigasi, footer, dan ajakan pada
 * halaman detail produk - tetap mendarat di tempat yang benar.
 */

require_once __DIR__ . '/../includes/init.php';

wajib_pelanggan();

// Hanya dihitung, bukan diambil seluruh barisnya: halaman ini tidak
// menampilkan satu pun data master, hanya perlu tahu layanannya siap
// atau belum sebelum menawarkan tombolnya.
$siap = (int) $pdo->query("SELECT COUNT(*) FROM model_tas WHERE status = 'aktif'")->fetchColumn() > 0
     && (int) $pdo->query("SELECT COUNT(*) FROM bahan     WHERE status = 'aktif'")->fetchColumn() > 0;

$judulHalaman = 'Custom Order';
$menuAktif    = 'custom';

require_once __DIR__ . '/../includes/header.php';
?>

    <section class="panel-langkah panel-langkah-dalam">
        <div>

            <div class="judul-bagian">
                <span class="rintik">Cara Memesan</span>
                <!-- Ditulis h1 karena inilah judul tertinggi halaman ini.
                     Aturan .judul-bagian pada style.css berlaku untuk h1
                     maupun h2, jadi tampilannya sama saja. -->
                <h1>Custom Order dalam Tiga Langkah</h1>
                <p>Sistem menghitung harga begitu Anda memilih, jadi anggaran acara dapat langsung disusun.</p>
            </div>

            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="langkah">
                        <span class="nomor-langkah">01</span>
                        <h2 class="h5 mt-3 mb-2">Pilih Model &amp; Bahan</h2>
                        <p class="teks-kaki mx-auto">
                            Tentukan model tas dan jenis bahan. Setiap kombinasi punya
                            faktor harga yang sudah ditetapkan toko.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="langkah">
                        <span class="nomor-langkah">02</span>
                        <h2 class="h5 mt-3 mb-2">Tentukan Desain</h2>
                        <p class="teks-kaki mx-auto">
                            Gunakan template desain yang kami sediakan, atau unggah
                            berkas desain milik Anda sendiri.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="langkah">
                        <span class="nomor-langkah">03</span>
                        <h2 class="h5 mt-3 mb-2">Bayar &amp; Diproses</h2>
                        <p class="teks-kaki mx-auto">
                            Total muncul otomatis. Setelah pembayaran diterima,
                            pesanan langsung masuk antrean produksi.
                        </p>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <?php if ($siap): ?>
                    <a href="<?= BASE_URL ?>/pelanggan/buat_custom_order.php"
                       class="btn btn-primary px-5 py-3">
                        Buat Custom Order
                    </a>
                    <!-- mx-auto wajib ada: .teks-kaki dibatasi max-width 42ch,
                         jadi tanpa margin otomatis bloknya menempel ke kiri
                         meski teks di dalamnya sudah rata tengah. -->
                    <p class="teks-kaki mt-3 mb-0 mx-auto">
                        Minimum pemesanan <?= MIN_QTY_CUSTOM ?> pcs, dan satu pesanan
                        berlaku untuk satu jenis tas.
                    </p>
                <?php else: ?>
                    <!-- Tombolnya tidak ditampilkan sama sekali, bukan sekadar
                         dinonaktifkan, supaya pelanggan tidak diantar ke
                         formulir yang pasti menolaknya. -->
                    <div class="alert alert-warning mb-0 d-inline-block text-start">
                        Layanan custom order belum tersedia karena data model tas
                        atau bahan belum lengkap.
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
