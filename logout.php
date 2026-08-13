<?php
/**
 * logout.php
 * Pengalih ke proses logout yang sebenarnya di auth/logout.php.
 * Disediakan agar URL singkat /trishop/logout.php tidak menghasilkan 404.
 */

require_once __DIR__ . '/includes/init.php';

redirect(BASE_URL . '/auth/logout.php');
