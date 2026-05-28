<?php
function is_logged_in(): bool { return !empty($_SESSION['user']['id_user']); }
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void { if (!is_logged_in()) { flash('warning', 'Silakan login terlebih dahulu.'); redirect('auth/login.php'); } }
function require_role(string $role): void { require_login(); $u = current_user(); if (($u['role']??'') !== $role) { http_response_code(403); flash('error', 'Akses ditolak.'); redirect(($u['role']??'')==='Admin'?'admin/dashboard.php':'petugas/dashboard.php'); } }
function login_user(array $user): void { session_regenerate_id(true); $_SESSION['user'] = ['id_user'=>(int)$user['id_user'],'username'=>$user['username'],'nama_lengkap'=>$user['nama_lengkap'],'role'=>$user['role']]; }
function logout_user(): void { $_SESSION=[]; if(ini_get('session.use_cookies')){$p=session_get_cookie_params();setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);} session_destroy(); }
