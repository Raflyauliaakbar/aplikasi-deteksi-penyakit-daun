<?php
require_once __DIR__ . '/../includes/bootstrap.php';
logout_user();
flash('success', 'Anda telah keluar dari sistem.');
redirect('auth/login.php');
