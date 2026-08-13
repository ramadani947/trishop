<?php
/**
 * includes/footer.php
 * Kerangka bawah seluruh halaman pelanggan: penutup konten, footer,
 * dan pemuatan skrip bersama.
 *
 * Halaman yang memakai $lebarPenuh = true pada header wajib menutup
 * wadahnya sendiri sebelum menyertakan berkas ini. Nilainya selalu sudah
 * ditetapkan includes/header.php.
 */
?>
<?php if (!$lebarPenuh): ?>
</div>
<?php endif; ?>
</main>

<footer class="kaki-situs">
    <div class="container-wide">

        <div class="row g-4 g-lg-5">

            <div class="col-lg-4 col-md-12">
                <a class="merek-kaki" href="<?= BASE_URL ?>/index.php">Tri Shop Souvenir</a>
                <p class="teks-kaki">
                    Perajin tas souvenir di Surabaya. Kami mengerjakan tas ready stock
                    maupun pesanan custom dengan model, bahan, dan desain pilihan Anda.
                </p>
            </div>

            <div class="col-lg-2 col-6">
                <h2 class="judul-kaki">Jelajahi</h2>
                <a class="tautan-kaki" href="<?= BASE_URL ?>/index.php">Beranda</a>
                <a class="tautan-kaki" href="<?= BASE_URL ?>/pelanggan/ready_stock.php">Ready Stock</a>
                <a class="tautan-kaki" href="<?= BASE_URL ?>/pelanggan/custom_order.php">Custom Order</a>
            </div>

            <div class="col-lg-3 col-6">
                <h2 class="judul-kaki">Akun</h2>
                <?php if (user_aktif()): ?>
                    <a class="tautan-kaki" href="<?= BASE_URL ?>/pelanggan/keranjang.php">Keranjang Belanja</a>
                    <a class="tautan-kaki" href="<?= BASE_URL ?>/pelanggan/pesanan_saya.php">Pesanan Saya</a>
                    <a class="tautan-kaki" href="<?= BASE_URL ?>/pelanggan/profil.php">Profil Saya</a>
                <?php else: ?>
                    <a class="tautan-kaki" href="<?= BASE_URL ?>/auth/login.php">Masuk</a>
                    <a class="tautan-kaki" href="<?= BASE_URL ?>/auth/register.php">Daftar Akun</a>
                <?php endif; ?>
            </div>

            <div class="col-lg-3 col-md-12">
                <h2 class="judul-kaki">Kontak</h2>
                <p class="teks-kaki mb-2">
                    Pondok Benowo Indah Blok CD No.&nbsp;8<br>
                    Surabaya, Jawa Timur
                </p>
                <p class="teks-kaki mb-0">Minimum custom order <?= MIN_QTY_CUSTOM ?> pcs</p>
            </div>

        </div>

        <div class="garis-kaki">
            &copy; <?= date('Y') ?> Tri Shop Souvenir. Seluruh hak cipta dilindungi.
        </div>

    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= aset("assets/js/keranjang.js") ?>"></script>
</body>
</html>
