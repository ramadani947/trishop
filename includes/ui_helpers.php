<?php
/**
 * includes/ui_helpers.php
 * Potongan HTML kecil yang berulang di banyak halaman admin.
 * Dimuat otomatis oleh includes/init.php.
 */

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
        . csrf_field()
        . '<input type="hidden" name="aksi" value="hapus">'
        . '<input type="hidden" name="id" value="' . (int) $id . '">'
        . '<button class="btn btn-sm btn-outline-danger">Hapus</button>'
        . '</form>';
}
