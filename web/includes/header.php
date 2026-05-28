<?php
if (!isset($page_title)) $page_title = APP_NAME;
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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<div class="app-wrapper">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand"><i class="bi bi-leaf"></i><span>Deteksi Daun Jeruk</span></div>
    <div class="sidebar-user"><i class="bi bi-person-circle"></i><div><div class="fw-semibold"><?= e($u['nama_lengkap']) ?></div><small class="opacity-75"><?= e($u['role']) ?></small></div></div>
    <nav class="sidebar-nav">
      <?php if ($is_admin): ?>
        <a href="<?= e(url('admin/dashboard.php')) ?>" class="<?= $active_menu==='dashboard'?'active':'' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="<?= e(url('shared/deteksi.php')) ?>" class="<?= $active_menu==='deteksi'?'active':'' ?>"><i class="bi bi-camera"></i> Deteksi Penyakit</a>
        <a href="<?= e(url('shared/riwayat.php')) ?>" class="<?= $active_menu==='riwayat'?'active':'' ?>"><i class="bi bi-clock-history"></i> Riwayat Saya</a>
        <div class="sidebar-divider"></div>
        <a href="<?= e(url('admin/pengguna.php')) ?>" class="<?= $active_menu==='pengguna'?'active':'' ?>"><i class="bi bi-people"></i> Manajemen Pengguna</a>
        <a href="<?= e(url('admin/penyakit.php')) ?>" class="<?= $active_menu==='penyakit'?'active':'' ?>"><i class="bi bi-journal-medical"></i> Info Penyakit</a>
        <a href="<?= e(url('admin/monitoring.php')) ?>" class="<?= $active_menu==='monitoring'?'active':'' ?>"><i class="bi bi-clipboard-data"></i> Monitoring Riwayat</a>
      <?php else: ?>
        <a href="<?= e(url('petugas/dashboard.php')) ?>" class="<?= $active_menu==='dashboard'?'active':'' ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="<?= e(url('shared/deteksi.php')) ?>" class="<?= $active_menu==='deteksi'?'active':'' ?>"><i class="bi bi-camera"></i> Deteksi Penyakit</a>
        <a href="<?= e(url('shared/riwayat.php')) ?>" class="<?= $active_menu==='riwayat'?'active':'' ?>"><i class="bi bi-clock-history"></i> Riwayat Saya</a>
        <a href="<?= e(url('petugas/profil.php')) ?>" class="<?= $active_menu==='profil'?'active':'' ?>"><i class="bi bi-person-gear"></i> Profil</a>
      <?php endif; ?>
      <div class="sidebar-divider"></div>
      <a href="<?= e(url('auth/logout.php')) ?>"><i class="bi bi-box-arrow-right"></i> Keluar</a>
    </nav>
    <div class="sidebar-footer">&copy; <?= date('Y') ?> YOLOv8 Detection</div>
  </aside>
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
  <div class="main-content">
    <header class="topbar">
      <button class="btn-toggle-sidebar" id="btnToggleSidebar" type="button"><i class="bi bi-list"></i></button>
      <h1 class="topbar-title"><?= e($page_title) ?></h1>
      <div class="topbar-right d-none d-md-flex align-items-center gap-2"><small class="text-muted"><i class="bi bi-calendar3"></i> <?= date('d/m/Y') ?></small></div>
    </header>
    <main class="content-area">
      <?= render_flashes() ?>
