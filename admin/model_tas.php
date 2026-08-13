<?php
/**
 * admin/model_tas.php
 * Pengelolaan template model tas beserta faktor pengali harga.
 * Faktor pengali menjadi dasar rumus kalkulasi harga custom order:
 * harga per pcs = harga bahan x faktor pengali.
 */

require_once __DIR__ . '/../includes/init.php';

wajib_admin();

$error  = '';
$editId = (int) ($_GET['edit'] ?? 0);

$data = [
    'nama_model'     => '',
    'deskripsi'      => '',
    'faktor_pengali' => '1.00',
    'gambar'         => null,
    'status'         => 'aktif',
];

if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM model_tas WHERE id = ?");
    $stmt->execute([$editId]);
    $lama = $stmt->fetch();

    if (!$lama) {
        set_flash('danger', 'Model tas tidak ditemukan.');
        redirect(BASE_URL . '/admin/model_tas.php');
    }
    $data = $lama;
}

// --- Hapus --------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    hapus_master($pdo, 'model', (int) ($_POST['id'] ?? 0));
    redirect(BASE_URL . '/admin/model_tas.php');
}

// --- Simpan tambah atau edit -------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'simpan') {
    $id = (int) ($_POST['id'] ?? 0);

    $data['nama_model']     = trim($_POST['nama_model'] ?? '');
    $data['deskripsi']      = trim($_POST['deskripsi'] ?? '');
    $data['faktor_pengali'] = $_POST['faktor_pengali'] ?? '';
    $data['status']         = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';

    if ($data['nama_model'] === '') {
        $error = 'Nama model tas wajib diisi.';
    } elseif (!is_numeric($data['faktor_pengali']) || (float) $data['faktor_pengali'] <= 0) {
        $error = 'Faktor pengali harus berupa angka lebih besar dari nol, misalnya 1.5.';
    }

    $namaGambar = $data['gambar'];

    if ($error === '') {
        [$namaGambar, $error] = ganti_file_form($_FILES['gambar'] ?? null, 'produk', $data['gambar']);
    }

    if ($error === '') {
        if ($id > 0) {
            $stmt = $pdo->prepare(
                "UPDATE model_tas
                 SET nama_model = ?, deskripsi = ?, faktor_pengali = ?, gambar = ?, status = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                $data['nama_model'], $data['deskripsi'], $data['faktor_pengali'],
                $namaGambar, $data['status'], $id,
            ]);
            set_flash('success', 'Model tas berhasil diperbarui.');
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO model_tas (nama_model, deskripsi, faktor_pengali, gambar, status)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $data['nama_model'], $data['deskripsi'], $data['faktor_pengali'],
                $namaGambar, $data['status'],
            ]);
            set_flash('success', 'Model tas berhasil ditambahkan.');
        }

        redirect(BASE_URL . '/admin/model_tas.php');
    }
}

$daftar = $pdo->query("SELECT * FROM model_tas ORDER BY nama_model ASC")->fetchAll();

$judulHalaman = 'Model Tas';
$menuAktif    = 'model';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="row g-4">

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <?= $editId > 0 ? 'Edit Model Tas' : 'Tambah Model Tas' ?>
            </div>
            <div class="card-body">

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="aksi" value="simpan">
                    <input type="hidden" name="id" value="<?= $editId ?>">

                    <div class="mb-3">
                        <label for="nama_model" class="form-label">Nama Model</label>
                        <input type="text" class="form-control" id="nama_model" name="nama_model"
                               value="<?= e($data['nama_model']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="faktor_pengali" class="form-label">Faktor Pengali</label>
                        <input type="number" class="form-control" id="faktor_pengali" name="faktor_pengali"
                               min="0.01" step="0.01" value="<?= e($data['faktor_pengali']) ?>" required>
                        <div class="form-text">
                            Pengali terhadap harga bahan. Contoh: 1.5 berarti harga bahan dikalikan 1,5.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">
                            Deskripsi <span class="text-muted">(opsional)</span>
                        </label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi"
                                  rows="2"><?= e($data['deskripsi']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="gambar" class="form-label">
                            Gambar
                            <?php if ($editId > 0): ?>
                                <span class="text-muted">(kosongkan bila tidak diganti)</span>
                            <?php endif; ?>
                        </label>
                        <input type="file" class="form-control" id="gambar" name="gambar"
                               accept="image/jpeg,image/png,image/webp">

                        <?php if ($editId > 0 && $data['gambar']): ?>
                            <img src="<?= BASE_URL ?>/uploads/produk/<?= e($data['gambar']) ?>"
                                 alt="" class="rounded mt-2"
                                 style="width: 90px; height: 90px; object-fit: cover;">
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="aktif"    <?= $data['status'] === 'aktif'    ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= $data['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>

                    <button class="btn btn-primary w-100">
                        <?= $editId > 0 ? 'Simpan Perubahan' : 'Tambah' ?>
                    </button>

                    <?php if ($editId > 0): ?>
                        <a href="<?= BASE_URL ?>/admin/model_tas.php"
                           class="btn btn-outline-secondary w-100 mt-2">Batal</a>
                    <?php endif; ?>
                </form>

            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px;">Gambar</th>
                            <th>Nama Model</th>
                            <th class="text-center">Faktor</th>
                            <th class="text-center">Status</th>
                            <th class="text-end" style="min-width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($daftar)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Belum ada model tas.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftar as $m): ?>
                            <tr>
                                <td><?= thumb_berkas('produk', $m['gambar']) ?></td>

                                <td>
                                    <div><?= e($m['nama_model']) ?></div>
                                    <?php if ($m['deskripsi']): ?>
                                        <div class="small text-muted">
                                            <?= e(mb_strimwidth($m['deskripsi'], 0, 50, '...')) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-info-subtle text-info-emphasis">
                                        &times; <?= number_format((float) $m['faktor_pengali'], 2, ',', '.') ?>
                                    </span>
                                </td>

                                <td class="text-center"><?= badge_status($m['status']) ?></td>

                                <td>
                                    <div class="aksi-tabel">
                                        <a href="?edit=<?= (int) $m['id'] ?>"
                                           class="btn btn-sm btn-outline-primary">Edit</a>
                                        <?= tombol_hapus($m['id'], 'Hapus model ' . $m['nama_model'] . '?') ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
