<?php
/**
 * admin/pesanan.php
 * Pengelolaan pesanan oleh admin.
 * Implementasi Activity Diagram Pengelolaan Pesanan (Gambar 3.6):
 * admin melihat pesanan berstatus Diproses, mengerjakannya,
 * lalu memperbarui status menjadi Selesai.
 */

require_once __DIR__ . '/../includes/init.php';

wajib_admin();

// --- Perbarui status pesanan -------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'ubah_status') {
    $id          = (int) ($_POST['id'] ?? 0);
    $statusBaru  = $_POST['status'] ?? '';

    // Konfirmasi pembayaran BUKAN wewenang admin. Status lunas hanya boleh
    // datang dari Midtrans, lewat webhook payment/callback.php atau
    // pengecekan aktif payment/cek_status.php. Admin hanya menuntaskan
    // pesanan yang pengerjaannya selesai, atau membatalkan pesanan.
    if (!in_array($statusBaru, ['selesai', 'canceled'], true)) {
        set_flash('danger', 'Status pesanan tidak dikenali.');
        redirect(BASE_URL . '/admin/pesanan.php');
    }

    $stmt = $pdo->prepare("SELECT status, kode_pesanan, jenis_pesanan FROM pesanan WHERE id = ?");
    $stmt->execute([$id]);
    $pesanan = $stmt->fetch();

    if (!$pesanan) {
        set_flash('danger', 'Pesanan tidak ditemukan.');
        redirect(BASE_URL . '/admin/pesanan.php');
    }

    // Perpindahan status dibatasi pada yang memang masuk akal. Sebelumnya
    // hanya pesanan berstatus menunggu_pembayaran yang dicegah diselesaikan,
    // sehingga pesanan yang SUDAH DIBATALKAN masih bisa ditandai selesai lewat
    // kiriman POST. Pesanan seperti itu lalu ikut terhitung sebagai pendapatan
    // pada Laporan Penjualan Periodik, yang menyaring status diproses+selesai.
    if ($statusBaru === 'selesai' && $pesanan['status'] !== 'diproses') {
        set_flash('warning', 'Hanya pesanan yang sedang Diproses yang dapat ditandai selesai.');
        redirect(BASE_URL . '/admin/pesanan.php');
    }

    // Begitu pula sebaliknya: yang sudah selesai atau sudah batal tidak perlu
    // dibatalkan lagi. Tanpa penjagaan ini stok ready stock bisa dikembalikan
    // dua kali untuk satu pesanan yang sama.
    if ($statusBaru === 'canceled'
        && !in_array($pesanan['status'], ['menunggu_pembayaran', 'diproses'], true)) {
        set_flash('warning', 'Pesanan ini sudah tidak dapat dibatalkan.');
        redirect(BASE_URL . '/admin/pesanan.php');
    }

    try {
        $pdo->beginTransaction();

        $pdo->prepare("UPDATE pesanan SET status = ? WHERE id = ?")->execute([$statusBaru, $id]);

        // Pengurangan stok tidak ditangani di sini. Stok ready stock berkurang
        // saat Midtrans memastikan pembayaran berhasil, di dalam
        // midtrans_terapkan_status() pada includes/midtrans.php.
        //
        // Stok dikembalikan apabila pesanan yang sudah dibayar dibatalkan.
        if (pesanan_sudah_dibayar($pesanan['status'])
            && $statusBaru === 'canceled'
            && $pesanan['jenis_pesanan'] === 'ready_stock') {

            $detail = $pdo->prepare("SELECT produk_id, qty FROM detail_pesanan WHERE pesanan_id = ?");
            $detail->execute([$id]);

            $tambah = $pdo->prepare("UPDATE produk SET stok = stok + ? WHERE id = ?");

            foreach ($detail->fetchAll() as $item) {
                $tambah->execute([$item['qty'], $item['produk_id']]);
            }
        }

        $pdo->commit();

    } catch (PDOException $ex) {
        $pdo->rollBack();
        set_flash('danger', 'Status pesanan gagal diperbarui.');
        redirect(BASE_URL . '/admin/pesanan.php');
    }

    set_flash('success', 'Status pesanan ' . $pesanan['kode_pesanan'] . ' diperbarui menjadi ' . label_status($statusBaru) . '.');
    redirect(BASE_URL . '/admin/pesanan.php?status=' . urlencode($_POST['filter'] ?? 'semua'));
}

