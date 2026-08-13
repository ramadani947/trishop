<?php
/**
 * auth/ganti_sandi.php
 * Penggantian kata sandi untuk admin maupun pelanggan.
 * Kata sandi disimpan dalam bentuk hash menggunakan password_hash().
 */

require_once __DIR__ . '/../includes/init.php';

wajib_login();

$user   = user_aktif();
$error  = '';
$sukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sandiLama  = $_POST['sandi_lama'] ?? '';
    $sandiBaru  = $_POST['sandi_baru'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();

    if ($sandiLama === '' || $sandiBaru === '') {
        $error = 'Seluruh kolom wajib diisi.';
    } elseif (!$row || !password_verify($sandiLama, $row['password'])) {
        $error = 'Kata sandi lama tidak sesuai.';
    } elseif (strlen($sandiBaru) < 8) {
        $error = 'Kata sandi baru minimal 8 karakter.';
    } elseif ($sandiBaru !== $konfirmasi) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } elseif ($sandiBaru === $sandiLama) {
        $error = 'Kata sandi baru tidak boleh sama dengan kata sandi lama.';
    } else {
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
            ->execute([password_hash($sandiBaru, PASSWORD_BCRYPT), $user['id']]);

        $sukses = 'Kata sandi berhasil diperbarui.';
    }
}

// Admin memakai kerangka halaman admin, pelanggan memakai kerangka pelanggan.
$judulHalaman = 'Ganti Kata Sandi';
$menuAktif    = is_admin() ? '' : 'profil';

if (is_admin()) {
    require_once __DIR__ . '/../includes/admin_header.php';
} else {
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <h1 class="judul-halaman">
        Ganti Kata Sandi
        <span class="sub">Perbarui kata sandi secara berkala untuk menjaga keamanan akun.</span>
    </h1>
    <?php
}
?>

<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Ganti Kata Sandi</div>
            <div class="card-body">

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><?= e($error) ?></div>
                <?php endif; ?>

                <?php if ($sukses): ?>
                    <div class="alert alert-success py-2"><?= e($sukses) ?></div>
                <?php endif; ?>

                <form method="post"><?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="sandi_lama" class="form-label">Kata Sandi Lama</label>
                        <input type="password" class="form-control" id="sandi_lama"
                               name="sandi_lama" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="sandi_baru" class="form-label">Kata Sandi Baru</label>
                        <input type="password" class="form-control" id="sandi_baru"
                               name="sandi_baru" minlength="8" required>
                        <div class="form-text">Minimal 8 karakter.</div>
                    </div>

                    <div class="mb-4">
                        <label for="konfirmasi" class="form-label">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" class="form-control" id="konfirmasi"
                               name="konfirmasi" minlength="8" required>
                    </div>

                    <button class="btn btn-primary">Simpan Kata Sandi</button>

                    <?php if (!is_admin()): ?>
                        <a href="<?= BASE_URL ?>/pelanggan/profil.php"
                           class="btn btn-outline-secondary">Kembali</a>
                    <?php endif; ?>

                </form>

            </div>
        </div>
    </div>
</div>

<?php
if (is_admin()) {
    require_once __DIR__ . '/../includes/admin_footer.php';
} else {
    ?>
    <script src="<?= aset("assets/js/sandi.js") ?>"></script>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
}
