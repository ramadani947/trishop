<?php
/**
 * pelanggan/buat_custom_order.php
 * Formulir pemesanan custom order.
 *
 * Halaman ini langkah KEDUA. Pintu masuknya pelanggan/custom_order.php,
 * yang menjelaskan alurnya lebih dulu lalu mengantar ke sini.
 *
 * Pelanggan memilih template model tas, bahan, serta menentukan desain
 * (memilih template desain atau mengunggah custom desain). Harga dihitung
 * otomatis oleh sistem menggunakan rumus:
 *   harga per pcs = harga bahan x faktor pengali model tas
 *   total harga   = harga per pcs x jumlah pesanan
 */

require_once __DIR__ . '/../includes/init.php';

wajib_pelanggan();

$user  = user_aktif();
$error = '';

$daftarModel  = $pdo->query("SELECT id, nama_model, deskripsi, faktor_pengali, gambar FROM model_tas WHERE status = 'aktif' ORDER BY nama_model ASC")->fetchAll();
$daftarBahan  = $pdo->query("SELECT id, nama_bahan, deskripsi, harga_bahan, gambar FROM bahan WHERE status = 'aktif' ORDER BY nama_bahan ASC")->fetchAll();
$daftarDesain = $pdo->query("SELECT id, nama_desain, keterangan, file_desain FROM template_desain WHERE status = 'aktif' ORDER BY nama_desain ASC")->fetchAll();

// Nilai form yang dipertahankan bila validasi gagal.
$pilihan = [
    'model_id'      => (int) ($_POST['model_id'] ?? 0),
    'bahan_id'      => (int) ($_POST['bahan_id'] ?? 0),
    'sumber_desain' => $_POST['sumber_desain'] ?? 'template',
    'desain_id'     => (int) ($_POST['desain_id'] ?? 0),
    'qty'           => (int) ($_POST['qty'] ?? MIN_QTY_CUSTOM),
    'catatan'       => trim($_POST['catatan'] ?? ''),
];

// --- Proses simpan pesanan custom --------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil ulang data master dari database sebagai sumber harga yang sah.
    $stmt = $pdo->prepare("SELECT * FROM model_tas WHERE id = ? AND status = 'aktif'");
    $stmt->execute([$pilihan['model_id']]);
    $model = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT * FROM bahan WHERE id = ? AND status = 'aktif'");
    $stmt->execute([$pilihan['bahan_id']]);
    $bahan = $stmt->fetch();

    $desain    = null;
    $fileKustom = null;

    if (!$model) {
        $error = 'Template model tas belum dipilih atau tidak tersedia.';
    } elseif (!$bahan) {
        $error = 'Bahan belum dipilih atau tidak tersedia.';
    } elseif ($pilihan['qty'] < MIN_QTY_CUSTOM) {
        $error = 'Jumlah pesanan minimal ' . MIN_QTY_CUSTOM . ' pcs.';
    }

    // Validasi pilihan desain.
    if ($error === '') {
        if ($pilihan['sumber_desain'] === 'template') {
            $stmt = $pdo->prepare("SELECT * FROM template_desain WHERE id = ? AND status = 'aktif'");
            $stmt->execute([$pilihan['desain_id']]);
            $desain = $stmt->fetch();

            if (!$desain) {
                $error = 'Template desain belum dipilih atau tidak tersedia.';
            }
        } else {
            if (!isset($_FILES['file_desain']) || $_FILES['file_desain']['error'] !== UPLOAD_ERR_OK) {
                $error = 'File custom desain belum diunggah.';
            } else {
                $fileKustom = upload_file($_FILES['file_desain'], 'custom');

                if ($fileKustom === false) {
                    $error = 'File desain gagal diunggah. Gunakan format JPG, PNG, WEBP, atau PDF maksimal 2 MB.';
                }
            }
        }
    }

    // Perhitungan harga dilakukan di sisi server berdasarkan data database.
    if ($error === '') {
        $hargaPerPcs = hitung_harga_per_pcs($bahan['harga_bahan'], $model['faktor_pengali']);
        $totalHarga  = hitung_total_custom($hargaPerPcs, $pilihan['qty']);

        try {
            $pdo->beginTransaction();

            $kode = generate_kode_pesanan($pdo);

            $stmt = $pdo->prepare(
                "INSERT INTO pesanan (kode_pesanan, user_id, jenis_pesanan, total_harga, status, catatan)
                 VALUES (?, ?, 'custom', ?, 'menunggu_pembayaran', ?)"
            );
            $stmt->execute([$kode, $user['id'], $totalHarga, $pilihan['catatan']]);

            $pesananId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                "INSERT INTO detail_custom
                    (pesanan_id, model_id, bahan_id, template_desain_id, file_custom_desain,
                     nama_model, nama_bahan, harga_bahan, faktor_pengali,
                     harga_per_pcs, qty, subtotal, catatan_desain)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $pesananId,
                $model['id'],
                $bahan['id'],
                $desain ? $desain['id'] : null,
                $fileKustom,
                $model['nama_model'],
                $bahan['nama_bahan'],
                $bahan['harga_bahan'],
                $model['faktor_pengali'],
                $hargaPerPcs,
                $pilihan['qty'],
                $totalHarga,
                $pilihan['catatan'],
            ]);

            $pdo->commit();

            set_flash('success', 'Pesanan custom berhasil dibuat. Silakan lanjutkan pembayaran.');
            redirect(BASE_URL . '/pelanggan/checkout.php?pesanan=' . $pesananId);

        } catch (PDOException $ex) {
            $pdo->rollBack();

            // Berkas desain sudah terlanjur tersimpan sebelum transaksi dimulai.
            // Tanpa langkah ini ia menetap di uploads/custom/ tanpa pernah
            // dirujuk baris mana pun, karena pesanannya batal tercatat.
            hapus_file('custom', $fileKustom);

            $error = 'Pesanan gagal disimpan. Silakan coba lagi.';
        }
    }
}

