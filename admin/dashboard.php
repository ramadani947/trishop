<?php
/**
 * admin/dashboard.php
 * Halaman ringkasan bagi admin: statistik singkat dan daftar pesanan terbaru.
 */

require_once __DIR__ . '/../includes/init.php';

wajib_admin();

// --- Statistik ringkas -------------------------------------------------
// Keempat angka diambil sekaligus: dua yang berbasis bulan berjalan dihitung
// dari klausa WHERE di bawah, dua sisanya dari subkueri.

$statistik = $pdo->query(
    "SELECT (SELECT COUNT(*) FROM produk  WHERE status = 'aktif')    AS produk_aktif,
            (SELECT COUNT(*) FROM pesanan WHERE status = 'diproses') AS perlu_diproses,
            COUNT(*)                             AS transaksi_bulan_ini,
            COALESCE(SUM(total_harga), 0)        AS pendapatan_bulan_ini
     FROM pesanan
     WHERE status IN ('diproses','selesai')
       AND MONTH(tanggal_pesanan) = MONTH(CURDATE())
       AND YEAR(tanggal_pesanan)  = YEAR(CURDATE())"
)->fetch();

// --- Sepuluh pesanan terbaru -------------------------------------------

$pesananTerbaru = $pdo->query(
    "SELECT p.kode_pesanan, p.jenis_pesanan, p.total_harga,
            p.status, p.tanggal_pesanan, u.nama
     FROM pesanan p
     JOIN users u ON u.id = p.user_id
     ORDER BY p.tanggal_pesanan DESC
     LIMIT 10"
)->fetchAll();

$judulHalaman = 'Dashboard';
$menuAktif    = 'dashboard';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="row g-3 mb-4">

    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Produk Aktif</div>
                <div class="fs-3"><?= (int) $statistik['produk_aktif'] ?></div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Pesanan Perlu Diproses</div>
                <div class="fs-3"><?= (int) $statistik['perlu_diproses'] ?></div>
                <!-- Hanya custom order yang singgah di status ini. Pesanan
                     ready stock langsung selesai begitu pembayaran masuk. -->
                <div class="text-muted" style="font-size: 11px;">Custom order menunggu dikerjakan</div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Transaksi Bulan Ini</div>
                <div class="fs-3"><?= (int) $statistik['transaksi_bulan_ini'] ?></div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Pendapatan Bulan Ini</div>
                <div class="fs-5 mt-2"><?= rupiah($statistik['pendapatan_bulan_ini']) ?></div>
            </div>
        </div>
    </div>

</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span>Pesanan Terbaru</span>
        <a href="<?= BASE_URL ?>/admin/pesanan.php" class="btn btn-sm btn-outline-primary">
            Lihat Semua
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kode Pesanan</th>
                    <th>Pelanggan</th>
                    <th>Jenis</th>
                    <th>Tanggal</th>
                    <th class="text-end">Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($pesananTerbaru)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        Belum ada pesanan yang masuk.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($pesananTerbaru as $row): ?>
                    <tr>
                        <td><?= e($row['kode_pesanan']) ?></td>
                        <td><?= e($row['nama']) ?></td>
                        <td><?= label_jenis($row['jenis_pesanan']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($row['tanggal_pesanan'])) ?></td>
                        <td class="text-end"><?= rupiah($row['total_harga']) ?></td>
                        <td>
                            <span class="badge bg-<?= warna_status($row['status']) ?>">
                                <?= e(label_status($row['status'])) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
