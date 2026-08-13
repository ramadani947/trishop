<?php
/**
 * pelanggan/checkout.php
 * Halaman checkout dan pembayaran.
 *
 * Dua jalur masuk sesuai Activity Diagram Pemesanan (Gambar 3.4):
 *   1. Tanpa parameter  -> checkout produk ready stock dari keranjang.
 *   2. ?pesanan=ID      -> melanjutkan pembayaran pesanan yang sudah dibuat
 *                          (umumnya berasal dari custom order).
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/midtrans.php';

wajib_pelanggan();

$user      = user_aktif();
$pesananId = (int) ($_GET['pesanan'] ?? 0);
$error     = '';

// --- Membatalkan pesanan yang belum dibayar -----------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'batalkan') {

    $idBatal = (int) ($_POST['pesanan_id'] ?? 0);

    // Hanya pesanan milik sendiri yang masih menunggu pembayaran yang
    // boleh dibatalkan. Pesanan yang sudah diproses adalah urusan admin.
    $stmt = $pdo->prepare(
        "SELECT p.id, p.kode_pesanan, b.order_id_midtrans
         FROM pesanan p
         LEFT JOIN pembayaran b ON b.pesanan_id = p.id
         WHERE p.id = ? AND p.user_id = ? AND p.status = 'menunggu_pembayaran'"
    );
    $stmt->execute([$idBatal, $user['id']]);
    $target = $stmt->fetch();

    if (!$target) {
        set_flash('danger', 'Pesanan tidak dapat dibatalkan.');
        redirect(BASE_URL . '/pelanggan/pesanan_saya.php');
    }

    // Tutup dulu transaksinya di Midtrans, supaya kode pembayaran yang
    // terlanjur terbit tidak bisa dibayar setelah pesanan dibatalkan.
    $tutup = midtrans_batalkan_transaksi($target['order_id_midtrans'] ?? '');

    // Pembayaran ternyata sudah berhasil, hanya notifikasinya yang belum
    // tiba. Pesanan tidak boleh dibatalkan; statusnya justru disesuaikan.
    if (!empty($tutup['sudah_dibayar'])) {
        try {
            $trx = \Midtrans\Transaction::status($target['order_id_midtrans']);
            midtrans_terapkan_status(
                $pdo,
                $target['order_id_midtrans'],
                $trx->transaction_status ?? '',
                $trx->fraud_status ?? null,
                $trx->payment_type ?? null,
                $trx->transaction_time ?? null,
                json_encode($trx)
            );
        } catch (Exception $ex) {
            // Diabaikan; pesan di bawah tetap menjelaskan keadaannya.
        }

        set_flash('info', 'Pesanan tidak dibatalkan karena pembayarannya sudah berhasil. '
            . 'Status pesanan telah diperbarui.');
        redirect(BASE_URL . '/pelanggan/pesanan_saya.php');
    }

    try {
        $pdo->beginTransaction();

        $pdo->prepare("UPDATE pesanan SET status = 'canceled' WHERE id = ?")
            ->execute([$idBatal]);

        // Stok tidak perlu dikembalikan: pengurangan stok baru dilakukan
        // setelah pembayaran berhasil, dan pesanan ini belum dibayar.
        if (!empty($target['order_id_midtrans'])) {
            $pdo->prepare("UPDATE pembayaran SET status_pembayaran = 'cancel' WHERE pesanan_id = ?")
                ->execute([$idBatal]);
        }

        $pdo->commit();

    } catch (PDOException $ex) {
        $pdo->rollBack();
        set_flash('danger', 'Pesanan gagal dibatalkan. Silakan coba lagi.');
        redirect(BASE_URL . '/pelanggan/checkout.php?pesanan=' . $idBatal);
    }

    if ($tutup['sukses']) {
        set_flash('success', 'Pesanan ' . $target['kode_pesanan'] . ' berhasil dibatalkan.');
    } else {
        // Pesanan tetap dibatalkan, tetapi pelanggan perlu tahu bahwa
        // kode pembayaran lama mungkin masih aktif di sisi Midtrans.
        set_flash('warning', 'Pesanan ' . $target['kode_pesanan'] . ' dibatalkan, '
            . 'namun kode pembayaran lama belum dapat ditutup di Midtrans. '
            . 'Mohon jangan membayarnya.');
    }

    redirect(BASE_URL . '/pelanggan/pesanan_saya.php');
}

// --- Membuat pesanan ready stock dari keranjang -------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'buat_pesanan') {

    $stmt = $pdo->prepare(
        "SELECT k.qty, p.id, p.nama_produk, p.harga, p.stok, p.status
         FROM keranjang k
         JOIN produk p ON p.id = k.produk_id
         WHERE k.user_id = ?"
    );
    $stmt->execute([$user['id']]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
        set_flash('danger', 'Keranjang Anda kosong.');
        redirect(BASE_URL . '/pelanggan/keranjang.php');
    }

    // Validasi ulang ketersediaan produk sesaat sebelum pesanan dibuat.
    foreach ($items as $item) {
        if ($item['status'] !== 'aktif') {
            set_flash('danger', 'Produk ' . $item['nama_produk'] . ' sudah tidak tersedia.');
            redirect(BASE_URL . '/pelanggan/keranjang.php');
        }
        if ($item['qty'] > $item['stok']) {
            set_flash('danger', 'Stok ' . $item['nama_produk'] . ' tidak mencukupi.');
            redirect(BASE_URL . '/pelanggan/keranjang.php');
        }
    }

    $total = 0;
    foreach ($items as $item) {
        $total += $item['harga'] * $item['qty'];
    }

    try {
        $pdo->beginTransaction();

        $kode = generate_kode_pesanan($pdo);

        $stmt = $pdo->prepare(
            "INSERT INTO pesanan (kode_pesanan, user_id, jenis_pesanan, total_harga, status, catatan)
             VALUES (?, ?, 'ready_stock', ?, 'menunggu_pembayaran', ?)"
        );
        $stmt->execute([$kode, $user['id'], $total, trim($_POST['catatan'] ?? '')]);

        $idBaru = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            "INSERT INTO detail_pesanan (pesanan_id, produk_id, nama_produk, harga_satuan, qty, subtotal)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        foreach ($items as $item) {
            $stmt->execute([
                $idBaru, $item['id'], $item['nama_produk'],
                $item['harga'], $item['qty'], $item['harga'] * $item['qty'],
            ]);
        }

        // Keranjang dikosongkan setelah pesanan terbentuk.
        $pdo->prepare("DELETE FROM keranjang WHERE user_id = ?")->execute([$user['id']]);

        $pdo->commit();

        redirect(BASE_URL . '/pelanggan/checkout.php?pesanan=' . $idBaru);

    } catch (PDOException $ex) {
        $pdo->rollBack();
        set_flash('danger', 'Pesanan gagal dibuat. Silakan coba lagi.');
        redirect(BASE_URL . '/pelanggan/keranjang.php');
    }
}

// =======================================================================
// MODE A: ringkasan keranjang sebelum pesanan dibuat
// =======================================================================

if ($pesananId === 0) {

    $stmt = $pdo->prepare(
        "SELECT k.qty, p.id, p.nama_produk, p.harga, p.stok, p.gambar, p.status
         FROM keranjang k
         JOIN produk p ON p.id = k.produk_id
         WHERE k.user_id = ?
         ORDER BY k.created_at DESC"
    );
    $stmt->execute([$user['id']]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
        set_flash('warning', 'Keranjang Anda kosong.');
        redirect(BASE_URL . '/pelanggan/keranjang.php');
    }

    $total = 0;
    foreach ($items as $item) {
        $total += $item['harga'] * $item['qty'];
    }

    $judulHalaman = 'Checkout';

// =======================================================================
// MODE B: pesanan sudah ada, lanjut ke pembayaran
// =======================================================================

} else {

    $stmt = $pdo->prepare("SELECT * FROM pesanan WHERE id = ? AND user_id = ?");
    $stmt->execute([$pesananId, $user['id']]);
    $pesanan = $stmt->fetch();

    if (!$pesanan) {
        set_flash('danger', 'Pesanan tidak ditemukan.');
        redirect(BASE_URL . '/index.php');
    }

    if ($pesanan['status'] !== 'menunggu_pembayaran') {
        set_flash('info', 'Pesanan ini sudah tidak dalam status menunggu pembayaran.');
        redirect(BASE_URL . '/pelanggan/pesanan_saya.php');
    }

    // Ambil rincian sesuai jenis pesanan.
    if ($pesanan['jenis_pesanan'] === 'custom') {
        $stmt = $pdo->prepare("SELECT * FROM detail_custom WHERE pesanan_id = ?");
        $stmt->execute([$pesananId]);
        $detailCustom = $stmt->fetch();
        $detailItems  = [];
    } else {
        $stmt = $pdo->prepare("SELECT * FROM detail_pesanan WHERE pesanan_id = ?");
        $stmt->execute([$pesananId]);
        $detailItems  = $stmt->fetchAll();
        $detailCustom = null;
    }

    // --- Siapkan Snap Token --------------------------------------------

    $stmt = $pdo->prepare("SELECT * FROM pembayaran WHERE pesanan_id = ?");
    $stmt->execute([$pesananId]);
    $pembayaran = $stmt->fetch();

    $snapToken = $pembayaran['snap_token'] ?? null;

    if (!$snapToken) {
        // order_id wajib unik secara global dan permanen di sisi Midtrans:
        // sebuah order_id yang pernah dipakai tidak akan pernah bisa dipakai
        // lagi, sekalipun database lokal direset atau pesanan dibuat ulang.
        // Karena kode_pesanan bernomor urut harian (TSS-YYYYMMDD-0001), kode
        // yang sama bisa terbentuk kembali dan ditolak Midtrans. Kode pesanan
        // tetap dipakai sebagai awalan agar mudah ditelusuri di dashboard,
        // lalu diberi akhiran acak sebagai penjamin keunikan.
        $orderId = $pesanan['kode_pesanan'] . '-' . strtoupper(bin2hex(random_bytes(3)));
        $gross   = (int) round($pesanan['total_harga']);

        $itemDetails = [];

        if ($detailCustom) {
            $itemDetails[] = [
                'id'       => 'CUSTOM-' . $detailCustom['id'],
                'price'    => (int) round($detailCustom['harga_per_pcs']),
                'quantity' => (int) $detailCustom['qty'],
                'name'     => mb_substr(
                    $detailCustom['nama_model'] . ' - ' . $detailCustom['nama_bahan'], 0, 50
                ),
            ];
        } else {
            foreach ($detailItems as $d) {
                $itemDetails[] = [
                    'id'       => 'PRD-' . $d['produk_id'],
                    'price'    => (int) round($d['harga_satuan']),
                    'quantity' => (int) $d['qty'],
                    'name'     => mb_substr($d['nama_produk'], 0, 50),
                ];
            }
        }

        // Ambil data kontak pelanggan.
        $stmt = $pdo->prepare("SELECT nama, email, no_hp FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $profil = $stmt->fetch();

        $hasil = midtrans_buat_snap_token([
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => $gross,
            ],
            'item_details'     => $itemDetails,
            'customer_details' => [
                'first_name' => $profil['nama'],
                'email'      => $profil['email'],
                'phone'      => $profil['no_hp'] ?: '',
            ],
            // Metode e-wallet (DANA, ShopeePay, dsb.) memindahkan pelanggan ke
            // halaman penyedia dana, sehingga callback JavaScript onSuccess
            // tidak pernah berjalan. Tanpa alamat ini, Midtrans memakai alamat
            // bawaannya (example.com) dan pelanggan tersesat keluar dari sistem.
            'callbacks' => [
                'finish' => BASE_URL . '/payment/selesai.php',
            ],
        ]);

        if ($hasil['sukses']) {
            $snapToken = $hasil['token'];

            if ($pembayaran) {
                $pdo->prepare(
                    "UPDATE pembayaran SET snap_token = ?, order_id_midtrans = ?, jumlah_bayar = ?
                     WHERE pesanan_id = ?"
                )->execute([$snapToken, $orderId, $gross, $pesananId]);
            } else {
                $pdo->prepare(
                    "INSERT INTO pembayaran (pesanan_id, order_id_midtrans, snap_token, jumlah_bayar, status_pembayaran)
                     VALUES (?, ?, ?, ?, 'pending')"
                )->execute([$pesananId, $orderId, $snapToken, $gross]);
            }
        } else {
            $error = 'Gagal menyiapkan pembayaran: ' . $hasil['pesan'];
        }
    }

    $judulHalaman = 'Pembayaran';
}

$menuAktif = 'keranjang';

require_once __DIR__ . '/../includes/header.php';
?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <h1 class="judul-halaman"><?= e($judulHalaman) ?></h1>

<?php if ($pesananId === 0): ?>

    <!-- ============ MODE A: konfirmasi keranjang ============ -->
    <form method="post"><?= csrf_field() ?>
        <input type="hidden" name="aksi" value="buat_pesanan">

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">Rincian Pesanan</div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= e($item['nama_produk']) ?></td>
                                    <td class="text-end"><?= rupiah($item['harga']) ?></td>
                                    <td class="text-center"><?= (int) $item['qty'] ?></td>
                                    <td class="text-end"><?= rupiah($item['harga'] * $item['qty']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body">
                        <label for="catatan" class="form-label">
                            Catatan untuk Penjual <span class="text-muted">(opsional)</span>
                        </label>
                        <textarea class="form-control" id="catatan" name="catatan" rows="2"></textarea>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="fw-semibold">Total Pembayaran</span>
                            <span class="fw-semibold fs-5 text-primary"><?= rupiah($total) ?></span>
                        </div>
                        <button class="btn btn-primary w-100">Buat Pesanan</button>
                        <a href="<?= BASE_URL ?>/pelanggan/keranjang.php"
                           class="btn btn-link w-100 mt-1">Kembali ke keranjang</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

<?php else: ?>

    <!-- ============ MODE B: pembayaran ============ -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between">
                    <span>Pesanan <?= e($pesanan['kode_pesanan']) ?></span>
                    <span class="text-muted small"><?= label_jenis($pesanan['jenis_pesanan']) ?></span>
                </div>
                <div class="card-body">

                    <?php if ($detailCustom): ?>
                        <dl class="row mb-0">
                            <dt class="col-sm-4 fw-normal text-muted">Model tas</dt>
                            <dd class="col-sm-8"><?= e($detailCustom['nama_model']) ?></dd>

                            <dt class="col-sm-4 fw-normal text-muted">Bahan</dt>
                            <dd class="col-sm-8"><?= e($detailCustom['nama_bahan']) ?></dd>

                            <dt class="col-sm-4 fw-normal text-muted">Desain</dt>
                            <dd class="col-sm-8">
                                <?php if (berkas_unggahan_ada('custom', $detailCustom['file_custom_desain'])): ?>
                                    <a href="<?= BASE_URL ?>/uploads/custom/<?= e($detailCustom['file_custom_desain']) ?>"
                                       target="_blank">Custom desain (unggahan Anda)</a>
                                <?php elseif ($detailCustom['template_desain_id']): ?>
                                    Template desain yang dipilih
                                <?php else: ?>
                                    &mdash;
                                <?php endif; ?>
                            </dd>

                            <dt class="col-sm-4 fw-normal text-muted">Perhitungan</dt>
                            <dd class="col-sm-8">
                                <?= rupiah($detailCustom['harga_bahan']) ?> &times;
                                <?= number_format((float) $detailCustom['faktor_pengali'], 2, ',', '.') ?>
                                = <?= rupiah($detailCustom['harga_per_pcs']) ?> / pcs
                            </dd>

                            <dt class="col-sm-4 fw-normal text-muted">Jumlah</dt>
                            <dd class="col-sm-8"><?= (int) $detailCustom['qty'] ?> pcs</dd>

                            <?php if ($detailCustom['catatan_desain']): ?>
                                <dt class="col-sm-4 fw-normal text-muted">Catatan</dt>
                                <dd class="col-sm-8 mb-0"><?= e($detailCustom['catatan_desain']) ?></dd>
                            <?php endif; ?>
                        </dl>
                    <?php else: ?>
                        <!-- Dibungkus agar tabel bergulir sendiri di layar sempit,
                             bukan melebarkan seluruh halaman. -->
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th class="text-end">Harga</th>
                                        <th class="text-center">Jumlah</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($detailItems as $d): ?>
                                    <tr>
                                        <td><?= e($d['nama_produk']) ?></td>
                                        <td class="text-end"><?= rupiah($d['harga_satuan']) ?></td>
                                        <td class="text-center"><?= (int) $d['qty'] ?></td>
                                        <td class="text-end"><?= rupiah($d['subtotal']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <div class="col-lg-4 ringkasan-lengket">
            <div class="card border-0 shadow-sm">
                <div class="card-body">

                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-semibold">Total Pembayaran</span>
                        <span class="fw-semibold fs-5 text-primary">
                            <?= rupiah($pesanan['total_harga']) ?>
                        </span>
                    </div>

                    <?php if ($snapToken): ?>
                        <button id="btn-bayar" class="btn btn-primary w-100">Bayar Sekarang</button>
                        <p class="text-muted mt-3 mb-0" style="font-size: 12px;">
                            Pembayaran diproses oleh Midtrans. Status pesanan diperbarui
                            secara otomatis setelah pembayaran dikonfirmasi.
                            <?php if ($pesanan['jenis_pesanan'] === 'ready_stock'): ?>
                                Produk ready stock tidak melewati proses produksi, jadi
                                pesanan langsung berstatus selesai begitu pembayaran masuk.
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <button class="btn btn-secondary w-100" disabled>Pembayaran Tidak Tersedia</button>
                    <?php endif; ?>

                    <a href="<?= BASE_URL ?>/pelanggan/pesanan_saya.php"
                       class="btn btn-link w-100 mt-1">Bayar nanti</a>

                    <hr class="my-3">

                    <form method="post" onsubmit="return confirm('Batalkan pesanan <?= e($pesanan['kode_pesanan']) ?>? Tindakan ini tidak dapat dibatalkan.');"><?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="batalkan">
                        <input type="hidden" name="pesanan_id" value="<?= (int) $pesanan['id'] ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                            Batalkan Pesanan
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php if ($pesananId > 0 && !empty($snapToken)): ?>
<script src="<?= MIDTRANS_SNAP_JS ?>" data-client-key="<?= MIDTRANS_CLIENT_KEY ?>"></script>
<script>
// Webhook Midtrans tidak dapat menjangkau server localhost, jadi status
// dicek aktif dari sini sebagai pelengkap agar pesanan tetap ter-update.
function periksaStatus() {
    return fetch('<?= BASE_URL ?>/payment/cek_status.php?pesanan=<?= $pesananId ?>')
        .then(function (r) { return r.json(); });
}

function keDaftarPesanan() {
    window.location.href = '<?= BASE_URL ?>/pelanggan/pesanan_saya.php';
}

/**
 * Dipakai onPending dan onClose, yaitu ketika popup ditutup tanpa keterangan
 * hasil yang pasti. Snap memanggil onClose bila pelanggan menutup sebelum
 * memilih metode, dan onPending bila metode sudah dipilih sehingga nomor VA
 * atau kode QR terlanjur terbit; keduanya sama-sama belum berarti dibayar.
 *
 * Status tetap ditanyakan, sebab pelanggan bisa saja sudah membayar di luar
 * popup (QRIS dipindai dari ponsel, misalnya) atau transaksinya ditutup
 * Midtrans. Halaman hanya ditinggalkan bila status pesanan benar-benar sudah
 * berubah. Selama masih menunggu pembayaran, pelanggan dibiarkan di sini agar
 * dapat menekan "Bayar Sekarang" lagi untuk memunculkan kembali instruksi
 * pembayarannya.
 */
function pindahBilaStatusBerubah() {
    periksaStatus()
        .then(function (d) {
            if (d && d.status_pesanan && d.status_pesanan !== 'menunggu_pembayaran') {
                keDaftarPesanan();
            }
        })
        .catch(function () { /* diabaikan, pelanggan tetap di halaman ini */ });
}

document.getElementById('btn-bayar').addEventListener('click', function () {
    snap.pay('<?= $snapToken ?>', {
        // Pembayaran dipastikan berhasil: status diperbarui lebih dulu, lalu
        // pelanggan diantar ke daftar pesanannya apa pun hasil pengecekannya.
        onSuccess: function () {
            periksaStatus()
                .catch(function () { /* webhook tetap memperbarui status */ })
                .finally(keDaftarPesanan);
        },
        onPending: pindahBilaStatusBerubah,
        onClose:   pindahBilaStatusBerubah,
        onError: function () {
            alert('Pembayaran gagal diproses. Silakan coba lagi.');
        }
    });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
