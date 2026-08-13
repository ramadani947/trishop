<?php
/**
 * login.php
 * Pengalih ke halaman login yang sebenarnya di auth/login.php.
 * Disediakan agar URL singkat /trishop/login.php tidak menghasilkan 404.
 */

require_once __DIR__ . '/includes/init.php';

// Pengguna yang sudah login langsung diarahkan ke halaman utamanya.
if (is_login()) {
    redirect(is_admin() ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/index.php');
}

// Pilihan peran pada URL tetap diteruskan, mis. /trishop/login.php?role=admin
$role = $_GET['role'] ?? '';
$tujuan = BASE_URL . '/auth/login.php';
if (in_array($role, ['pelanggan', 'admin'], true)) {
    $tujuan .= '?role=' . $role;
}

redirect($tujuan);
