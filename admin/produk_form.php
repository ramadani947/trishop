<?php
/**
 * admin/produk_form.php
 * Form tambah dan edit produk ready stock.
 * Mode edit aktif apabila parameter id tersedia pada URL.
 */

require_once __DIR__ . '/../includes/init.php';

wajib_admin();

$id      = (int) ($_GET['id'] ?? 0);
$isEdit  = $id > 0;
$error   = '';

$data = [
    'nama_produk' => '',
    'deskripsi'   => '',
    'harga'       => '',
    'stok'        => 0,
    'gambar'      => null,
    'status'      => 'aktif',
];

// Muat data lama saat mode edit.
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM produk WHERE id = ?");
    $stmt->execute([$id]);
    $produkLama = $stmt->fetch();

    if (!$produkLama) {
        set_flash('danger', 'Produk tidak ditemukan.');
        redirect(BASE_URL . '/admin/produk.php');
    }
    $data = $produkLama;
}

// --- Proses simpan ------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['nama_produk'] = trim($_POST['nama_produk'] ?? '');
    $data['deskripsi']   = trim($_POST['deskripsi'] ?? '');
    $data['harga']       = $_POST['harga'] ?? '';
    $data['stok']        = $_POST['stok'] ?? 0;
    $data['status']      = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';

    if ($data['nama_produk'] === '') {
        $error = 'Nama produk wajib diisi.';
    } elseif (!is_numeric($data['harga']) || (float) $data['harga'] <= 0) {
        $error = 'Harga harus berupa angka lebih besar dari nol.';
    } elseif (!is_numeric($data['stok']) || (int) $data['stok'] < 0) {
        $error = 'Stok harus berupa angka nol atau lebih.';
    }

    // Proses unggah gambar bila ada file baru. Gambar lama ikut dibuang.
    $namaGambar = $data['gambar'];

    if ($error === '') {
        [$namaGambar, $error] = ganti_file_form($_FILES['gambar'] ?? null, 'produk', $data['gambar']);
    }

    if ($error === '') {
        if ($isEdit) {
            $stmt = $pdo->prepare(
                "UPDATE produk
                 SET nama_produk = ?, deskripsi = ?, harga = ?, stok = ?, gambar = ?, status = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                $data['nama_produk'], $data['deskripsi'], $data['harga'],
                $data['stok'], $namaGambar, $data['status'], $id,
            ]);
            set_flash('success', 'Produk berhasil diperbarui.');
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO produk (nama_produk, deskripsi, harga, stok, gambar, status)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $data['nama_produk'], $data['deskripsi'], $data['harga'],
                $data['stok'], $namaGambar, $data['status'],
            ]);
            set_flash('success', 'Produk berhasil ditambahkan.');
        }

        redirect(BASE_URL . '/admin/produk.php');
    }
}

$judulHalaman = $isEdit ? 'Edit Produk' : 'Tambah Produk';
$menuAktif    = 'produk';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data"><?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="nama_produk" class="form-label">Nama Produk</label>
                        <input type="text" class="form-control" id="nama_produk" name="nama_produk"
                               value="<?= e($data['nama_produk']) ?>" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">
                            Deskripsi <span class="text-muted">(opsional)</span>
                        </label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi"
                                  rows="3"><?= e($data['deskripsi']) ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 mb-3">
                            <label for="harga" class="form-label">Harga (Rp)</label>
                            <input type="number" class="form-control" id="harga" name="harga"
                                   min="1" step="1" value="<?= e($data['harga']) ?>" required>
                        </div>

                        <div class="col-sm-6 mb-3">
                            <label for="stok" class="form-label">Stok</label>
                            <input type="number" class="form-control" id="stok" name="stok"
                                   min="0" value="<?= e($data['stok']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="gambar" class="form-label">
                            Gambar Produk
                            <?php if ($isEdit): ?>
                                <span class="text-muted">(kosongkan bila tidak diganti)</span>
                            <?php endif; ?>
                        </label>
                        <input type="file" class="form-control" id="gambar" name="gambar"
                               accept="image/jpeg,image/png,image/webp">
                        <div class="form-text">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</div>

                        <?php if ($isEdit && $data['gambar']): ?>
                            <img src="<?= BASE_URL ?>/uploads/produk/<?= e($data['gambar']) ?>"
                                 alt="" class="rounded mt-2"
                                 style="width: 120px; height: 120px; object-fit: cover;">
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="aktif"    <?= $data['status'] === 'aktif'    ? 'selected' : '' ?>>Aktif (tampil di katalog)</option>
                            <option value="nonaktif" <?= $data['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif (disembunyikan)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Produk' ?>
                    </button>
                    <a href="<?= BASE_URL ?>/admin/produk.php" class="btn btn-outline-secondary">Batal</a>

                </form>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
