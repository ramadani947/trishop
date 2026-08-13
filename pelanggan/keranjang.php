<?php
/**
 * pelanggan/keranjang.php
 * Keranjang belanja produk ready stock.
 * Pelanggan dapat mengubah jumlah, menghapus item, dan melanjutkan ke checkout.
 */

require_once __DIR__ . '/../includes/init.php';

wajib_pelanggan();

$user = user_aktif();

/**
 * Mengambil isi keranjang pelanggan beserta ringkasannya.
 * Dipakai bersama oleh penyaji halaman dan penanganan permintaan AJAX,
 * agar angka yang ditampilkan keduanya dijamin dihitung dengan cara sama.
 */
function ambil_keranjang(PDO $pdo, $userId)
{
    $stmt = $pdo->prepare(
        "SELECT k.id, k.qty, p.id AS produk_id, p.nama_produk, p.harga, p.stok, p.gambar, p.status
         FROM keranjang k
         JOIN produk p ON p.id = k.produk_id
         WHERE k.user_id = ?
         ORDER BY k.created_at DESC"
    );
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll();

    $total      = 0;
    $adaMasalah = false;

    foreach ($items as $item) {
        $total += $item['harga'] * $item['qty'];

        if ($item['status'] !== 'aktif' || $item['qty'] > $item['stok']) {
            $adaMasalah = true;
        }
    }

    return ['items' => $items, 'total' => $total, 'ada_masalah' => $adaMasalah];
}

// --- Aksi ubah jumlah dan hapus item -----------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $id   = (int) ($_POST['id'] ?? 0);

    // Permintaan dari JavaScript meminta balasan JSON agar halaman tidak
    // perlu dimuat ulang. Tanpa JavaScript, alur lama (redirect) tetap jalan.
    $jsonMode = ($_POST['format'] ?? '') === 'json';

    if ($jsonMode) {
        header('Content-Type: application/json');
    }

    // Pastikan item benar-benar milik pelanggan yang sedang login.
    $stmt = $pdo->prepare(
        "SELECT k.id, k.qty, p.stok, p.nama_produk
         FROM keranjang k
         JOIN produk p ON p.id = k.produk_id
         WHERE k.id = ? AND k.user_id = ?"
    );
    $stmt->execute([$id, $user['id']]);
    $item = $stmt->fetch();

    if (!$item) {
        if ($jsonMode) {
            http_response_code(404);
            echo json_encode(['sukses' => false, 'pesan' => 'Item keranjang tidak ditemukan.']);
            exit;
        }
        set_flash('danger', 'Item keranjang tidak ditemukan.');
        redirect(BASE_URL . '/pelanggan/keranjang.php');
    }

    $sukses   = true;
    $pesan    = '';
    $qtyAkhir = (int) $item['qty'];

    if ($aksi === 'hapus') {
        $pdo->prepare("DELETE FROM keranjang WHERE id = ?")->execute([$id]);
        $pesan = 'Item berhasil dihapus dari keranjang.';

    } elseif ($aksi === 'ubah') {
        $qty = (int) ($_POST['qty'] ?? 1);

        if ($qty < 1) {
            $sukses = false;
            $pesan  = 'Jumlah minimal 1.';
        } elseif ($qty > $item['stok']) {
            $sukses = false;
            $pesan  = 'Jumlah melebihi stok ' . $item['nama_produk'] . ' yang tersedia (' . $item['stok'] . ').';
        } else {
            $pdo->prepare("UPDATE keranjang SET qty = ? WHERE id = ?")->execute([$qty, $id]);
            $qtyAkhir = $qty;
            $pesan    = 'Jumlah berhasil diperbarui.';
        }
    }

    if ($jsonMode) {
        $keranjang = ambil_keranjang($pdo, $user['id']);

        // Subtotal baris diambil dari keranjang yang baru dibaca ulang,
        // bukan dihitung di sisi browser, supaya angkanya sama persis
        // dengan yang tersimpan di basis data.
        $subtotal = 0;
        foreach ($keranjang['items'] as $baris) {
            if ((int) $baris['id'] === $id) {
                $subtotal = $baris['harga'] * $baris['qty'];
                break;
            }
        }

        echo json_encode([
            'sukses'      => $sukses,
            'pesan'       => $pesan,
            // Jumlah yang benar-benar tersimpan. Bila permintaan ditolak,
            // nilai ini dipakai untuk mengembalikan isian ke keadaan semula.
            'qty'         => $qtyAkhir,
            'subtotal'    => rupiah($subtotal),
            'total'       => rupiah($keranjang['total']),
            'jumlah_item' => count($keranjang['items']),
            'ada_masalah' => $keranjang['ada_masalah'],
        ]);
        exit;
    }

    set_flash($sukses ? 'success' : 'danger', $pesan);
    redirect(BASE_URL . '/pelanggan/keranjang.php');
}

