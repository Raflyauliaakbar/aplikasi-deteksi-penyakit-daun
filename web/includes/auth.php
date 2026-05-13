<?php
/**
 * Helper autentikasi & role guard.
 * Panggil require_login() / require_role('Admin') di awal setiap halaman.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/** Apakah user sudah login? */
function is_logged_in(): bool
{
    return !empty($_SESSION['user']['id_user']);
}

/** Ambil user yang sedang login. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Paksa login, redirect ke halaman login jika belum. */
function require_login(): void
{
    if (!is_logged_in()) {
        flash('warning', 'Silakan masuk terlebih dahulu.');
        redirect('auth/login.php');
    }
}

/** Paksa role tertentu ('Admin' atau 'Petugas'). */
function require_role(string $role): void
{
    require_login();
    $u = current_user();
    if (($u['role'] ?? '') !== $role) {
        http_response_code(403);
        flash('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        // Redirect ke dashboard sesuai role-nya.
        if (($u['role'] ?? '') === 'Admin') {
            redirect('admin/dashboard.php');
        } else {
            redirect('petugas/dashboard.php');
        }
    }
}

/** Login: set session user. */
function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id_user'      => (int) $user['id_user'],
        'username'     => $user['username'],
        'nama_lengkap' => $user['nama_lengkap'],
        'role'         => $user['role'],
    ];
}

/** Logout. */
function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
