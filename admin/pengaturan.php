<?php
/**
 * admin/pengaturan.php
 * Pengelolaan data identitas usaha yang digunakan sebagai kop surat
 * pada Laporan Penjualan Periodik.
 */

require_once __DIR__ . '/../includes/init.php';

wajib_admin();

$error = '';

// Ambil data toko, atau siapkan baris awal bila belum tersedia.
$toko = $pdo->query("SELECT * FROM pengaturan_toko LIMIT 1")->fetch();

if (!$toko) {
    $pdo->prepare(
        "INSERT INTO pengaturan_toko (nama_toko, alamat, no_telp, email)
         VALUES ('Tri Shop Souvenir', '', '', '')"
    )->execute();

    $toko = $pdo->query("SELECT * FROM pengaturan_toko LIMIT 1")->fetch();
}

// --- Simpan perubahan identitas usaha --------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toko['nama_toko'] = trim($_POST['nama_toko'] ?? '');
    $toko['alamat']    = trim($_POST['alamat'] ?? '');
    $toko['no_telp']   = trim($_POST['no_telp'] ?? '');
    $toko['email']     = trim($_POST['email'] ?? '');

    if ($toko['nama_toko'] === '') {
        $error = 'Nama usaha wajib diisi.';
    } elseif ($toko['email'] !== '' && !email_valid($toko['email'])) {
        $error = 'Format email tidak valid.';
    }

    $namaLogo = $toko['logo'];

    if ($error === '') {
        [$namaLogo, $error] = ganti_file_form($_FILES['logo'] ?? null, 'produk', $toko['logo'], 'Logo');
    }

    // --- Gambar bagian keunggulan pada beranda --------------------------
    // Dikerjakan terpisah dari data toko: gambar-gambar ini disimpan sebagai
    // berkas dengan nama tetap, bukan kolom di tabel pengaturan_toko.
    if ($error === '') {
        foreach (array_keys(slot_gambar_beranda()) as $slot) {

            // Centang hapus diproses lebih dulu, supaya admin bisa menghapus
            // dan mengunggah pengganti dalam satu kali simpan.
            if (!empty($_POST['hapus_beranda'][$slot])) {
                hapus_gambar_beranda($slot);
            }

            // Tiap tempat memakai nama medan sendiri, bukan beranda[slot].
            // Dengan nama bertanda kurung, PHP menyusun ulang $_FILES menjadi
            // $_FILES['beranda']['name'][slot] - bentuk yang tidak bisa
            // langsung dioper ke upload_file().
            $error = simpan_gambar_beranda($_FILES['beranda_' . $slot] ?? null, $slot);

            if ($error !== '') {
                break;
            }
        }
    }

    if ($error === '') {
        $pdo->prepare(
            "UPDATE pengaturan_toko
             SET nama_toko = ?, alamat = ?, no_telp = ?, email = ?, logo = ?
             WHERE id = ?"
        )->execute([
            $toko['nama_toko'], $toko['alamat'], $toko['no_telp'],
            $toko['email'], $namaLogo, $toko['id'],
        ]);

        set_flash('success', 'Pengaturan toko berhasil disimpan.');
        redirect(BASE_URL . '/admin/pengaturan.php');
    }
}

