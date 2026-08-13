<?php
/**
 * includes/functions.php
 * Kumpulan fungsi bantu yang dipakai di seluruh sistem.
 * Dimuat otomatis oleh includes/init.php.
 */

// ---------------------------------------------------------------------------
// Tampilan dan navigasi
// ---------------------------------------------------------------------------

/** Format angka menjadi format Rupiah. */
function rupiah($angka)
{
    return 'Rp' . number_format((float) $angka, 0, ',', '.');
}

/** Format email valid? Dipakai bersama oleh register, profil pelanggan,
 *  dan pengaturan toko supaya aturan validasinya tidak diketik ulang. */
function email_valid($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** Mencegah XSS pada output ke halaman. */
function e($teks)
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

/** Redirect ke URL tertentu lalu hentikan eksekusi. */
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

/**
 * Alamat berkas aset lokal beserta penanda versinya.
 *
 * Penanda diambil dari waktu ubah berkasnya. Peramban tetap memakai salinan
 * simpanannya selama berkas tidak berubah, lalu mengambil yang baru begitu
 * berkasnya benar-benar diubah.
 *
 * Ini bukan hiasan: ketika tata letak sidebar dipindahkan dari <style>
 * sisipan ke assets/css/style.css, peramban yang masih memegang style.css
 * lama menampilkan panel admin tanpa tata letak sama sekali, dan satu-satunya
 * jalan keluar adalah menekan Ctrl+F5. Dengan penanda ini hal itu tidak
 * mungkin terulang untuk perubahan CSS maupun JS berikutnya.
 */
function aset($path)
{
    $path   = ltrim($path, '/');
    $berkas = __DIR__ . '/../' . $path;
    $versi  = is_file($berkas) ? filemtime($berkas) : null;

    return BASE_URL . '/' . $path . ($versi ? '?v=' . $versi : '');
}

/** Simpan pesan flash untuk ditampilkan pada halaman berikutnya. */
function set_flash($tipe, $pesan)
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

/** Ambil sekaligus hapus pesan flash. */
function get_flash()
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// ---------------------------------------------------------------------------
// Kontak toko
// ---------------------------------------------------------------------------

/** Alamat profil Instagram toko. */
function tautan_instagram()
{
    return 'https://www.instagram.com/' . IG_USERNAME . '/';
}

/**
 * Mengubah nomor telepon Indonesia menjadi format yang diterima wa.me,
 * yaitu kode negara tanpa tanda plus: 081234567890 menjadi 6281234567890.
 *
 * Mengembalikan null bila nomornya kosong atau terlalu pendek untuk
 * dianggap nomor yang sah, sehingga pemanggil dapat menyembunyikan tautan.
 */
function nomor_whatsapp($noTelp)
{
    $angka = preg_replace('/\D+/', '', (string) $noTelp);

    if ($angka === '') {
        return null;
    }

    if (strpos($angka, '0') === 0) {
        $angka = '62' . substr($angka, 1);
    } elseif (strpos($angka, '62') !== 0) {
        // Nomor lokal tanpa awalan 0, misalnya 81234567890.
        $angka = '62' . $angka;
    }

    // 62 + minimal 9 digit. Lebih pendek dari itu pasti bukan nomor ponsel.
    return strlen($angka) >= 11 ? $angka : null;
}

/**
 * Alamat chat WhatsApp toko, lengkap dengan pesan pembuka opsional.
 * Mengembalikan null bila nomor toko belum diisi pada Pengaturan Toko.
 */
function tautan_whatsapp($noTelp, $pesan = '')
{
    $nomor = nomor_whatsapp($noTelp);

    if ($nomor === null) {
        return null;
    }

    return 'https://wa.me/' . $nomor
        . ($pesan !== '' ? '?text=' . rawurlencode($pesan) : '');
}

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

// ---------------------------------------------------------------------------
// Berkas unggahan
// ---------------------------------------------------------------------------

/**
 * Ekstensi yang boleh masuk ke setiap subfolder unggahan.
 *
 * Isi folder produk selalu disajikan lewat <img>, baik di katalog, tabel
 * admin, maupun kartu pilihan custom order. PDF tidak punya tempat di sana:
 * begitu tersimpan, ia hanya tampil sebagai gambar rusak. Folder desain dan
 * custom memang menerima PDF, sebab template dan desain kiriman pelanggan
 * lazim berbentuk itu dan penyajiannya sudah disiapkan.
 *
 * Subfolder yang belum terdaftar diperlakukan paling ketat, supaya folder
 * baru tidak diam-diam mewarisi izin yang paling longgar.
 */
function ekstensi_diizinkan($subfolder)
{
    $gambar = ['jpg', 'jpeg', 'png', 'webp'];

    $perFolder = [
        'produk'  => $gambar,
        'beranda' => $gambar,
        'desain'  => array_merge($gambar, ['pdf']),
        'custom'  => array_merge($gambar, ['pdf']),
    ];

    return $perFolder[$subfolder] ?? $gambar;
}

/**
 * Unggah file gambar/desain dengan validasi tipe dan ukuran.
 * Mengembalikan nama file baru, atau false bila gagal.
 */
function upload_file($file, $subfolder, $maksUkuran = 2097152)
{
    if (!is_array($file) || $file['error'] !== UPLOAD_ERR_OK || $file['size'] > $maksUkuran) {
        return false;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ekstensi_diizinkan($subfolder), true)) {
        return false;
    }

    // Isi berkas ikut diperiksa, bukan cuma namanya. Tanpa langkah ini berkas
    // apa pun tinggal diganti namanya menjadi .png untuk lolos, lalu berakhir
    // sebagai gambar rusak di katalog. Ekstensi dan isi juga harus sejalan,
    // supaya PDF tidak tersimpan dengan nama .png di folder desain.
    $mime = mime_content_type($file['tmp_name']);

    if ($ext === 'pdf' ? $mime !== 'application/pdf' : strpos((string) $mime, 'image/') !== 0) {
        return false;
    }

    $tujuan = UPLOAD_PATH . $subfolder . '/';
    if (!is_dir($tujuan)) {
        mkdir($tujuan, 0777, true);
    }

    $namaBaru = uniqid($subfolder . '_', true) . '.' . $ext;

    return move_uploaded_file($file['tmp_name'], $tujuan . $namaBaru) ? $namaBaru : false;
}

