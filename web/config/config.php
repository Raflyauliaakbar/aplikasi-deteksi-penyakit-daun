<?php
/**
 * Konfigurasi global aplikasi.
 * Ubah nilai di sini saat deploy.
 */

// --- Base URL & Path ------------------------------------------------------
// BASE_URL otomatis terdeteksi. Override jika menggunakan subdirectory.
if (!defined('BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Jika aplikasi ada di http://localhost/aplikasi-deteksi-penyakit-daun/web/
    // ganti baris ini dengan: define('BASE_URL', '/aplikasi-deteksi-penyakit-daun/web');
    $script = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    // Normalisasi: cari posisi "/web" paling kanan agar subfolder admin/petugas tetap benar.
    $pos = strrpos($script, '/web');
    if ($pos !== false) {
        $base = substr($script, 0, $pos + 4);
    } else {
        $base = rtrim($script, '/');
    }
    define('BASE_URL', $scheme . '://' . $host . $base);
}

// --- Flask AI API ---------------------------------------------------------
define('FLASK_API_URL', 'http://127.0.0.1:5000/predict');
define('FLASK_TIMEOUT', 30); // detik

// --- Upload --------------------------------------------------------------
define('UPLOAD_DIR_ORIGINAL', __DIR__ . '/../uploads/original/');
define('UPLOAD_DIR_DETECTED', __DIR__ . '/../uploads/detected/');
define('UPLOAD_URL_ORIGINAL', BASE_URL . '/uploads/original/');
define('UPLOAD_URL_DETECTED', BASE_URL . '/uploads/detected/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_MIME', ['image/jpeg', 'image/png', 'image/jpg']);

// --- App -----------------------------------------------------------------
define('APP_NAME', 'Deteksi Penyakit Daun Jeruk');
date_default_timezone_set('Asia/Jakarta');

// --- Error reporting (ubah ke false saat production) ---------------------
define('APP_DEBUG', true);
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Pastikan folder upload ada
foreach ([UPLOAD_DIR_ORIGINAL, UPLOAD_DIR_DETECTED] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}
