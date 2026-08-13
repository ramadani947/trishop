<?php
/**
 * register.php
 * Pengalih ke halaman registrasi yang sebenarnya di auth/register.php.
 * Disediakan agar URL singkat /trishop/register.php tidak menghasilkan 404.
 */

require_once __DIR__ . '/includes/init.php';

// Pengguna yang sudah login tidak perlu mendaftar lagi.
if (is_login()) {
    redirect(is_admin() ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/index.php');
}

redirect(BASE_URL . '/auth/register.php');
