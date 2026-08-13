<?php
/**
 * admin/template_desain.php
 * Pengelolaan template desain tas yang dapat dipilih pelanggan
 * pada fitur custom order.
 */

require_once __DIR__ . '/../includes/init.php';

wajib_admin();

$error  = '';
$editId = (int) ($_GET['edit'] ?? 0);

$data = [
    'nama_desain' => '',
    'keterangan'  => '',
    'file_desain' => null,
    'status'      => 'aktif',
];

if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM template_desain WHERE id = ?");
    $stmt->execute([$editId]);
    $lama = $stmt->fetch();

    if (!$lama) {
        set_flash('danger', 'Template desain tidak ditemukan.');
        redirect(BASE_URL . '/admin/template_desain.php');
    }
    $data = $lama;
}

// --- Hapus --------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    hapus_master($pdo, 'desain', (int) ($_POST['id'] ?? 0));
    redirect(BASE_URL . '/admin/template_desain.php');
}

// --- Simpan tambah atau edit -------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'simpan') {
    $id = (int) ($_POST['id'] ?? 0);

    $data['nama_desain'] = trim($_POST['nama_desain'] ?? '');
    $data['keterangan']  = trim($_POST['keterangan'] ?? '');
    $data['status']      = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';

    if ($data['nama_desain'] === '') {
        $error = 'Nama desain wajib diisi.';
    }

    $namaFile = $data['file_desain'];

    if ($error === '') {
        [$namaFile, $error] = ganti_file_form(
            $_FILES['file_desain'] ?? null, 'desain', $data['file_desain'],
            'File desain', 'JPG, PNG, WEBP, atau PDF'
        );
    }

    // File desain wajib ada saat menambah data baru.
    if ($error === '' && $id === 0 && !$namaFile) {
        $error = 'File desain wajib diunggah.';
    }

    if ($error === '') {
        if ($id > 0) {
            $stmt = $pdo->prepare(
                "UPDATE template_desain
                 SET nama_desain = ?, keterangan = ?, file_desain = ?, status = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                $data['nama_desain'], $data['keterangan'], $namaFile, $data['status'], $id,
            ]);
            set_flash('success', 'Template desain berhasil diperbarui.');
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO template_desain (nama_desain, keterangan, file_desain, status)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([
                $data['nama_desain'], $data['keterangan'], $namaFile, $data['status'],
            ]);
            set_flash('success', 'Template desain berhasil ditambahkan.');
        }

        redirect(BASE_URL . '/admin/template_desain.php');
    }
}

$daftar = $pdo->query("SELECT * FROM template_desain ORDER BY nama_desain ASC")->fetchAll();

$judulHalaman = 'Template Desain';
$menuAktif    = 'desain';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="row g-4">

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <?= $editId > 0 ? 'Edit Template Desain' : 'Tambah Template Desain' ?>
            </div>
            <div class="card-body">

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data"><?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="simpan">
                    <input type="hidden" name="id" value="<?= $editId ?>">

                    <div class="mb-3">
                        <label for="nama_desain" class="form-label">Nama Desain</label>
                        <input type="text" class="form-control" id="nama_desain" name="nama_desain"
                               value="<?= e($data['nama_desain']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="keterangan" class="form-label">
                            Keterangan <span class="text-muted">(opsional)</span>
                        </label>
                        <textarea class="form-control" id="keterangan" name="keterangan"
                                  rows="2"><?= e($data['keterangan']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="file_desain" class="form-label">
                            File Desain
                            <?php if ($editId > 0): ?>
                                <span class="text-muted">(kosongkan bila tidak diganti)</span>
                            <?php endif; ?>
                        </label>
                        <input type="file" class="form-control" id="file_desain" name="file_desain"
                               accept="image/jpeg,image/png,image/webp,application/pdf"
                               <?= $editId === 0 ? 'required' : '' ?>>
                        <div class="form-text">Format JPG, PNG, WEBP, atau PDF. Maksimal 2 MB.</div>

                        <?php if ($editId > 0 && berkas_unggahan_ada('desain', $data['file_desain'])): ?>
                            <?php $ext = strtolower(pathinfo($data['file_desain'], PATHINFO_EXTENSION)); ?>
                            <?php if ($ext === 'pdf'): ?>
                                <a href="<?= BASE_URL ?>/uploads/desain/<?= e($data['file_desain']) ?>"
                                   target="_blank" class="d-inline-block mt-2 small">Lihat file PDF</a>
                            <?php else: ?>
                                <img src="<?= BASE_URL ?>/uploads/desain/<?= e($data['file_desain']) ?>"
                                     alt="" class="rounded mt-2"
                                     style="width: 90px; height: 90px; object-fit: cover;">
                            <?php endif; ?>
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
                        <a href="<?= BASE_URL ?>/admin/template_desain.php"
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
                            <th style="width: 70px;">Pratinjau</th>
                            <th>Nama Desain</th>
                            <th class="text-center">Status</th>
                            <th class="text-end" style="min-width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($daftar)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                Belum ada template desain.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftar as $d): ?>
                            <tr>
                                <td><?= thumb_berkas('desain', $d['file_desain']) ?></td>

                                <td>
                                    <div><?= e($d['nama_desain']) ?></div>
                                    <?php if ($d['keterangan']): ?>
                                        <div class="small text-muted">
                                            <?= e(mb_strimwidth($d['keterangan'], 0, 60, '...')) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center"><?= badge_status($d['status']) ?></td>

                                <td>
                                    <div class="aksi-tabel">
                                        <a href="?edit=<?= (int) $d['id'] ?>"
                                           class="btn btn-sm btn-outline-primary">Edit</a>
                                        <?= tombol_hapus($d['id'], 'Hapus desain ' . $d['nama_desain'] . '?') ?>
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
