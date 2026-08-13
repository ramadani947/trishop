<?php
/**
 * includes/auth_header.php
 * Kerangka atas halaman masuk dan daftar.
 *
 * Kedua halaman ini berdiri sendiri, di luar kerangka pelanggan, karena
 * belum ada pengguna yang login sehingga navigasi dan keranjang tidak
 * relevan. Bagian yang sebelumnya disalin di keduanya dikumpulkan di sini.
 *
 * Sebelum menyertakan berkas ini, sediakan:
 *   $judulHalaman - judul pada tab peramban
 *   $judulPanel   - judul besar di kepala kartu
 *   $subJudul     - kalimat pendek di bawahnya
 *   $lebarPanel   - lebar maksimum kartu, mis. '430px'
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judulHalaman) ?> &mdash; Tri Shop Souvenir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= aset("assets/css/style.css") ?>" rel="stylesheet">
</head>
<body class="bg-light">
<div class="latar-auth py-5">
<div class="container" style="max-width: <?= e($lebarPanel) ?>;">

    <div class="card panel-auth">

        <div class="kepala text-center">
            <h1 class="h4"><?= e($judulPanel) ?></h1>
            <p><?= e($subJudul) ?></p>
        </div>

        <div class="card-body p-4">
