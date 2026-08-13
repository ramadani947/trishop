<?php
/**
 * includes/pesanan_helpers.php
 * Fungsi bantu seputar keranjang, perhitungan harga, dan status pesanan.
 * Dimuat otomatis oleh includes/init.php.
 */

// ---------------------------------------------------------------------------
// Keranjang dan perhitungan harga
// ---------------------------------------------------------------------------

/**
 * Jumlah item pada keranjang seorang pelanggan.
 * Dipakai includes/header.php untuk menampilkan penanda pada menu Keranjang.
 */
function jumlah_keranjang(PDO $pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM keranjang WHERE user_id = ?");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Kalkulasi harga custom order.
 * Rumus proposal: harga per pcs = harga bahan x faktor pengali model tas.
 */
function hitung_harga_per_pcs($hargaBahan, $faktorPengali)
{
    return round((float) $hargaBahan * (float) $faktorPengali, 2);
}

/** Total harga custom order = harga per pcs x jumlah pesanan. */
function hitung_total_custom($hargaPerPcs, $qty)
{
    return round((float) $hargaPerPcs * (int) $qty, 2);
}

// ---------------------------------------------------------------------------
// Pesanan
// ---------------------------------------------------------------------------

/** Membuat kode pesanan unik, contoh: TSS-20260729-0001. */
function generate_kode_pesanan(PDO $pdo)
{
    $urut = (int) $pdo->query(
        "SELECT COUNT(*) FROM pesanan WHERE DATE(tanggal_pesanan) = CURDATE()"
    )->fetchColumn() + 1;

    return 'TSS-' . date('Ymd') . '-' . str_pad($urut, 4, '0', STR_PAD_LEFT);
}

/** Seluruh status pesanan beserta labelnya, sekaligus urutan tampilnya. */
function daftar_status()
{
    return [
        'menunggu_pembayaran' => 'Menunggu Pembayaran',
        'diproses'            => 'Diproses',
        'selesai'             => 'Selesai',
        'canceled'            => 'Dibatalkan',
    ];
}

/** Label status pesanan untuk ditampilkan ke pengguna. */
function label_status($status)
{
    return daftar_status()[$status] ?? $status;
}

/** Warna badge Bootstrap untuk setiap status pesanan. */
function warna_status($status)
{
    $warna = [
        'menunggu_pembayaran' => 'secondary',
        'diproses'            => 'warning',
        'selesai'             => 'success',
        'canceled'            => 'danger',
    ];
    return $warna[$status] ?? 'secondary';
}

/** Pilihan penyaring pada halaman daftar pesanan (admin maupun pelanggan). */
function tab_status()
{
    return ['semua' => 'Semua'] + daftar_status();
}

/** Label jenis pesanan untuk ditampilkan ke pengguna. */
function label_jenis($jenisPesanan)
{
    return $jenisPesanan === 'custom' ? 'Custom Order' : 'Ready Stock';
}

/**
 * Status pesanan setelah pembayaran dipastikan berhasil.
 *
 * Pesanan ready stock tidak melewati tahap produksi apa pun: barangnya sudah
 * ada di gudang, sehingga pembayaran yang berhasil langsung menuntaskan
 * pesanan. Custom order tetap masuk antrean pengerjaan lebih dulu.
 *
 * Dipakai bersama oleh jalur otomatis (webhook Midtrans lewat
 * midtrans_status_ke_pesanan) dan jalur manual admin/pesanan.php, supaya
 * keduanya tidak pernah menghasilkan status yang berbeda.
 */
function status_lunas_pesanan($jenisPesanan)
{
    return $jenisPesanan === 'ready_stock' ? 'selesai' : 'diproses';
}

/** Menandai status pesanan yang pembayarannya sudah berhasil. */
function pesanan_sudah_dibayar($status)
{
    return in_array($status, ['diproses', 'selesai'], true);
}

/**
 * Mengambil rincian sejumlah pesanan sekaligus, cukup dua kueri untuk berapa
 * pun banyaknya pesanan. Dipakai bersama oleh admin/pesanan.php dan
 * pelanggan/pesanan_saya.php.
 *
 * Hasil: [pesanan_id => ['tipe' => 'custom'|'ready', 'data' => baris|daftar]]
 */
function ambil_rincian_pesanan(PDO $pdo, array $daftarPesanan)
{
    $rincian  = [];
    $idCustom = [];
    $idReady  = [];

    foreach ($daftarPesanan as $p) {
        $id = (int) $p['id'];

        if ($p['jenis_pesanan'] === 'custom') {
            $rincian[$id] = ['tipe' => 'custom', 'data' => null];
            $idCustom[]   = $id;
        } else {
            $rincian[$id] = ['tipe' => 'ready', 'data' => []];
            $idReady[]    = $id;
        }
    }

    if ($idCustom) {
        // Template desain ikut diambil supaya admin/pesanan.php tidak perlu
        // mengueri namanya satu per satu.
        $tanya = implode(',', array_fill(0, count($idCustom), '?'));
        $stmt  = $pdo->prepare(
            "SELECT dc.*, td.nama_desain, td.file_desain
             FROM detail_custom dc
             LEFT JOIN template_desain td ON td.id = dc.template_desain_id
             WHERE dc.pesanan_id IN ($tanya)"
        );
        $stmt->execute($idCustom);

        foreach ($stmt->fetchAll() as $d) {
            $rincian[(int) $d['pesanan_id']]['data'] = $d;
        }
    }

    if ($idReady) {
        $tanya = implode(',', array_fill(0, count($idReady), '?'));
        $stmt  = $pdo->prepare("SELECT * FROM detail_pesanan WHERE pesanan_id IN ($tanya)");
        $stmt->execute($idReady);

        foreach ($stmt->fetchAll() as $d) {
            $rincian[(int) $d['pesanan_id']]['data'][] = $d;
        }
    }

    return $rincian;
}
