<?php
/**
 * admin/laporan.php
 * Laporan Penjualan Periodik berdasarkan rentang tanggal.
 * Implementasi Activity Diagram Laporan Penjualan Periodik (Gambar 3.7)
 * dan kebutuhan fungsional nomor 7 pada Tabel 3.1.
 */

require_once __DIR__ . '/../includes/init.php';

wajib_admin();

$error = '';

// Nilai bawaan: awal bulan berjalan sampai hari ini.
$tanggalAwal  = $_GET['tanggal_awal']  ?? date('Y-m-01');
$tanggalAkhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');

$transaksi       = [];
$jumlahTrx       = 0;
$totalPendapatan = 0;

// --- Validasi rentang tanggal ------------------------------------------

if (!DateTime::createFromFormat('Y-m-d', $tanggalAwal)
    || !DateTime::createFromFormat('Y-m-d', $tanggalAkhir)) {
    $error = 'Format tanggal tidak valid.';
} elseif ($tanggalAwal > $tanggalAkhir) {
    $error = 'Tanggal awal tidak boleh melebihi tanggal akhir.';
}

// --- Ambil data transaksi ----------------------------------------------

if ($error === '') {
    // Tabel pembayaran sengaja tidak ikut di-join: tidak ada satu pun kolomnya
    // yang ditampilkan, sedangkan join-nya berisiko menggandakan baris laporan
    // bila sebuah pesanan sampai punya lebih dari satu catatan pembayaran.
    $stmt = $pdo->prepare(
        "SELECT p.kode_pesanan, p.jenis_pesanan, p.total_harga, p.status,
                p.tanggal_pesanan, u.nama
         FROM pesanan p
         JOIN users u ON u.id = p.user_id
         WHERE p.status IN ('diproses','selesai')
           AND DATE(p.tanggal_pesanan) BETWEEN ? AND ?
         ORDER BY p.tanggal_pesanan ASC"
    );
    $stmt->execute([$tanggalAwal, $tanggalAkhir]);
    $transaksi = $stmt->fetchAll();

    $jumlahTrx = count($transaksi);

    foreach ($transaksi as $t) {
        $totalPendapatan += $t['total_harga'];
    }
}

// Data toko untuk kop surat laporan.
$toko = $pdo->query("SELECT * FROM pengaturan_toko LIMIT 1")->fetch();

if (!$toko) {
    $toko = [
        'nama_toko' => 'Tri Shop Souvenir',
        'alamat'    => 'Pondok Benowo Indah Blok CD No. 8, Surabaya',
        'no_telp'   => '-',
        'email'     => '-',
    ];
}