// --- Ambil daftar pesanan ----------------------------------------------

$filter = $_GET['status'] ?? 'semua';

$sql = "SELECT p.*, u.nama, u.email, u.no_hp,
               b.status_pembayaran, b.metode_pembayaran
        FROM pesanan p
        JOIN users u ON u.id = p.user_id
        LEFT JOIN pembayaran b ON b.pesanan_id = p.id";
$params = [];

if (isset(daftar_status()[$filter])) {
    $sql .= " WHERE p.status = ?";
    $params[] = $filter;
}

$sql .= " ORDER BY p.tanggal_pesanan DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftarPesanan = $stmt->fetchAll();

$rincian = ambil_rincian_pesanan($pdo, $daftarPesanan);

// Hitung jumlah per status untuk label tab.
$jumlahStatus = $pdo->query(
    "SELECT status, COUNT(*) FROM pesanan GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$judulHalaman = 'Pesanan';
$menuAktif    = 'pesanan';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<ul class="nav nav-pills mb-3 small">
    <?php foreach (tab_status() as $key => $label): ?>
        <li class="nav-item">
            <a class="nav-link <?= $filter === $key ? 'active' : '' ?>" href="?status=<?= $key ?>">
                <?= e($label) ?>
                <?php if ($key !== 'semua' && !empty($jumlahStatus[$key])): ?>
                    <span class="badge bg-light text-dark ms-1"><?= (int) $jumlahStatus[$key] ?></span>
                <?php endif; ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<?php if (empty($daftarPesanan)): ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body text-center text-muted py-5">
            Belum ada pesanan pada kategori ini.
        </div>
    </div>

<?php else: ?>

    <?php foreach ($daftarPesanan as $p): ?>
        <div class="card border-0 shadow-sm mb-3">

            <?php require __DIR__ . '/../includes/kepala_pesanan.php'; ?>

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-4">
                        <div class="small text-muted mb-1">Pelanggan</div>
                        <div class="small">
                            <div><?= e($p['nama']) ?></div>
                            <div class="text-muted teks-panjang"><?= e($p['email']) ?></div>
                            <?php if ($p['no_hp']): ?>
                                <div class="text-muted"><?= e($p['no_hp']) ?></div>
                            <?php endif; ?>
                        </div>

                        <?php if ($p['status_pembayaran']): ?>
                            <div class="small text-muted mt-2">
                                Pembayaran: <?= e($p['status_pembayaran']) ?>
                                <?php if ($p['metode_pembayaran']): ?>
                                    (<?= e(strtoupper($p['metode_pembayaran'])) ?>)
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-8">
                        <div class="small text-muted mb-1">Rincian Pesanan</div>

                        <?php $r = $rincian[$p['id']]; ?>

                        <?php if ($r['tipe'] === 'custom' && $r['data']): ?>
                            <?php $d = $r['data']; ?>
                            <div class="table-responsive">
                            <table class="table table-sm mb-0 small">
                                <tr>
                                    <td class="text-muted" style="min-width: 110px;">Model tas</td>
                                    <td><?= e($d['nama_model']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Bahan</td>
                                    <td><?= e($d['nama_bahan']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Desain</td>
                                    <td>
                                        <?php if (berkas_unggahan_ada('custom', $d['file_custom_desain'])): ?>
                                            <a href="<?= BASE_URL ?>/uploads/custom/<?= e($d['file_custom_desain']) ?>"
                                               target="_blank">Unduh custom desain pelanggan</a>
                                        <?php elseif ($d['nama_desain']): ?>
                                            <!-- Nama dan berkasnya ikut dibawa ambil_rincian_pesanan().
                                                 Ditautkan hanya bila berkasnya benar-benar ada; kalau
                                                 tidak, namanya tetap ditampilkan agar admin masih tahu
                                                 desain mana yang dipesan. -->
                                            <?php if (berkas_unggahan_ada('desain', $d['file_desain'])): ?>
                                                <a href="<?= BASE_URL ?>/uploads/desain/<?= e($d['file_desain']) ?>"
                                                   target="_blank"><?= e($d['nama_desain']) ?></a>
                                            <?php else: ?>
                                                <?= e($d['nama_desain']) ?>
                                                <span class="text-muted">(berkas desain tidak ditemukan)</span>
                                            <?php endif; ?>
                                        <?php elseif ($d['template_desain_id']): ?>
                                            Template desain
                                        <?php else: ?>
                                            &mdash;
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Perhitungan</td>
                                    <td>
                                        <?= rupiah($d['harga_bahan']) ?> &times;
                                        <?= number_format((float) $d['faktor_pengali'], 2, ',', '.') ?>
                                        = <?= rupiah($d['harga_per_pcs']) ?> / pcs
                                        &times; <?= (int) $d['qty'] ?> pcs
                                    </td>
                                </tr>
                                <?php if ($d['catatan_desain']): ?>
                                    <tr>
                                        <td class="text-muted">Catatan</td>
                                        <td><?= e($d['catatan_desain']) ?></td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                            </div>

                        <?php elseif ($r['tipe'] === 'ready'): ?>
                            <div class="table-responsive">
                            <table class="table table-sm mb-0 small">
                                <?php foreach ($r['data'] as $item): ?>
                                    <tr>
                                        <td><?= e($item['nama_produk']) ?></td>
                                        <td class="text-center" style="width: 70px;">
                                            &times; <?= (int) $item['qty'] ?>
                                        </td>
                                        <td class="text-end" style="width: 120px;">
                                            <?= rupiah($item['subtotal']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                            </div>
                        <?php endif; ?>

                        <?php if ($p['catatan']): ?>
                            <div class="small text-muted mt-2">
                                Catatan pelanggan: <?= e($p['catatan']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

                <hr class="my-3">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="text-muted small">Total</span>
                        <span class="fw-semibold ms-2 text-primary"><?= rupiah($p['total_harga']) ?></span>
                    </div>

                    <div class="d-flex gap-2">
                        <?php if ($p['status'] === 'diproses'): ?>
                            <form method="post"
                                  onsubmit="return confirm('Tandai pesanan ini sebagai Selesai?');"><?= csrf_field() ?>
                                <input type="hidden" name="aksi" value="ubah_status">
                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                <input type="hidden" name="filter" value="<?= e($filter) ?>">
                                <input type="hidden" name="status" value="selesai">
                                <button class="btn btn-sm btn-success">Tandai Selesai</button>
                            </form>

                        <?php elseif ($p['status'] === 'menunggu_pembayaran'): ?>
                            <span class="small text-muted align-self-center">
                                Menunggu konfirmasi pembayaran dari Midtrans.
                            </span>

                            <form method="post"
                                  onsubmit="return confirm('Batalkan pesanan ini?');"><?= csrf_field() ?>
                                <input type="hidden" name="aksi" value="ubah_status">
                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                <input type="hidden" name="filter" value="<?= e($filter) ?>">
                                <input type="hidden" name="status" value="canceled">
                                <button class="btn btn-sm btn-outline-danger">Batalkan</button>
                            </form>

                        <?php elseif ($p['status'] === 'selesai'): ?>
                            <span class="small text-success align-self-center">
                                Pesanan telah diselesaikan.
                            </span>

                        <?php else: ?>
                            <span class="small text-danger align-self-center">
                                Pesanan dibatalkan.
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    <?php endforeach; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
