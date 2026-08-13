<?php
/**
 * auth/logout.php
 * Mengakhiri sesi pengguna lalu mengarahkan kembali ke halaman login.
 */

require_once __DIR__ . '/../includes/init.php';

logout_user();

session_start();
set_flash('success', 'Anda telah keluar dari sistem.');

redirect(BASE_URL . '/auth/login.php');
