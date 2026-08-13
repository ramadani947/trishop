<?php
/**
 * pelanggan/pesanan_saya.php
 * Daftar pesanan milik pelanggan beserta pemantauan status.
 * Memenuhi kebutuhan fungsional nomor 15 pada Tabel 3.1.
 */

require_once __DIR__ . '/../includes/init.php';

wajib_pelanggan();

$user   = user_aktif();
$filter = $_GET['status'] ?? 'semua';

$sql = "SELECT p.*, b.status_pembayaran, b.metode_pembayaran
        FROM pesanan p
        LEFT JOIN pembayaran b ON b.pesanan_id = p.id
        WHERE p.user_id = ?";
$params = [$user['id']];

if (isset(daftar_status()[$filter])) {
    $sql .= " AND p.status = ?";
    $params[] = $filter;
}

$sql .= " ORDER BY p.tanggal_pesanan DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftarPesanan = $stmt->fetchAll();

$rincian = ambil_rincian_pesanan($pdo, $daftarPesanan);

$judulHalaman = 'Pesanan Saya';
$menuAktif    = 'pesanan';

require_once __DIR__ . '/../includes/header.php';
?>

    <h1 class="judul-halaman">Pesanan Saya<span class="sub">Pantau status setiap pesanan Anda di sini.</span></h1>

    <ul class="nav nav-pills mb-3 small">
        <?php foreach (tab_status() as $key => $label): ?>
            <li class="nav-item">
                <a class="nav-link <?= $filter === $key ? 'active' : '' ?>"
                   href="?status=<?= $key ?>"><?= e($label) ?></a>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if (empty($daftarPesanan)): ?>

        <div class="card">
            <div class="kosong">
                <div class="ikon">&#128230;</div>
                <p class="text-muted mb-3">Belum ada pesanan pada kategori ini.</p>
                <a href="<?= BASE_URL ?>/pelanggan/ready_stock.php" class="btn btn-primary">Mulai Belanja</a>
            </div>
        </div>

    <?php else: ?>

        <?php foreach ($daftarPesanan as $p): ?>
            <div class="card border-0 shadow-sm mb-3">

                <?php require __DIR__ . '/../includes/kepala_pesanan.php'; ?>

                <div class="card-body">

                    <?php $r = $rincian[$p['id']]; ?>

                    <?php if ($r['tipe'] === 'custom' && $r['data']): ?>
                        <?php $d = $r['data']; ?>
                        <div class="small">
                            <div class="mb-1">
                                <span class="text-muted">Model:</span> <?= e($d['nama_model']) ?>
                                <span class="text-muted ms-3">Bahan:</span> <?= e($d['nama_bahan']) ?>
                            </div>
                            <div class="mb-1">
                                <span class="text-muted">Desain:</span>
                                <?php if (berkas_unggahan_ada('custom', $d['file_custom_desain'])): ?>
                                    <a href="<?= BASE_URL ?>/uploads/custom/<?= e($d['file_custom_desain']) ?>"
                                       target="_blank">Custom desain</a>
                                <?php elseif ($d['template_desain_id']): ?>
                                    Template desain
                                <?php else: ?>
                                    &mdash;
                                <?php endif; ?>
                            </div>
                            <div class="text-muted">
                                <?= rupiah($d['harga_per_pcs']) ?> &times; <?= (int) $d['qty'] ?> pcs
                            </div>
                        </div>

                    <?php elseif ($r['tipe'] === 'ready'): ?>
                        <div class="small">
                            <?php foreach ($r['data'] as $item): ?>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>
                                        <?= e($item['nama_produk']) ?>
                                        <span class="text-muted">&times; <?= (int) $item['qty'] ?></span>
                                    </span>
                                    <span class="text-muted"><?= rupiah($item['subtotal']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <hr class="my-3">

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="text-muted small">Total</span>
                            <span class="fw-semibold fs-6 ms-2 text-primary">
                                <?= rupiah($p['total_harga']) ?>
                            </span>

                            <?php if ($p['metode_pembayaran']): ?>
                                <span class="text-muted small ms-3">
                                    via <?= e(strtoupper($p['metode_pembayaran'])) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($p['status'] === 'menunggu_pembayaran'): ?>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= BASE_URL ?>/pelanggan/checkout.php?pesanan=<?= (int) $p['id'] ?>"
                                   class="btn btn-sm btn-primary">Lanjutkan Pembayaran</a>

                                <!-- Pembatalan ditangani checkout.php, memakai penanganan
                                     yang sama dengan tombol pada halaman pembayaran. -->
                                <form method="post" action="<?= BASE_URL ?>/pelanggan/checkout.php"
                                      onsubmit="return confirm('Batalkan pesanan <?= e($p['kode_pesanan']) ?>? Tindakan ini tidak dapat dibatalkan.');"><?= csrf_field() ?>
                                    <input type="hidden" name="aksi" value="batalkan">
                                    <input type="hidden" name="pesanan_id" value="<?= (int) $p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Batalkan
                                    </button>
                                </form>
                            </div>
                        <?php elseif ($p['status'] === 'diproses'): ?>
                            <span class="small text-muted">Pesanan sedang dikerjakan penjual.</span>
                        <?php elseif ($p['status'] === 'selesai'): ?>
                            <span class="small text-success">Pesanan telah selesai.</span>
                        <?php else: ?>
                            <span class="small text-danger">Pesanan dibatalkan.</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($p['catatan']): ?>
                        <div class="mt-2 small text-muted">
                            Catatan: <?= e($p['catatan']) ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