$judulHalaman = 'Laporan Penjualan Periodik';
$menuAktif    = 'laporan';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<style>
@media print {
    #bilah-admin, .no-print, footer { display: none !important; }
    #konten { margin-left: 0 !important; }
    main { padding: 0 !important; }
    .card { box-shadow: none !important; border: none !important; }
    #kop-surat { display: block !important; }
    body { background: #fff !important; overflow-x: visible !important; }

    /* Sudut squircle pada .table-responsive dikombinasikan dengan
       overflow-x: auto (bawaan Bootstrap) membuat pojok tabel ikut
       terpotong saat dicetak. Dimatikan khusus untuk cetak supaya
       tabel tercetak utuh tanpa sudut membulat yang memotong. */
    .table-responsive {
        overflow: visible !important;
        border-radius: 0 !important;
    }
}
#kop-surat { display: none; }
</style>

<!-- Filter rentang tanggal -->
<div class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">

            <div class="col-md-3">
                <label for="tanggal_awal" class="form-label">Tanggal Awal</label>
                <input type="date" class="form-control" id="tanggal_awal" name="tanggal_awal"
                       value="<?= e($tanggalAwal) ?>" required>
            </div>

            <div class="col-md-3">
                <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir"
                       value="<?= e($tanggalAkhir) ?>" required>
            </div>

            <div class="col-md-6 d-flex gap-2">
                <button class="btn btn-primary">Tampilkan Laporan</button>

                <?php if ($error === '' && $jumlahTrx > 0): ?>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.print();">
                        Cetak Laporan
                    </button>
                <?php endif; ?>
            </div>

        </form>
    </div>
</div>

<?php if ($error !== ''): ?>

    <div class="alert alert-danger no-print"><?= e($error) ?></div>

<?php elseif ($jumlahTrx === 0): ?>

    <div class="alert alert-secondary no-print">
        Data transaksi pada rentang tanggal
        <?= date('d/m/Y', strtotime($tanggalAwal)) ?> sampai
        <?= date('d/m/Y', strtotime($tanggalAkhir)) ?> tidak tersedia.
    </div>

<?php else: ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            <!-- Kop surat, tampil hanya saat dicetak -->
            <div id="kop-surat" class="mb-4">
                <table style="width: 100%; border-bottom: 3px double #000; padding-bottom: 10px;">
                    <tr>
                        <td style="text-align: center;">
                            <div style="font-size: 20px; font-weight: bold; letter-spacing: 1px;">
                                <?= e(strtoupper($toko['nama_toko'])) ?>
                            </div>
                            <div style="font-size: 12px;"><?= e($toko['alamat']) ?></div>
                            <div style="font-size: 12px;">
                                Telp: <?= e($toko['no_telp']) ?>
                                <?php if (!empty($toko['email'])): ?>
                                    &nbsp;|&nbsp; Email: <?= e($toko['email']) ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                </table>

                <div style="text-align: center; margin-top: 16px;">
                    <div style="font-size: 15px; font-weight: bold; text-decoration: underline;">
                        LAPORAN PENJUALAN PERIODIK
                    </div>
                    <div style="font-size: 13px; margin-top: 4px;">
                        Periode <?= date('d F Y', strtotime($tanggalAwal)) ?>
                        s.d. <?= date('d F Y', strtotime($tanggalAkhir)) ?>
                    </div>
                </div>
            </div>

            <!-- Ringkasan, hanya tampil di layar -->
            <div class="row g-3 mb-4 no-print">
                <div class="col-md-4">
                    <div class="border rounded p-3">
                        <div class="text-muted small">Periode</div>
                        <div>
                            <?= date('d/m/Y', strtotime($tanggalAwal)) ?> &ndash;
                            <?= date('d/m/Y', strtotime($tanggalAkhir)) ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3">
                        <div class="text-muted small">Jumlah Transaksi</div>
                        <div class="fs-5"><?= $jumlahTrx ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3">
                        <div class="text-muted small">Total Pendapatan</div>
                        <div class="fs-5 text-primary"><?= rupiah($totalPendapatan) ?></div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 40px;">No</th>
                            <th>Kode Pesanan</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Jenis</th>
                            <th>Status</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($transaksi as $i => $t): ?>
                        <tr>
                            <td class="text-center"><?= $i + 1 ?></td>
                            <td><?= e($t['kode_pesanan']) ?></td>
                            <td><?= date('d/m/Y', strtotime($t['tanggal_pesanan'])) ?></td>
                            <td><?= e($t['nama']) ?></td>
                            <td><?= label_jenis($t['jenis_pesanan']) ?></td>
                            <td><?= e(label_status($t['status'])) ?></td>
                            <td class="text-end"><?= rupiah($t['total_harga']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="6" class="text-end">Jumlah Transaksi: <?= $jumlahTrx ?></th>
                            <th class="text-end"><?= rupiah($totalPendapatan) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Tanda tangan, hanya tampil saat dicetak -->
            <div id="ttd" class="mt-5" style="display: none;">
                <div style="width: 260px; margin-left: auto; text-align: center; font-size: 13px;">
                    <div>Surabaya, <?= date('d F Y') ?></div>
                    <div style="margin-bottom: 70px;">Pemilik Usaha</div>
                    <div style="border-top: 1px solid #000; padding-top: 4px;">
                        <?= e($toko['nama_toko']) ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <style>
        @media print { #ttd { display: block !important; } }
    </style>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
