<?php
/**
 * includes/auth_header.php
 * Kerangka atas halaman masuk dan daftar.
 *
 * Kedua halaman ini berdiri sendiri, di luar kerangka pelanggan, karena
 * belum ada pengguna yang login sehingga navigasi dan keranjang tidak
 * relevan. Bagian yang sebelumnya disalin di keduanya dikumpulkan di sini.
 *
 * Tata letak: satu layar penuh. Begitu admin mengunggah foto lewat
 * Pengaturan, foto itu memenuhi seluruh layar sebagai latar, dengan
 * sapaan toko tertulis di kiri dan kartu login kaca mengambang di
 * kanan. Sebelum ada foto, latar memakai gradien merek sebagai
 * pengganti. Pada layar sempit, kartu login pindah ke bawah logo.
 *
 * Sebelum menyertakan berkas ini, sediakan:
 *   $judulHalaman - judul pada tab peramban
 *   $judulPanel   - judul besar di kepala form
 *   $subJudul     - kalimat pendek di bawahnya
 *   $lebarPanel   - lebar maksimum form, mis. '430px'
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judulHalaman) ?> &mdash; Tri Shop Souvenir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= aset("assets/css/style.css") ?>" rel="stylesheet">
</head>
<body class="auth-body">

<?php $fotoHero = gambar_beranda('login-hero'); ?>
<div class="auth-layar<?= $fotoHero ? ' auth-layar-foto' : '' ?>"
    <?php if ($fotoHero): ?>
        style="background-image: url('<?= aset('uploads/beranda/' . $fotoHero) ?>');"
    <?php endif; ?>
>
    <?php if (!$fotoHero): ?>
        <div class="auth-merek-blob auth-merek-blob-1"></div>
        <div class="auth-merek-blob auth-merek-blob-2"></div>
    <?php endif; ?>

    <a href="<?= BASE_URL ?>/index.php" class="auth-merek-atas">
        <span class="auth-logo">TS</span>
        <span class="auth-merek-nama">Tri Shop Souvenir</span>
    </a>

    <div class="auth-merek-tengah">
        <p class="auth-merek-rintik">Suvenir &amp; Tas Custom Surabaya</p>
        <h1 class="auth-merek-judul">Buat tas impianmu,<br>bawa pulang ceritanya.</h1>
        <p class="auth-merek-teks">Ready stock berkualitas dan custom order sesuai desainmu, dikerjakan langsung oleh pengrajin lokal.</p>
    </div>

    <p class="auth-merek-bawah">&copy; <?= date('Y') ?> Tri Shop Souvenir</p>

    <main class="auth-panel-form">
        <div class="auth-form-wrap" style="max-width: <?= e($lebarPanel) ?>;">

            <div class="auth-kepala-mobile">
                <span class="auth-logo auth-logo-kecil">TS</span>
                <span class="auth-merek-nama">Tri Shop Souvenir</span>
            </div>

            <div class="auth-form-isi">

                <div class="auth-kepala">
                    <?php if ($judulPanel !== ''): ?>
                        <h2><?= e($judulPanel) ?></h2>
                    <?php endif; ?>
                    <p><?= e($subJudul) ?></p>
                </div>
