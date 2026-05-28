<?php
if (!defined('BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    $pos = strrpos($script, '/web');
    $base = $pos !== false ? substr($script, 0, $pos + 4) : rtrim($script, '/');
    define('BASE_URL', $scheme . '://' . $host . $base);
}
define('FLASK_API_URL', 'http://localhost:5000/predict');
define('FLASK_TIMEOUT', 60);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_DIR_ORIGINAL', __DIR__ . '/../uploads/original/');
define('UPLOAD_DIR_DETECTED', __DIR__ . '/../uploads/detected/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png']);
define('ALLOWED_MIME', ['image/jpeg', 'image/png', 'image/jpg']);
define('APP_NAME', 'Deteksi Penyakit Daun Jeruk Siam');
define('APP_VERSION', '2.0.0');
date_default_timezone_set('Asia/Jakarta');
define('APP_DEBUG', true);
if (APP_DEBUG) { error_reporting(E_ALL); ini_set('display_errors', '1'); }
else { error_reporting(0); ini_set('display_errors', '0'); }
foreach ([UPLOAD_DIR_ORIGINAL, UPLOAD_DIR_DETECTED] as $dir) { if (!is_dir($dir)) @mkdir($dir, 0775, true); }
