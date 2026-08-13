<?php
/**
 * includes/upload.php
 * Fungsi bantu unggahan berkas: validasi tipe/ukuran, penyimpanan,
 * penggantian, dan penghapusan berkas di folder uploads/.
 * Dimuat otomatis oleh includes/init.php.
 */

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
