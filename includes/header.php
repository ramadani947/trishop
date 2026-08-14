<?php
/**
 * includes/header.php
 * Kerangka atas seluruh halaman pelanggan: <head>, navigasi utama,
 * dan pembuka area konten.
 *
 * Sebelum memanggil berkas ini, halaman boleh mendefinisikan:
 *   $judulHalaman  - judul pada tab peramban (wajib secara praktis)
 *   $menuAktif     - 'beranda' | 'ready_stock' | 'custom' | 'keranjang' | 'pesanan' | 'profil'
 *   $lebarPenuh    - true bila halaman ingin mengatur wadahnya sendiri
 */

if (!isset($judulHalaman)) {
    $judulHalaman = 'Tri Shop Souvenir';
}
if (!isset($menuAktif)) {
    $menuAktif = '';
}
if (!isset($lebarPenuh)) {
    $lebarPenuh = false;
}

$navUser = user_aktif();

// Penanda jumlah item keranjang. Halaman boleh menghitungnya lebih dulu;
// bila belum, dihitung di sini.
if (!isset($jumlahKeranjang)) {
    $jumlahKeranjang = $navUser ? jumlah_keranjang($pdo, $navUser['id']) : 0;
}

// Nomor WhatsApp admin diambil dari Pengaturan Toko supaya dapat diubah
// lewat panel admin, bukan ditanam di dalam kode. Bila belum diisi,
// tombolnya tidak ditampilkan sama sekali.
$waToko = tautan_whatsapp(
    $pdo->query("SELECT no_telp FROM pengaturan_toko LIMIT 1")->fetchColumn(),
    'Halo Tri Shop Souvenir, saya ingin bertanya tentang pesanan tas.'
);

/** Menandai menu yang sedang dibuka. */
function nav_aktif($nama)
{
    global $menuAktif;
    return $menuAktif === $nama ? ' aktif' : '';
}
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
<body>

<a class="lompat-konten" href="#konten-utama">Lompat ke konten</a>

<header class="bilah-atas">
    <div class="container-wide">
        <nav class="navbar navbar-expand-lg p-0">

            <div class="kepala-merek">
                <?php if ($waToko): ?>
                    <a class="tombol-wa" href="<?= e($waToko) ?>"
                       target="_blank" rel="noopener noreferrer"
                       title="Hubungi admin lewat WhatsApp"
                       aria-label="Hubungi admin lewat WhatsApp">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/>
                        </svg>
                    </a>
                <?php endif; ?>

                <!-- Instagram berdampingan dengan WhatsApp sebagai sepasang
                     tautan kontak. Hanya logonya; nama penggunanya sudah
                     terbaca dari halaman yang dituju. -->
                <a class="tombol-ig" href="<?= e(tautan_instagram()) ?>"
                   target="_blank" rel="noopener noreferrer"
                   title="Buka profil Instagram @<?= e(IG_USERNAME) ?>"
                   aria-label="Buka profil Instagram @<?= e(IG_USERNAME) ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41-.56-.22-.96-.48-1.38-.9-.42-.42-.68-.82-.9-1.38-.16-.42-.36-1.06-.41-2.23-.06-1.27-.07-1.65-.07-4.85s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63c-.79.3-1.46.72-2.13 1.38C1.35 2.68.93 3.35.63 4.14.33 4.9.13 5.78.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.27.26 2.15.56 2.91.3.79.72 1.46 1.38 2.13.67.66 1.34 1.08 2.13 1.38.76.3 1.64.5 2.91.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.27-.06 2.15-.26 2.91-.56.79-.3 1.46-.72 2.13-1.38.66-.67 1.08-1.34 1.38-2.13.3-.76.5-1.64.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.27-.26-2.15-.56-2.91-.3-.79-.72-1.46-1.38-2.13C21.32 1.35 20.65.93 19.86.63 19.1.33 18.22.13 16.95.07 15.67.01 15.26 0 12 0z"/>
                        <path d="M12 5.84A6.16 6.16 0 1 0 18.16 12 6.16 6.16 0 0 0 12 5.84zm0 10.16A4 4 0 1 1 16 12a4 4 0 0 1-4 4z"/>
                        <circle cx="18.41" cy="5.59" r="1.44"/>
                    </svg>
                </a>

                <a class="merek" href="<?= BASE_URL ?>/index.php">Tri Shop Souvenir</a>
            </div>

            <button class="navbar-toggler border-0 shadow-none" type="button"
                    data-bs-toggle="collapse" data-bs-target="#menu-utama"
                    aria-controls="menu-utama" aria-expanded="false" aria-label="Buka menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menu-utama">

                <ul class="navbar-nav mx-lg-auto">
                    <li class="nav-item">
                        <a class="nav-link<?= nav_aktif('beranda') ?>"
                           href="<?= BASE_URL ?>/index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= nav_aktif('ready_stock') ?>"
                           href="<?= BASE_URL ?>/pelanggan/ready_stock.php">Ready Stock</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= nav_aktif('custom') ?>"
                           href="<?= BASE_URL ?>/pelanggan/custom_order.php">Custom Order</a>
                    </li>
                    <?php if ($navUser): ?>
                        <li class="nav-item">
                            <a class="nav-link<?= nav_aktif('pesanan') ?>"
                               href="<?= BASE_URL ?>/pelanggan/pesanan_saya.php">Pesanan Saya</a>
                        </li>
                    <?php endif; ?>
                </ul>

                <div class="aksi-atas">
                    <?php if ($navUser): ?>

                        <a class="btn-garis tombol-ikon-keranjang<?= nav_aktif('keranjang') ?>"
                           href="<?= BASE_URL ?>/pelanggan/keranjang.php"
                           data-tautan-keranjang aria-label="Keranjang belanja">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 4h2l2.4 12.2a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.6L21 8H6"
                                      stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="10" cy="20.5" r="1.4" fill="currentColor"/>
                                <circle cx="17.5" cy="20.5" r="1.4" fill="currentColor"/>
                            </svg>
                            <?php if ($jumlahKeranjang > 0): ?><span class="lencana-keranjang"><?= $jumlahKeranjang ?></span><?php endif; ?>
                        </a>

                        <div class="dropdown dropdown-profil-admin">
                            <button class="tombol-profil-admin<?= nav_aktif('profil') ?>" type="button" id="dropdownProfilPelanggan"
                                    data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu profil <?= e($navUser['nama']) ?>">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8"/>
                                    <path d="M4 20c0-3.6 3.6-6.5 8-6.5s8 2.9 8 6.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-profil-admin" aria-labelledby="dropdownProfilPelanggan">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/pelanggan/profil.php"><?= e($navUser['nama']) ?></a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item dropdown-item-bahaya" href="<?= BASE_URL ?>/auth/logout.php">Logout</a></li>
                            </ul>
                        </div>

                    <?php else: ?>

                        <a class="btn-garis" href="<?= BASE_URL ?>/auth/login.php">Login</a>

                    <?php endif; ?>
                </div>

            </div>
        </nav>
    </div>
</header>

<main id="konten-utama">
<?php if (!$lebarPenuh): ?>
<div class="container py-5">
    <?php require __DIR__ . '/flash.php'; ?>
<?php endif; ?>
