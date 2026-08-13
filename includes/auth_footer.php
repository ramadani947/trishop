<?php
/**
 * includes/auth_footer.php
 * Kerangka bawah halaman masuk dan daftar, pasangan auth_header.php.
 *
 * Skrip sandi.js dimuat di sini karena kedua halaman sama-sama memuat
 * isian kata sandi dan memerlukan tombol "Lihat".
 */
?>
        </div>
    </div>

    <p class="text-center mt-3 mb-0">
        <a href="<?= BASE_URL ?>/index.php" class="tautan-kembali">&larr; Kembali ke beranda</a>
    </p>

</div>
</div>

<script src="<?= aset("assets/js/sandi.js") ?>"></script>
</body>
</html>
