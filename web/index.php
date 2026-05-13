<?php
/**
 * Entry point: redirect ke dashboard sesuai role atau ke login.
 */
require_once __DIR__ . '/includes/bootstrap.php';

if (!is_logged_in()) {
    redirect('auth/login.php');
}
$u = current_user();
if ($u['role'] === 'Admin') {
    redirect('admin/dashboard.php');
}
redirect('petugas/dashboard.php');