// --- Ambil isi keranjang ------------------------------------------------

$keranjang    = ambil_keranjang($pdo, $user['id']);
$items        = $keranjang['items'];
$totalBelanja = $keranjang['total'];
$adaMasalah   = $keranjang['ada_masalah'];

$judulHalaman = 'Keranjang Belanja';
$menuAktif    = 'keranjang';

require_once __DIR__ . '/../includes/header.php';
?>

    <h1 class="judul-halaman">Keranjang Belanja<span class="sub">Periksa pesanan Anda sebelum melanjutkan ke pembayaran.</span></h1>

    <?php if (empty($items)): ?>

        <div class="card">
            <div class="kosong">
                <div class="ikon">&#128722;</div>
                <p class="text-muted mb-3">Keranjang Anda masih kosong.</p>
                <a href="<?= BASE_URL ?>/pelanggan/ready_stock.php" class="btn btn-primary">Mulai Belanja</a>
            </div>
        </div>

    <?php else: ?>

        <div class="row g-4">

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th colspan="2">Produk</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-center" style="min-width: 150px;">Jumlah</th>
                                    <th class="text-end">Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td style="width: 76px;">
                                        <?= thumb_berkas('produk', $item['gambar'], 56) ?>
                                    </td>

                                    <td>
                                        <a href="<?= BASE_URL ?>/pelanggan/detail_produk.php?id=<?= (int) $item['produk_id'] ?>"
                                           class="text-decoration-none"><?= e($item['nama_produk']) ?></a>

                                        <?php if ($item['status'] !== 'aktif'): ?>
                                            <div class="small text-danger">Produk tidak lagi tersedia.</div>
                                        <?php elseif ($item['qty'] > $item['stok']): ?>
                                            <div class="small text-danger">
                                                Stok tinggal <?= (int) $item['stok'] ?>. Kurangi jumlah pesanan.
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end"><?= rupiah($item['harga']) ?></td>

                                    <td class="text-center">
                                        <form method="post" class="d-flex gap-1 justify-content-center"
                                              data-form-qty data-id="<?= (int) $item['id'] ?>"><?= csrf_field() ?>
                                            <input type="hidden" name="aksi" value="ubah">
                                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                            <input type="number" name="qty" class="form-control form-control-sm"
                                                   value="<?= (int) $item['qty'] ?>" min="1"
                                                   max="<?= (int) $item['stok'] ?>" style="width: 70px;">
                                            <button class="btn btn-sm btn-outline-secondary" data-tombol-ubah>Ubah</button>
                                        </form>
                                    </td>

                                    <td class="text-end" data-subtotal>
                                        <?= rupiah($item['harga'] * $item['qty']) ?>
                                    </td>

                                    <td class="text-end">
                                        <form method="post"
                                              onsubmit="return confirm('Hapus item ini dari keranjang?');"><?= csrf_field() ?>
                                            <input type="hidden" name="aksi" value="hapus">
                                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger">&times;</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <a href="<?= BASE_URL ?>/pelanggan/ready_stock.php" class="btn btn-sm btn-link ps-0 mt-2">
                    &larr; Lanjut belanja
                </a>
            </div>

            <!-- data-ringkasan menjadi pegangan assets/js/keranjang.js -->
            <div class="col-lg-4 ringkasan-lengket"
                 data-ringkasan data-ada-masalah="<?= $adaMasalah ? '1' : '0' ?>">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">

                        <h2 class="h6 mb-3">Ringkasan Belanja</h2>

                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Jumlah item</span>
                            <span id="ringkasan-item"><?= count($items) ?></span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="fw-semibold">Total</span>
                            <span class="fw-semibold fs-5 text-primary"
                                  id="ringkasan-total"><?= rupiah($totalBelanja) ?></span>
                        </div>

                        <?php if ($adaMasalah): ?>
                            <div class="alert alert-warning small py-2">
                                Perbaiki item bermasalah di keranjang sebelum melanjutkan checkout.
                            </div>
                            <button class="btn btn-primary w-100" disabled>Checkout</button>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/pelanggan/checkout.php" class="btn btn-primary w-100">
                                Checkout
                            </a>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>

    <?php endif; ?>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
