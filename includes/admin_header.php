<?php
/**
 * includes/admin_header.php
 * Kerangka atas halaman admin: sidebar, topbar, dan pembuka konten.
 *
 * Sebelum memanggil file ini, halaman admin wajib mendefinisikan
 * variabel $judulHalaman dan $menuAktif.
 */

if (!isset($judulHalaman)) {
    $judulHalaman = 'Dashboard';
}
if (!isset($menuAktif)) {
    $menuAktif = '';
}

$menu = [
    'dashboard' => ['label' => 'Dashboard',                'url' => '/admin/dashboard.php'],
    'produk'    => ['label' => 'Produk Ready Stock',       'url' => '/admin/produk.php'],
    'model'     => ['label' => 'Model Tas',                'url' => '/admin/model_tas.php'],
    'bahan'     => ['label' => 'Bahan',                    'url' => '/admin/bahan.php'],
    'desain'    => ['label' => 'Template Desain',          'url' => '/admin/template_desain.php'],
    'pesanan'   => ['label' => 'Pesanan',                  'url' => '/admin/pesanan.php'],
    'laporan'   => ['label' => 'Laporan Penjualan',        'url' => '/admin/laporan.php'],
];

$admin = user_aktif();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judulHalaman) ?> &mdash; Admin Tri Shop Souvenir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= aset("assets/css/style.css") ?>" rel="stylesheet">
</head>
<body>

<a class="lompat-konten" href="#konten-admin-utama">Lompat ke konten</a>

<!-- Bilah admin memakai kerangka Bootstrap navbar yang sama persis dengan
     bilah pelanggan (includes/header.php): kapsul kaca melayang, menu
     tengah yang runtuh menjadi off-canvas hamburger di layar sempit, dan
     kelompok aksi di kanan. Ini supaya kedua bilah terasa satu keluarga
     desain, bukan dua sistem yang kebetulan mirip. -->
<header class="bilah-atas bilah-atas-admin">
    <div class="container-wide">
        <nav class="navbar navbar-expand-lg p-0">

            <div class="kepala-merek">
                <div class="dropdown dropdown-profil-admin">
                    <button class="tombol-profil-admin" type="button" id="dropdownProfilAdmin"
                            data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu profil admin">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8"/>
                            <path d="M4 20c0-3.6 3.6-6.5 8-6.5s8 2.9 8 6.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-profil-admin" aria-labelledby="dropdownProfilAdmin">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/pengaturan.php">Pengaturan Toko</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item dropdown-item-bahaya" href="<?= BASE_URL ?>/auth/logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>

            <button class="navbar-toggler border-0 shadow-none" type="button"
                    data-bs-toggle="collapse" data-bs-target="#menu-admin-utama"
                    aria-controls="menu-admin-utama" aria-expanded="false" aria-label="Buka menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menu-admin-utama">

                <ul class="navbar-nav mx-lg-auto menu-admin-nav">
                    <?php foreach ($menu as $key => $item): ?>
                        <li class="nav-item">
                            <a class="nav-link<?= $menuAktif === $key ? ' aktif' : '' ?>"
                               href="<?= BASE_URL . $item['url'] ?>"><?= e($item['label']) ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>

            </div>
        </nav>
    </div>
</header>

<div id="konten">

    <main id="konten-admin-utama" class="p-4">

        <?php require __DIR__ . '/flash.php'; ?>
