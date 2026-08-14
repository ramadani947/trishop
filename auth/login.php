<?php
/**
 * auth/login.php
 * Halaman login untuk admin dan pelanggan.
 * Implementasi jalur "Mengisi form login" dan "Validasi kredensial"
 * pada Activity Diagram Registrasi dan Login (Gambar 3.3).
 */

require_once __DIR__ . '/../includes/init.php';

// Pengguna yang sudah login tidak perlu membuka halaman ini lagi.
if (is_login()) {
    redirect(is_admin() ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/index.php');
}

$error = '';
$email = '';

// Peran yang dipilih pada form: 'pelanggan' (bawaan) atau 'admin'.
$role_dipilih = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['role'] ?? '')
    : ($_GET['role'] ?? '');
if (!in_array($role_dipilih, ['pelanggan', 'admin'], true)) {
    $role_dipilih = 'pelanggan';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan kata sandi wajib diisi.';
    } else {
        $user = verifikasi_login($pdo, $email, $password);

        if ($user && $user['role'] !== $role_dipilih) {
            // Kredensial benar, tetapi tidak sesuai dengan opsi peran yang dipilih.
            $error = $role_dipilih === 'admin'
                ? 'Akun ini bukan akun admin. Silakan masuk sebagai pelanggan.'
                : 'Akun ini adalah akun admin. Silakan masuk sebagai admin.';
        } elseif ($user) {
            login_user($user);
            set_flash('success', 'Selamat datang, ' . $user['nama'] . '.');
            redirect($user['role'] === 'admin'
                ? BASE_URL . '/admin/dashboard.php'
                : BASE_URL . '/index.php');
        } else {
            $error = 'Email atau kata sandi salah.';
        }
    }
}

$judulHalaman = 'Login';
$judulPanel   = '';
$subJudul     = 'Masuk ke akun Anda';
$lebarPanel   = '430px';

require_once __DIR__ . '/../includes/auth_header.php';
?>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($flash = get_flash()): ?>
                <div class="alert alert-<?= e($flash['tipe']) ?> py-2"><?= e($flash['pesan']) ?></div>
            <?php endif; ?>

            <form method="post" action=""><?= csrf_field() ?>

                <!-- Pilihan peran: pelanggan atau admin -->
                <div class="mb-3">
                    <label class="form-label">Masuk sebagai</label>
                    <div class="auth-tombol-peran" role="group" aria-label="Pilihan peran login">
                        <input type="radio" class="btn-check" name="role" id="role-pelanggan"
                               value="pelanggan" <?= $role_dipilih === 'pelanggan' ? 'checked' : '' ?>>
                        <label for="role-pelanggan">
                            <i class="bi bi-person"></i> Pelanggan
                        </label>

                        <input type="radio" class="btn-check" name="role" id="role-admin"
                               value="admin" <?= $role_dipilih === 'admin' ? 'checked' : '' ?>>
                        <label for="role-admin">
                            <i class="bi bi-lock"></i> Admin
                        </label>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= e($email) ?>" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <!-- Tombol "Lihat" dipasang otomatis oleh assets/js/sandi.js -->
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="form-text">Klik &ldquo;Lihat&rdquo; untuk memperlihatkan kata sandi.</div>
                </div>

                <button type="submit" class="btn-auth-kirim">
                    Login <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <p class="text-center text-muted mt-3 mb-0" id="tautan-daftar">
                Belum punya akun?
                <a href="<?= BASE_URL ?>/auth/register.php">Daftar di sini</a>
            </p>

<script>
// Tautan pendaftaran hanya relevan bagi pelanggan.
(function () {
    var tautan   = document.getElementById('tautan-daftar');
    var opsiRole = document.querySelectorAll('input[name="role"]');

    function perbarui() {
        var admin = document.getElementById('role-admin').checked;
        tautan.classList.toggle('d-none', admin);
    }

    opsiRole.forEach(function (opsi) {
        opsi.addEventListener('change', perbarui);
    });
    perbarui();
})();
</script>

<?php require_once __DIR__ . '/../includes/auth_footer.php'; ?>
