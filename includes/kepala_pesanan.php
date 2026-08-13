<?php
/**
 * includes/kepala_pesanan.php
 * Kepala kartu satu pesanan: kode, waktu, jenis, dan status.
 *
 * Dipakai bersama oleh admin/pesanan.php dan pelanggan/pesanan_saya.php
 * agar admin dan pelanggan selalu membaca keterangan yang sama persis.
 *
 * Sebelum menyertakan berkas ini, sediakan:
 *   $p - baris pesanan (kode_pesanan, tanggal_pesanan, jenis_pesanan, status)
 */
?>
<div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <span class="fw-semibold kode-pesanan"><?= e($p['kode_pesanan']) ?></span>
        <span class="text-muted small ms-2">
            <?= date('d/m/Y H:i', strtotime($p['tanggal_pesanan'])) ?>
        </span>
    </div>
    <div class="d-flex align-items-center flex-wrap gap-2">
        <span class="badge bg-light text-dark border">
            <?= label_jenis($p['jenis_pesanan']) ?>
        </span>
        <span class="badge bg-<?= warna_status($p['status']) ?>">
            <?= e(label_status($p['status'])) ?>
        </span>
    </div>
</div>
