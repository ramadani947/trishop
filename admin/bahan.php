<?php
/**
 * admin/bahan.php
 * Pengelolaan data bahan tas beserta harga dasar per pcs.
 * Harga bahan menjadi komponen utama rumus kalkulasi harga custom order.
 */

require_once __DIR__ . '/../includes/init.php';

wajib_admin();

$error  = '';
$editId = (int) ($_GET['edit'] ?? 0);

$data = [
    'nama_bahan'  => '',
    'deskripsi'   => '',
    'harga_bahan' => '',
    'gambar'      => null,
    'status'      => 'aktif',
];

if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM bahan WHERE id = ?");
    $stmt->execute([$editId]);
    $lama = $stmt->fetch();

    if (!$lama) {
        set_flash('danger', 'Bahan tidak ditemukan.');
        redirect(BASE_URL . '/admin/bahan.php');
    }
    $data = $lama;
}

// --- Hapus --------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    hapus_master($pdo, 'bahan', (int) ($_POST['id'] ?? 0));
    redirect(BASE_URL . '/admin/bahan.php');
}

// --- Simpan tambah atau edit -------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'simpan') {
    $id = (int) ($_POST['id'] ?? 0);

    $data['nama_bahan']  = trim($_POST['nama_bahan'] ?? '');
    $data['deskripsi']   = trim($_POST['deskripsi'] ?? '');
    $data['harga_bahan'] = $_POST['harga_bahan'] ?? '';
    $data['status']      = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';

    if ($data['nama_bahan'] === '') {
        $error = 'Nama bahan wajib diisi.';
    } elseif (!is_numeric($data['harga_bahan']) || (float) $data['harga_bahan'] <= 0) {
        $error = 'Harga bahan harus berupa angka lebih besar dari nol.';
    }

    $namaGambar = $data['gambar'];

    if ($error === '') {
        [$namaGambar, $error] = ganti_file_form($_FILES['gambar'] ?? null, 'produk', $data['gambar']);
    }

    if ($error === '') {
        if ($id > 0) {
            $stmt = $pdo->prepare(
                "UPDATE bahan
                 SET nama_bahan = ?, deskripsi = ?, harga_bahan = ?, gambar = ?, status = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                $data['nama_bahan'], $data['deskripsi'], $data['harga_bahan'],
                $namaGambar, $data['status'], $id,
            ]);
            set_flash('success', 'Bahan berhasil diperbarui.');
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO bahan (nama_bahan, deskripsi, harga_bahan, gambar, status)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $data['nama_bahan'], $data['deskripsi'], $data['harga_bahan'],
                $namaGambar, $data['status'],
            ]);
            set_flash('success', 'Bahan berhasil ditambahkan.');
        }

        redirect(BASE_URL . '/admin/bahan.php');
    }
}

$daftar = $pdo->query("SELECT * FROM bahan ORDER BY nama_bahan ASC")->fetchAll();

// Referensi untuk membantu admin memperkirakan harga akhir.
$modelAktif = $pdo->query(
    "SELECT nama_model, faktor_pengali FROM model_tas WHERE status = 'aktif' ORDER BY nama_model ASC"
)->fetchAll();

$judulHalaman = 'Bahan';
$menuAktif    = 'bahan';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="row g-4">

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <?= $editId > 0 ? 'Edit Bahan' : 'Tambah Bahan' ?>
            </div>
            <div class="card-body">

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="aksi" value="simpan">
                    <input type="hidden" name="id" value="<?= $editId ?>">

                    <div class="mb-3">
                        <label for="nama_bahan" class="form-label">Nama Bahan</label>
                        <input type="text" class="form-control" id="nama_bahan" name="nama_bahan"
                               value="<?= e($data['nama_bahan']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="harga_bahan" class="form-label">Harga Dasar per Pcs (Rp)</label>
                        <input type="number" class="form-control" id="harga_bahan" name="harga_bahan"
                               min="1" step="1" value="<?= e($data['harga_bahan']) ?>" required>
                        <div class="form-text">
                            Harga sebelum dikalikan faktor pengali model tas.
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
                        <a href="<?= BASE_URL ?>/admin/bahan.php"
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
                            <th>Nama Bahan</th>
                            <th class="text-end">Harga Dasar</th>
                            <th class="text-center">Status</th>
                            <th class="text-end" style="min-width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($daftar)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Belum ada data bahan.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftar as $b): ?>
                            <tr>
                                <td><?= thumb_berkas('produk', $b['gambar']) ?></td>

                                <td>
                                    <div><?= e($b['nama_bahan']) ?></div>
                                    <?php if ($b['deskripsi']): ?>
                                        <div class="small text-muted">
                                            <?= e(mb_strimwidth($b['deskripsi'], 0, 50, '...')) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end"><?= rupiah($b['harga_bahan']) ?></td>

                                <td class="text-center"><?= badge_status($b['status']) ?></td>

                                <td>
                                    <div class="aksi-tabel">
                                        <a href="?edit=<?= (int) $b['id'] ?>"
                                           class="btn btn-sm btn-outline-primary">Edit</a>
                                        <?= tombol_hapus($b['id'], 'Hapus bahan ' . $b['nama_bahan'] . '?') ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!empty($daftar) && !empty($modelAktif)): ?>
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white">
                    Simulasi Harga per Pcs
                    <span class="text-muted small">(harga bahan &times; faktor pengali model)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 text-center">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">Bahan</th>
                                <?php foreach ($modelAktif as $m): ?>
                                    <th><?= e($m['nama_model']) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daftar as $b): ?>
                                <?php if ($b['status'] !== 'aktif') continue; ?>
                                <tr>
                                    <td class="text-start"><?= e($b['nama_bahan']) ?></td>
                                    <?php foreach ($modelAktif as $m): ?>
                                        <td>
                                            <?= rupiah(hitung_harga_per_pcs($b['harga_bahan'], $m['faktor_pengali'])) ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
