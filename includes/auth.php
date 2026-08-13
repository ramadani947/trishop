<?php
/**
 * includes/auth.php
 * Pengelolaan sesi dan pembatasan akses berdasarkan peran pengguna.
 * Memenuhi kebutuhan non-fungsional Security pada Tabel 3.2.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Memeriksa apakah pengguna sudah login. */
function is_login()
{
    return isset($_SESSION['user_id']);
}

/** Memeriksa apakah pengguna yang login berperan sebagai admin. */
function is_admin()
{
    return is_login() && ($_SESSION['role'] ?? '') === 'admin';
}

/** Data pengguna yang sedang login. */
function user_aktif()
{
    if (!is_login()) {
        return null;
    }
    return [
        'id'   => $_SESSION['user_id'],
        'nama' => $_SESSION['nama'] ?? '',
        'role' => $_SESSION['role'] ?? '',
    ];
}

/** Membuat sesi login setelah kredensial diverifikasi. */
function login_user(array $user)
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nama']    = $user['nama'];
    $_SESSION['role']    = $user['role'];
}

/** Menghapus sesi dan mengakhiri login. */
function logout_user()
{
    $_SESSION = [];
    session_destroy();
}

/** Halaman hanya boleh diakses pengguna yang sudah login. */
function wajib_login()
{
    if (!is_login()) {
        set_flash('warning', 'Silakan login terlebih dahulu.');
        redirect(BASE_URL . '/auth/login.php');
    }
}

/** Halaman hanya boleh diakses admin. */
function wajib_admin()
{
    wajib_login();
    if (!is_admin()) {
        set_flash('danger', 'Anda tidak memiliki akses ke halaman tersebut.');
        redirect(BASE_URL . '/index.php');
    }
}

/** Halaman hanya boleh diakses pelanggan. */
function wajib_pelanggan()
{
    wajib_login();
    if (is_admin()) {
        redirect(BASE_URL . '/admin/dashboard.php');
    }
}

/**
 * Verifikasi kredensial login.
 * Mengembalikan data pengguna bila valid, atau false bila gagal.
 */
function verifikasi_login(PDO $pdo, $email, $password)
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}

/**
 * Registrasi pelanggan baru.
 * Mengembalikan id pengguna baru, atau false bila email sudah terdaftar.
 */
function registrasi_pelanggan(PDO $pdo, $nama, $email, $password, $noHp = null)
{
    $cek = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $cek->execute([$email]);
    if ($cek->fetch()) {
        return false;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO users (nama, email, password, no_hp, role)
         VALUES (?, ?, ?, ?, 'pelanggan')"
    );
    $stmt->execute([
        $nama,
        $email,
        password_hash($password, PASSWORD_BCRYPT),
        $noHp,
    ]);

    return (int) $pdo->lastInsertId();
}
