<?php
/**
 * Helper umum: escape, redirect, flash message, CSRF, format tanggal.
 */

/** HTML-escape. */
function e(?string $v): string
{
    return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirect (terima path relatif atau absolute). */
function redirect(string $path): void
{
    if (strpos($path, 'http') !== 0) {
        $path = rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }
    header('Location: ' . $path);
    exit;
}

/** URL generator sederhana (relatif ke BASE_URL). */
function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

/** Set flash message. */
function flash(string $type, string $msg): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
}

/** Ambil dan kosongkan flash messages. */
function get_flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/** Render seluruh flash messages sebagai alert Bootstrap. */
function render_flashes(): string
{
    $out = '';
    foreach (get_flashes() as $f) {
        $type = match ($f['type']) {
            'success' => 'success',
            'error'   => 'danger',
            'warning' => 'warning',
            default   => 'info',
        };
        $out .= '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
              . e($f['msg'])
              . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>'
              . '</div>';
    }
    return $out;
}

/** CSRF token generator. */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/** CSRF field HTML. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Verifikasi CSRF; abort 419 jika gagal. */
function csrf_verify(): void
{
    $given = $_POST['_csrf'] ?? '';
    if (!hash_equals(csrf_token(), (string) $given)) {
        http_response_code(419);
        die('Token CSRF tidak valid. Silakan muat ulang halaman.');
    }
}

/** Format tanggal Indonesia: 13 Mei 2026, 14:30. */
function format_tgl(?string $datetime): string
{
    if (!$datetime) return '-';
    $bulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $t = strtotime($datetime);
    return date('d', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y, H:i', $t);
}

/** Format persentase akurasi. */
function format_akurasi(float $v): string
{
    return number_format($v * 100, 2) . '%';
}

/** Nama file aman (untuk upload). */
function safe_filename(string $original): string
{
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $ext = preg_replace('/[^a-z0-9]/', '', $ext) ?: 'jpg';
    return date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
}

/** Badge untuk penyakit (warna sederhana biar mudah dibaca). */
function penyakit_badge(string $nama): string
{
    $map = [
        'Healthy'     => 'success',
        'Canker'      => 'danger',
        'HLB'         => 'danger',
        'Greasy Spot' => 'warning',
        'Melanose'    => 'warning',
        'Sooty Mold'  => 'dark',
    ];
    $cls = $map[$nama] ?? 'secondary';
    return '<span class="badge bg-' . $cls . '">' . e($nama) . '</span>';
}
