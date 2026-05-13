<?php
/**
 * Layout header + sidebar.
 * Variabel yang bisa di-set sebelum include:
 *   $page_title (string) - judul halaman di topbar
 *   $active_menu (string) - kunci menu aktif (dashboard, deteksi, riwayat, profil, petugas, penyakit, monitor)
 */
if (!isset($page_title))  $page_title  = APP_NAME;
if (!isset($active_menu)) $active_menu = '';

$u = current_user();
$is_admin = $u && $u['role'] === 'Admin';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title) ?> - <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<div class="app-wrapper">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="brand">
      <i class="bi bi-tree-fill"></i>
      <span>Deteksi Daun Jeruk</span>
    </div>

    <div class="user-box">
      <div class="name"><i class="bi bi-person-circle me-1"></i> <?= e($u['nama_lengkap']) ?></div>
      <div class="role">@<?= e($u['username']) ?> &middot; <?= e($u['role']) ?></div>
    </div>

    <nav class="nav flex-column">
      <?php if ($is_admin): ?>
        <a href="<?= e(url('admin/dashboard.php')) ?>"        class="<?= $active_menu==='dashboard'?'active':'' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="<?= e(url('petugas/deteksi.php')) ?>"        class="<?= $active_menu==='deteksi'?'active':'' ?>"><i class="bi bi-camera"></i> Deteksi Penyakit</a>
        <a href="<?= e(url('petugas/riwayat.php')) ?>"        class="<?= $active_menu==='riwayat'?'active':'' ?>"><i class="bi bi-clock-history"></i> Riwayat Saya</a>
        <hr class="border-light my-2 mx-3 opacity-25">
        <a href="<?= e(url('admin/kelola_petugas.php')) ?>"   class="<?= $active_menu==='petugas'?'active':'' ?>"><i class="bi bi-people"></i> Kelola Petugas</a>
        <a href="<?= e(url('admin/kelola_penyakit.php')) ?>"  class="<?= $active_menu==='penyakit'?'active':'' ?>"><i class="bi bi-journal-medical"></i> Kelola Penyakit</a>
        <a href="<?= e(url('admin/monitor_riwayat.php')) ?>"  class="<?= $active_menu==='monitor'?'active':'' ?>"><i class="bi bi-clipboard-data"></i> Monitor Riwayat</a>
      <?php else: ?>
        <a href="<?= e(url('petugas/dashboard.php')) ?>" class="<?= $active_menu==='dashboard'?'active':'' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="<?= e(url('petugas/deteksi.php')) ?>"   class="<?= $active_menu==='deteksi'?'active':'' ?>"><i class="bi bi-camera"></i> Deteksi Penyakit</a>
        <a href="<?= e(url('petugas/riwayat.php')) ?>"   class="<?= $active_menu==='riwayat'?'active':'' ?>"><i class="bi bi-clock-history"></i> Riwayat Saya</a>
        <a href="<?= e(url('petugas/profil.php')) ?>"    class="<?= $active_menu==='profil'?'active':'' ?>"><i class="bi bi-person-gear"></i> Profil</a>
      <?php endif; ?>
      <a href="<?= e(url('auth/logout.php')) ?>" class="mt-2"><i class="bi bi-box-arrow-right"></i> Keluar</a>
    </nav>

    <div class="sidebar-footer">
      &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>
    </div>
  </aside>

  <!-- Main -->
  <div class="main-content">
    <header class="topbar">
      <button class="btn-toggle-sidebar" type="button" aria-label="Buka menu">
        <i class="bi bi-list"></i>
      </button>
      <h1 class="page-title"><?= e($page_title) ?></h1>
      <div class="text-muted small d-none d-md-block">
        <i class="bi bi-calendar-event"></i> <?= date('d/m/Y H:i') ?>
      </div>
    </header>

    <main class="content">
      <?= render_flashes() ?>