/**
 * Apakah berkas unggahan yang tercatat di basis data benar-benar ada?
 *
 * Nama berkas dan berkas fisiknya bisa berpisah: baris contoh yang ditulis
 * langsung ke basis data, berkas yang terhapus manual dari folder, atau
 * pemindahan proyek tanpa membawa isi uploads/. Tanpa pemeriksaan ini
 * halaman menampilkan ikon gambar rusak dan tautan yang berujung 404.
 */
function berkas_unggahan_ada($subfolder, $nama)
{
    return (string) $nama !== '' && is_file(UPLOAD_PATH . $subfolder . '/' . $nama);
}

/** Menghapus satu berkas unggahan bila memang ada. */
function hapus_file($subfolder, $nama)
{
    $path = UPLOAD_PATH . $subfolder . '/' . $nama;

    if ($nama && is_file($path)) {
        unlink($path);
    }
}

/**
 * Mengganti berkas unggahan: menyimpan yang baru lalu membuang yang lama
 * agar folder uploads tidak menumpuk.
 *
 * Mengembalikan nama berkas yang harus disimpan ke basis data. Bila tidak ada
 * berkas baru yang dikirim, nama lama dikembalikan apa adanya. Mengembalikan
 * false bila unggahan gagal, sehingga pemanggil dapat menampilkan galat.
 */
function ganti_file($file, $subfolder, $namaLama = null)
{
    if (!is_array($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return $namaLama;
    }

    $baru = upload_file($file, $subfolder);

    if ($baru === false) {
        return false;
    }

    hapus_file($subfolder, $namaLama);

    return $baru;
}

/**
 * ganti_file() beserta penanganan galatnya untuk dipakai form admin.
 *
 * Kelima form unggahan (produk, bahan, model tas, template desain, dan logo
 * toko) sebelumnya menuliskan langkah yang persis sama: coba ganti berkas,
 * lalu bila gagal tampilkan pesan sekaligus mempertahankan berkas lama.
 *
 * Mengembalikan [namaBerkas, pesanGalat]. pesanGalat berisi string kosong
 * bila tidak ada masalah, sehingga hasilnya bisa langsung dipasangkan ke
 * variabel $error milik halaman.
 */
function ganti_file_form($file, $subfolder, $namaLama, $label = 'Gambar', $format = 'JPG, PNG, atau WEBP')
{
    $baru = ganti_file($file, $subfolder, $namaLama);

    if ($baru === false) {
        return [$namaLama, $label . ' gagal diunggah. Gunakan format ' . $format . ' maksimal 2 MB.'];
    }

    return [$baru, ''];
}

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
// Ketiga fungsi berikut mengembalikan HTML. Potongan sekecil ini dijadikan
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

/** Lencana status aktif atau nonaktif pada tabel data master. */
function badge_status($status)
{
    return '<span class="badge bg-' . ($status === 'aktif' ? 'success' : 'secondary') . '">'
        . e(ucfirst($status)) . '</span>';
}

/**
 * Form hapus satu baris data master, lengkap dengan konfirmasinya.
 * Tombol Edit di sebelahnya tetap milik masing-masing halaman karena
 * alamat tujuannya berbeda-beda.
 *
 * Pesan konfirmasi dilewatkan json_encode() lebih dulu, baru kemudian e().
 * Tanpa itu, nama data yang mengandung petik tunggal (misal "Tas Ana's")
 * memutus string JavaScript di dalam atribut onsubmit sehingga tombol Hapus
 * berhenti bekerja sama sekali.
 */
function tombol_hapus($id, $konfirmasi)
{
    $pesan = e(json_encode((string) $konfirmasi, JSON_UNESCAPED_UNICODE));

    return '<form method="post" onsubmit="return confirm(' . $pesan . ');">'
        . '<input type="hidden" name="aksi" value="hapus">'
        . '<input type="hidden" name="id" value="' . (int) $id . '">'
        . '<button class="btn btn-sm btn-outline-danger">Hapus</button>'
        . '</form>';
}