$judulHalaman = 'Pengaturan Toko';
$menuAktif    = 'pengaturan';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="row g-4">

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">Identitas Usaha</div>
            <div class="card-body">

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><?= e($error) ?></div>
                <?php endif; ?>

                <p class="text-muted small">
                    Data berikut digunakan sebagai kop surat pada Laporan Penjualan Periodik.
                </p>

                <form method="post" enctype="multipart/form-data"><?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="nama_toko" class="form-label">Nama Usaha</label>
                        <input type="text" class="form-control" id="nama_toko" name="nama_toko"
                               value="<?= e($toko['nama_toko']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="alamat" class="form-label">Alamat</label>
                        <textarea class="form-control" id="alamat" name="alamat"
                                  rows="2"><?= e($toko['alamat']) ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 mb-3">
                            <label for="no_telp" class="form-label">Nomor Telepon</label>
                            <input type="text" class="form-control" id="no_telp" name="no_telp"
                                   value="<?= e($toko['no_telp']) ?>">
                        </div>

                        <div class="col-sm-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= e($toko['email']) ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="logo" class="form-label">
                            Logo <span class="text-muted">(opsional)</span>
                        </label>
                        <input type="file" class="form-control" id="logo" name="logo"
                               accept="image/jpeg,image/png,image/webp">
                        <div class="form-text">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</div>

                        <?php if (berkas_unggahan_ada('produk', $toko['logo'])): ?>
                            <img src="<?= BASE_URL ?>/uploads/produk/<?= e($toko['logo']) ?>"
                                 alt="" class="rounded mt-2"
                                 style="max-height: 90px; object-fit: contain;">
                        <?php endif; ?>
                    </div>

                    <hr class="my-4">

                    <h2 class="h6 mb-1">Gambar Beranda</h2>
                    <p class="text-muted small">
                        Ketiga gambar pertama tampil pada bagian keunggulan di
                        halaman beranda. Ukuran yang paling pas 1000 &times; 625
                        piksel (perbandingan 16:10). Gambar terakhir, &ldquo;Foto
                        Panel Masuk &amp; Daftar&rdquo;, tampil di panel kiri
                        halaman masuk dan daftar; karena panelnya tegak, pilih
                        foto dengan orientasi potret, sekitar 900 &times; 1300
                        piksel. Selama belum diisi, yang tampil adalah petak
                        berwarna seperti sebelumnya.
                    </p>

                    <?php foreach (slot_gambar_beranda() as $slot => $info): ?>
                        <?php $berkas = gambar_beranda($slot); ?>
                        <div class="mb-4">
                            <label for="beranda_<?= e($slot) ?>" class="form-label">
                                <?= e($info['label']) ?>
                            </label>
                            <input type="file" class="form-control"
                                   id="beranda_<?= e($slot) ?>" name="beranda_<?= e($slot) ?>"
                                   accept="image/jpeg,image/png,image/webp">

                            <?php if ($berkas !== null): ?>
                                <div class="d-flex align-items-center flex-wrap gap-3 mt-2">
                                    <img src="<?= aset('uploads/beranda/' . $berkas) ?>"
                                         alt="" class="rounded"
                                         style="width: 128px; height: 80px; object-fit: cover;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               id="hapus_<?= e($slot) ?>"
                                               name="hapus_beranda[<?= e($slot) ?>]" value="1">
                                        <label class="form-check-label small" for="hapus_<?= e($slot) ?>">
                                            Hapus gambar ini
                                        </label>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="form-text">Belum ada gambar.</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <button class="btn btn-primary">Simpan Pengaturan</button>

                </form>

            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">Pratinjau Kop Surat</div>
            <div class="card-body">

                <div class="border rounded p-3 bg-white">
                    <div style="border-bottom: 3px double #000; padding-bottom: 10px; text-align: center;">

                        <?php if (!empty($toko['logo'])): ?>
                            <img src="<?= BASE_URL ?>/uploads/produk/<?= e($toko['logo']) ?>"
                                 alt="" style="max-height: 55px; object-fit: contain; margin-bottom: 6px;">
                        <?php endif; ?>

                        <div style="font-size: 17px; font-weight: bold; letter-spacing: 1px;">
                            <?= e(strtoupper($toko['nama_toko'])) ?>
                        </div>

                        <?php if ($toko['alamat']): ?>
                            <div style="font-size: 11px;"><?= e($toko['alamat']) ?></div>
                        <?php endif; ?>

                        <?php if ($toko['no_telp'] || $toko['email']): ?>
                            <div style="font-size: 11px;">
                                <?php if ($toko['no_telp']): ?>
                                    Telp: <?= e($toko['no_telp']) ?>
                                <?php endif; ?>
                                <?php if ($toko['no_telp'] && $toko['email']): ?>
                                    &nbsp;|&nbsp;
                                <?php endif; ?>
                                <?php if ($toko['email']): ?>
                                    Email: <?= e($toko['email']) ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    </div>

                    <div style="text-align: center; margin-top: 14px;">
                        <div style="font-size: 13px; font-weight: bold; text-decoration: underline;">
                            LAPORAN PENJUALAN PERIODIK
                        </div>
                        <div style="font-size: 11px; margin-top: 3px; color: #666;">
                            Periode ...
                        </div>
                    </div>
                </div>

                <p class="text-muted small mt-3 mb-0">
                    Pratinjau menyesuaikan data yang tersimpan. Simpan perubahan
                    terlebih dahulu untuk memperbarui tampilan.
                </p>

            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
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

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
