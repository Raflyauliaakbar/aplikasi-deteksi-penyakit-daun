<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (!is_logged_in()) redirect('auth/login.php');
$u = current_user();
redirect($u['role'] === 'Admin' ? 'admin/dashboard.php' : 'petugas/dashboard.php');
