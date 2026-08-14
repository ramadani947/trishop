<?php
/**
 * auth/register.php
 * Halaman registrasi pelanggan baru.
 * Implementasi jalur "Melakukan registrasi" dan "Validasi data akun"
 * pada Activity Diagram Registrasi dan Login (Gambar 3.3).
 */

require_once __DIR__ . '/../includes/init.php';

if (is_login()) {
    redirect(BASE_URL . '/index.php');
}

$error = '';
$data  = ['nama' => '', 'email' => '', 'no_hp' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['nama']  = trim($_POST['nama'] ?? '');
    $data['email'] = trim($_POST['email'] ?? '');
    $data['no_hp'] = trim($_POST['no_hp'] ?? '');
    $password      = $_POST['password'] ?? '';
    $konfirmasi    = $_POST['konfirmasi'] ?? '';

    // Validasi data akun sebelum disimpan ke basis data.
    if ($data['nama'] === '' || $data['email'] === '' || $password === '') {
        $error = 'Nama, email, dan kata sandi wajib diisi.';
    } elseif (!email_valid($data['email'])) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 8) {
        $error = 'Kata sandi minimal 8 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        $userId = registrasi_pelanggan($pdo, $data['nama'], $data['email'], $password, $data['no_hp']);

        if ($userId === false) {
            $error = 'Email tersebut sudah terdaftar. Silakan gunakan email lain.';
        } else {
            set_flash('success', 'Registrasi berhasil. Silakan login menggunakan akun Anda.');
            redirect(BASE_URL . '/auth/login.php');
        }
    }
}

$judulHalaman = 'Registrasi';
$judulPanel   = 'Buat Akun Baru';
$subJudul     = 'Daftar untuk mulai berbelanja';
$lebarPanel   = '470px';

require_once __DIR__ . '/../includes/auth_header.php';
?>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" action=""><?= csrf_field() ?>
                <div class="mb-3">
                    <label for="nama" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama" name="nama"
                           value="<?= e($data['nama']) ?>" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= e($data['email']) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="no_hp" class="form-label">Nomor HP <span class="text-muted">(opsional)</span></label>
                    <input type="text" class="form-control" id="no_hp" name="no_hp"
                           value="<?= e($data['no_hp']) ?>">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <input type="password" class="form-control" id="password" name="password"
                           minlength="8" required>
                    <div class="form-text">Minimal 8 karakter.</div>
                </div>

                <div class="mb-3">
                    <label for="konfirmasi" class="form-label">Konfirmasi Kata Sandi</label>
                    <input type="password" class="form-control" id="konfirmasi" name="konfirmasi"
                           minlength="8" required>
                </div>

                <button type="submit" class="btn-auth-kirim">
                    Daftar <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <p class="text-center text-muted mt-3 mb-0">
                Sudah punya akun?
                <a href="<?= BASE_URL ?>/auth/login.php">Login di sini</a>
            </p>

<?php require_once __DIR__ . '/../includes/auth_footer.php'; ?>