$judulHalaman = 'Custom Order';
$menuAktif    = 'custom';

require_once __DIR__ . '/../includes/header.php';
?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <a href="<?= BASE_URL ?>/pelanggan/custom_order.php" class="tautan-kembali">&larr; Cara memesan</a>

    <h1 class="judul-halaman mt-3">
        Buat Custom Order
        <span class="sub">
            Pilih model tas, bahan, dan desain. Harga dihitung otomatis oleh sistem.
            Minimum pemesanan <?= MIN_QTY_CUSTOM ?> pcs, dan satu pesanan berlaku untuk satu jenis tas.
        </span>
    </h1>

    <?php if (empty($daftarModel) || empty($daftarBahan)): ?>

        <div class="alert alert-warning">
            Layanan custom order belum tersedia karena data model tas atau bahan belum lengkap.
        </div>

    <?php else: ?>

    <form method="post" enctype="multipart/form-data">
        <div class="row g-4">

            <div class="col-lg-8">

                <!-- Langkah 1: model tas -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">1. Pilih Template Model Tas</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php foreach ($daftarModel as $m): ?>
                                <div class="col-md-4 col-sm-6">
                                    <label class="card h-100 opsi-card border">
                                        <input type="radio" name="model_id" value="<?= (int) $m['id'] ?>"
                                               data-faktor="<?= (float) $m['faktor_pengali'] ?>"
                                               data-nama="<?= e($m['nama_model']) ?>"
                                               <?= $pilihan['model_id'] === (int) $m['id'] ? 'checked' : '' ?> required>

                                        <?php if ($m['gambar']): ?>
                                            <img src="<?= BASE_URL ?>/uploads/produk/<?= e($m['gambar']) ?>"
                                                 alt="" class="card-img-top"
                                                 style="height: 120px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-secondary-subtle" style="height: 120px;"></div>
                                        <?php endif; ?>

                                        <div class="card-body p-2">
                                            <div class="small fw-semibold">
                                                <?= e($m['nama_model']) ?>
                                                <span class="opsi-cek text-primary">&check;</span>
                                            </div>
                                            <div class="text-muted" style="font-size: 12px;">
                                                Pengali &times; <?= number_format((float) $m['faktor_pengali'], 2, ',', '.') ?>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Langkah 2: bahan -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">2. Pilih Bahan</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php foreach ($daftarBahan as $b): ?>
                                <div class="col-md-4 col-sm-6">
                                    <label class="card h-100 opsi-card border">
                                        <input type="radio" name="bahan_id" value="<?= (int) $b['id'] ?>"
                                               data-harga="<?= (float) $b['harga_bahan'] ?>"
                                               data-nama="<?= e($b['nama_bahan']) ?>"
                                               <?= $pilihan['bahan_id'] === (int) $b['id'] ? 'checked' : '' ?> required>

                                        <?php if ($b['gambar']): ?>
                                            <img src="<?= BASE_URL ?>/uploads/produk/<?= e($b['gambar']) ?>"
                                                 alt="" class="card-img-top"
                                                 style="height: 120px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-secondary-subtle" style="height: 120px;"></div>
                                        <?php endif; ?>

                                        <div class="card-body p-2">
                                            <div class="small fw-semibold">
                                                <?= e($b['nama_bahan']) ?>
                                                <span class="opsi-cek text-primary">&check;</span>
                                            </div>
                                            <div class="text-muted" style="font-size: 12px;">
                                                <?= rupiah($b['harga_bahan']) ?> / pcs
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Langkah 3: desain -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">3. Tentukan Desain</div>
                    <div class="card-body">

                        <div class="btn-group mb-3" role="group">
                            <input type="radio" class="btn-check" name="sumber_desain" id="sd-template"
                                   value="template" <?= $pilihan['sumber_desain'] !== 'upload' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="sd-template">Pilih Template Desain</label>

                            <input type="radio" class="btn-check" name="sumber_desain" id="sd-upload"
                                   value="upload" <?= $pilihan['sumber_desain'] === 'upload' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="sd-upload">Unggah Custom Desain</label>
                        </div>

                        <div id="area-template">
                            <?php if (empty($daftarDesain)): ?>
                                <div class="alert alert-secondary small mb-0">
                                    Belum ada template desain. Silakan unggah custom desain.
                                </div>
                            <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach ($daftarDesain as $d): ?>
                                        <?php $ext = strtolower(pathinfo((string) $d['file_desain'], PATHINFO_EXTENSION)); ?>
                                        <div class="col-md-3 col-sm-6">
                                            <label class="card h-100 opsi-card border">
                                                <input type="radio" name="desain_id" value="<?= (int) $d['id'] ?>"
                                                       <?= $pilihan['desain_id'] === (int) $d['id'] ? 'checked' : '' ?>>

                                                <?php if ($ext === 'pdf'): ?>
                                                    <div class="bg-danger-subtle text-danger-emphasis d-flex align-items-center justify-content-center small"
                                                         style="height: 110px;">PDF</div>
                                                <?php else: ?>
                                                    <img src="<?= BASE_URL ?>/uploads/desain/<?= e($d['file_desain']) ?>"
                                                         alt="" class="card-img-top"
                                                         style="height: 110px; object-fit: cover;">
                                                <?php endif; ?>

                                                <div class="card-body p-2">
                                                    <div class="small">
                                                        <?= e($d['nama_desain']) ?>
                                                        <span class="opsi-cek text-primary">&check;</span>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div id="area-upload" class="d-none">
                            <label for="file_desain" class="form-label">File Custom Desain</label>
                            <input type="file" class="form-control" id="file_desain" name="file_desain"
                                   accept="image/jpeg,image/png,image/webp,application/pdf">
                            <div class="form-text">Format JPG, PNG, WEBP, atau PDF. Maksimal 2 MB.</div>
                        </div>

                    </div>
                </div>

                <!-- Langkah 4: jumlah dan catatan -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">4. Jumlah Pesanan</div>
                    <div class="card-body">

                        <div class="mb-3" style="max-width: 220px;">
                            <label for="qty" class="form-label">Jumlah (pcs)</label>
                            <input type="number" class="form-control" id="qty" name="qty"
                                   min="<?= MIN_QTY_CUSTOM ?>" step="1"
                                   value="<?= max($pilihan['qty'], MIN_QTY_CUSTOM) ?>" required>
                            <div class="form-text">Minimum <?= MIN_QTY_CUSTOM ?> pcs.</div>
                        </div>

                        <div class="mb-0">
                            <label for="catatan" class="form-label">
                                Catatan untuk Penjual <span class="text-muted">(opsional)</span>
                            </label>
                            <textarea class="form-control" id="catatan" name="catatan"
                                      rows="3" placeholder="Contoh: warna tali hitam, sablon di sisi depan"><?= e($pilihan['catatan']) ?></textarea>
                        </div>

                    </div>
                </div>

            </div>

            <!-- Ringkasan harga -->
            <div class="col-lg-4" id="kolom-ringkasan">
                <div class="card border-0 shadow-sm" id="ringkasan-harga">
                    <div class="card-header bg-white">Ringkasan Harga</div>
                    <div class="card-body">

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Model tas</span>
                            <span class="small text-end" id="r-model">&mdash;</span>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Bahan</span>
                            <span class="small text-end" id="r-bahan">&mdash;</span>
                        </div>

                        <hr>

                        <div class="small text-muted mb-1">Perhitungan</div>
                        <div class="small mb-3" id="r-rumus">
                            Pilih model tas dan bahan terlebih dahulu.
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Harga per pcs</span>
                            <span id="r-perpcs">&mdash;</span>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted small">Jumlah</span>
                            <span id="r-qty"><?= max($pilihan['qty'], MIN_QTY_CUSTOM) ?> pcs</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-semibold">Total</span>
                            <span class="fw-semibold fs-5 text-primary" id="r-total">Rp0</span>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Buat Pesanan</button>

                        <p class="text-muted mt-3 mb-0" style="font-size: 12px;">
                            Harga akan dihitung ulang oleh sistem saat pesanan disimpan.
                        </p>

                    </div>
                </div>
            </div>

        </div>
    </form>

<script src="<?= aset("assets/js/custom_order.js") ?>"></script>

    <?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
