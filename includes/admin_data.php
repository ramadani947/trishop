<?php
/**
 * includes/admin_data.php
 * Fungsi bantu data master (produk, model tas, bahan, template desain)
 * dan gambar bagian keunggulan pada beranda. Bergantung pada
 * includes/upload.php dan includes/format.php.
 * Dimuat otomatis oleh includes/init.php.
 */

// ---------------------------------------------------------------------------
// Data master (produk, model tas, bahan, template desain)
// ---------------------------------------------------------------------------

/**
 * Keempat data master beserta tempat pemakaiannya pada pesanan.
 *
 * tabel, label, kolom_file, folder  - identitas data master
 * pakai_tabel, pakai_kolom          - tempat memeriksa apakah pernah dipesan
 */
function data_master($jenis)
{
    $daftar = [
        'produk' => ['tabel' => 'produk',          'label' => 'Produk',          'kolom_file' => 'gambar',      'folder' => 'produk', 'pakai_tabel' => 'detail_pesanan', 'pakai_kolom' => 'produk_id'],
        'model'  => ['tabel' => 'model_tas',       'label' => 'Model tas',       'kolom_file' => 'gambar',      'folder' => 'produk', 'pakai_tabel' => 'detail_custom',  'pakai_kolom' => 'model_id'],
        'bahan'  => ['tabel' => 'bahan',           'label' => 'Bahan',           'kolom_file' => 'gambar',      'folder' => 'produk', 'pakai_tabel' => 'detail_custom',  'pakai_kolom' => 'bahan_id'],
        'desain' => ['tabel' => 'template_desain', 'label' => 'Template desain', 'kolom_file' => 'file_desain', 'folder' => 'desain', 'pakai_tabel' => 'detail_custom',  'pakai_kolom' => 'template_desain_id'],
    ];

    return $daftar[$jenis];
}

/**
 * Menghapus satu baris data master beserta berkasnya, lalu menyiapkan pesan
 * flash yang sesuai. Data yang pernah dipesan hanya dinonaktifkan agar
 * riwayat transaksi dan Laporan Penjualan Periodik tetap utuh.
 *
 * Nama tabel dan kolom berasal dari data_master(), bukan dari masukan
 * pengguna, sehingga aman disisipkan langsung ke dalam kueri.
 */
function hapus_master(PDO $pdo, $jenis, $id)
{
    $master = data_master($jenis);

    $stmt = $pdo->prepare("SELECT {$master['kolom_file']} AS berkas FROM {$master['tabel']} WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        set_flash('danger', $master['label'] . ' tidak ditemukan.');
        return;
    }

    $cek = $pdo->prepare("SELECT COUNT(*) FROM {$master['pakai_tabel']} WHERE {$master['pakai_kolom']} = ?");
    $cek->execute([$id]);

    if ((int) $cek->fetchColumn() > 0) {
        $pdo->prepare("UPDATE {$master['tabel']} SET status = 'nonaktif' WHERE id = ?")->execute([$id]);
        set_flash('warning', $master['label'] . ' pernah dipesan sehingga tidak dapat dihapus. '
            . 'Status diubah menjadi nonaktif.');
        return;
    }

    $pdo->prepare("DELETE FROM {$master['tabel']} WHERE id = ?")->execute([$id]);
    hapus_file($master['folder'], $row['berkas']);

    set_flash('success', $master['label'] . ' berhasil dihapus.');
}

// ---------------------------------------------------------------------------
// Potongan tampilan yang berulang
// ---------------------------------------------------------------------------
// Fungsi berikut mengembalikan HTML. Potongan sekecil ini dijadikan
// fungsi, bukan partial di includes/, karena partial menuntut penetapan
// beberapa variabel sebelum setiap penyertaan sehingga justru lebih panjang
// daripada barisnya sendiri.

/**
 * Kotak pratinjau berkas pada tabel data master.
 *
 * Menangani ketiga keadaan yang sebelumnya ditulis ulang di lima berkas:
 * gambar biasa, berkas PDF yang tidak bisa ditampilkan sebagai gambar, dan
 * baris yang memang belum punya berkas.
 */
function thumb_berkas($folder, $nama, $ukuran = 48)
{
    $kotak = 'width: ' . (int) $ukuran . 'px; height: ' . (int) $ukuran . 'px;';

    // Berkas yang tercatat tetapi hilang dari cakram diperlakukan sama seperti
    // yang memang belum punya berkas, supaya tabel tidak dipenuhi ikon gambar
    // rusak yang justru menyulitkan admin membaca datanya.
    if (!berkas_unggahan_ada($folder, $nama)) {
        return '<div class="bg-secondary-subtle rounded" style="' . $kotak . '"></div>';
    }

    if (strtolower(pathinfo((string) $nama, PATHINFO_EXTENSION)) === 'pdf') {
        return '<div class="bg-danger-subtle text-danger-emphasis rounded d-flex '
            . 'align-items-center justify-content-center small" style="' . $kotak . '">PDF</div>';
    }

    return '<img src="' . BASE_URL . '/uploads/' . $folder . '/' . e($nama) . '"'
        . ' alt="" class="rounded" style="' . $kotak . ' object-fit: cover;">';
}

