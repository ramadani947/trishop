<?php
/**
 * includes/flash.php
 * Menampilkan pesan flash bila memang ada.
 *
 * Dipakai bersama oleh kerangka pelanggan (includes/header.php), kerangka
 * admin (includes/admin_header.php), dan beranda yang mengatur wadahnya
 * sendiri (index.php), supaya ketiganya tidak pernah tampil berbeda.
 */

if ($flash = get_flash()): ?>
    <div class="alert alert-<?= e($flash['tipe']) ?> alert-dismissible fade show">
        <?= e($flash['pesan']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
