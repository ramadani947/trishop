<?php
/**
 * pelanggan/profil.php
 * Pengelolaan data profil pelanggan.
 * Nomor telepon dan alamat digunakan sebagai data kontak pada proses
 * pembayaran serta keperluan komunikasi pesanan.
 */

require_once __DIR__ . '/../includes/init.php';

wajib_pelanggan();

$user   = user_aktif();
$error  = '';
$sukses = '';

$stmt = $pdo->prepare("SELECT nama, email, no_hp, alamat, created_at FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$profil = $stmt->fetch();

if (!$profil) {
    logout_user();
    redirect(BASE_URL . '/auth/login.php');
}

// --- Simpan perubahan profil -------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profil['nama']   = trim($_POST['nama'] ?? '');
    $profil['email']  = trim($_POST['email'] ?? '');
    $profil['no_hp']  = trim($_POST['no_hp'] ?? '');
    $profil['alamat'] = trim($_POST['alamat'] ?? '');

    if ($profil['nama'] === '' || $profil['email'] === '') {
        $error = 'Nama dan email wajib diisi.';
    } elseif (!email_valid($profil['email'])) {
        $error = 'Format email tidak valid.';
    } elseif ($profil['no_hp'] !== '' && !preg_match('/^[0-9+\-\s]{8,20}$/', $profil['no_hp'])) {
        $error = 'Nomor telepon hanya boleh berisi angka, tanda plus, dan tanda hubung.';
    }

    // Pastikan email belum dipakai pengguna lain.
    if ($error === '') {
        $cek = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
        $cek->execute([$profil['email'], $user['id']]);

        if ($cek->fetch()) {
            $error = 'Email tersebut sudah digunakan oleh akun lain.';
        }
    }

    if ($error === '') {
        $pdo->prepare(
            "UPDATE users SET nama = ?, email = ?, no_hp = ?, alamat = ? WHERE id = ?"
        )->execute([
            $profil['nama'], $profil['email'], $profil['no_hp'],
            $profil['alamat'], $user['id'],
        ]);

        // Perbarui nama pada sesi agar tampilan navigasi ikut menyesuaikan.
        $_SESSION['nama'] = $profil['nama'];

        $sukses = 'Profil berhasil diperbarui.';
    }
}

// Ringkasan aktivitas pemesanan.
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS jumlah,
            COALESCE(SUM(CASE WHEN status IN ('diproses','selesai') THEN total_harga ELSE 0 END), 0) AS total
     FROM pesanan WHERE user_id = ?"
);
$stmt->execute([$user['id']]);
$ringkasan = $stmt->fetch();

$judulHalaman = 'Profil Saya';
$menuAktif    = 'profil';

require_once __DIR__ . '/../includes/header.php';
?>

    <h1 class="judul-halaman">Profil Saya<span class="sub">Kelola data akun dan kontak Anda.</span></h1>

    <div class="row g-4">

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">Data Diri</div>
                <div class="card-body">

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2"><?= e($error) ?></div>
                    <?php endif; ?>

                    <?php if ($sukses): ?>
                        <div class="alert alert-success py-2"><?= e($sukses) ?></div>
                    <?php endif; ?>

                    <form method="post"><?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama" name="nama"
                                   value="<?= e($profil['nama']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= e($profil['email']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="no_hp" class="form-label">Nomor Telepon</label>
                            <input type="text" class="form-control" id="no_hp" name="no_hp"
                                   value="<?= e($profil['no_hp']) ?>" placeholder="Contoh: 081234567890">
                            <div class="form-text">
                                Digunakan sebagai data kontak pada proses pembayaran.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="alamat" class="form-label">
                                Alamat <span class="text-muted">(opsional)</span>
                            </label>
                            <textarea class="form-control" id="alamat" name="alamat"
                                      rows="3"><?= e($profil['alamat']) ?></textarea>
                        </div>

                        <button class="btn btn-primary">Simpan Perubahan</button>

                    </form>

                </div>
            </div>
        </div>

        <div class="col-lg-5">

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">Ringkasan Akun</div>
                <div class="card-body">

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Terdaftar sejak</span>
                        <span class="small"><?= date('d/m/Y', strtotime($profil['created_at'])) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Jumlah pesanan</span>
                        <span class="small"><?= (int) $ringkasan['jumlah'] ?></span>
                    </div>

                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Total transaksi</span>
                        <span class="small"><?= rupiah($ringkasan['total']) ?></span>
                    </div>

                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">Keamanan</div>
                <div class="card-body">
                    <p class="text-muted small">
                        Perbarui kata sandi secara berkala untuk menjaga keamanan akun Anda.
                    </p>
                    <a href="<?= BASE_URL ?>/auth/ganti_sandi.php"
                       class="btn btn-outline-secondary btn-sm">Ganti Kata Sandi</a>
                </div>
            </div>

        </div>

    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