// ---------------------------------------------------------------------------
// Gambar bagian keunggulan pada beranda
// ---------------------------------------------------------------------------

/**
 * Ketiga tempat gambar pada beranda beserta keterangannya.
 *
 * Satu-satunya sumber kebenaran: index.php memakainya untuk menampilkan,
 * admin/pengaturan.php memakainya untuk membangun form unggahan. Menambah
 * atau mengganti tempat gambar cukup dilakukan di sini.
 *
 * Nama berkasnya tetap (kualitas-1, kustomisasi, ...) sehingga tidak perlu
 * kolom baru di basis data untuk mengingatnya. Ekstensinya boleh apa saja
 * di antara yang diizinkan; gambar_beranda() yang mencarikannya.
 */
function slot_gambar_beranda($slot = null)
{
    $daftar = [
        'kualitas-1' => [
            'label' => 'Kualitas Premium',
            'alt'   => 'Tas souvenir berbahan kanvas premium tampak depan',
        ],
        'kustomisasi' => [
            'label' => 'Kustomisasi Tanpa Batas',
            'alt'   => 'Contoh tas souvenir dengan sablon desain khusus',
        ],
        'harga-otomatis' => [
            'label' => 'Harga Terhitung Otomatis',
            'alt'   => 'Ringkasan harga custom order yang dihitung otomatis oleh sistem',
        ],
    ];

    return $slot === null ? $daftar : ($daftar[$slot] ?? null);
}

/**
 * Nama berkas gambar beranda untuk satu tempat, atau null bila belum ada.
 * Ekstensinya dicari satu per satu supaya admin bebas mengunggah JPG, PNG,
 * maupun WEBP tanpa perlu diseragamkan lebih dulu.
 */
function gambar_beranda($slot)
{
    if (slot_gambar_beranda($slot) === null) {
        return null;
    }

    foreach (ekstensi_diizinkan('beranda') as $ext) {
        if (is_file(UPLOAD_PATH . 'beranda/' . $slot . '.' . $ext)) {
            return $slot . '.' . $ext;
        }
    }

    return null;
}

/** Membuang gambar beranda pada satu tempat, apa pun ekstensinya. */
function hapus_gambar_beranda($slot)
{
    $lama = gambar_beranda($slot);

    if ($lama !== null) {
        hapus_file('beranda', $lama);
    }
}

/**
 * Menyimpan gambar beranda yang diunggah admin.
 *
 * Berkasnya lewat upload_file() lebih dulu agar tipe, ukuran, dan isinya
 * diperiksa dengan aturan yang sama seperti unggahan lain, baru kemudian
 * diganti namanya menjadi nama tetap milik tempat itu.
 *
 * Mengembalikan pesan galat, atau string kosong bila berhasil maupun bila
 * memang tidak ada berkas yang dikirim.
 */
function simpan_gambar_beranda($file, $slot)
{
    $info = slot_gambar_beranda($slot);

    // Nama tempat dipakai menyusun nama berkas, jadi wajib berasal dari
    // katalog di atas - bukan dari kiriman form apa adanya.
    if ($info === null) {
        return 'Tempat gambar tidak dikenali.';
    }

    if (!is_array($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return '';
    }

    $baru = upload_file($file, 'beranda');

    if ($baru === false) {
        return 'Gambar ' . $info['label'] . ' gagal diunggah. '
            . 'Gunakan format JPG, PNG, atau WEBP maksimal 2 MB.';
    }

    $ext    = strtolower(pathinfo($baru, PATHINFO_EXTENSION));
    $tujuan = UPLOAD_PATH . 'beranda/' . $slot . '.' . $ext;

    hapus_gambar_beranda($slot);

    if (!rename(UPLOAD_PATH . 'beranda/' . $baru, $tujuan)) {
        // Berkas sementara dibuang supaya tidak menumpuk tanpa dirujuk.
        hapus_file('beranda', $baru);
        return 'Gambar ' . $info['label'] . ' gagal disimpan.';
    }

    return '';
}

/**
 * Kolom gambar pada satu baris keunggulan di beranda.
 *
 * Selama gambarnya belum diunggah, fungsi ini tidak mengembalikan apa pun -
 * termasuk pembungkus .fitur-gambar-nya. Itu disengaja: kalau hanya isinya
 * yang dikosongkan, pembungkusnya tetap merebut separuh lebar baris dan
 * menyisakan ruang menganga. Dengan seluruh kolom absen, teksnya memakai
 * lebar penuh dan baris itu tetap terlihat utuh.
 */
function petak_fitur($slot)
{
    $berkas = gambar_beranda($slot);

    if ($berkas === null) {
        return '';
    }

    $info = slot_gambar_beranda($slot);

    return '<div class="fitur-gambar"><div class="petak">'
        . '<img src="' . aset('uploads/beranda/' . $berkas) . '"'
        . ' alt="' . e($info['alt']) . '" loading="lazy">'
        . '</div></div>';
}
