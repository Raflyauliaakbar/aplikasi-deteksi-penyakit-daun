<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('Petugas');

$u   = current_user();
$pdo = db();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM history_deteksi WHERE id_user = ?');
$stmt->execute([$u['id_user']]);
$total = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM history_deteksi WHERE id_user = ? AND DATE(tgl_deteksi) = CURDATE()');
$stmt->execute([$u['id_user']]);
$hari_ini = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT h.*, p.nama_penyakit
    FROM history_deteksi h
    JOIN penyakit p ON h.id_penyakit = p.id_penyakit
    WHERE h.id_user = ?
    ORDER BY h.tgl_deteksi DESC
    LIMIT 5
");
$stmt->execute([$u['id_user']]);
$recent = $stmt->fetchAll();

$page_title  = 'Dashboard';
$active_menu = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="card mb-4" style="border-left: 5px solid var(--brand);">
  <div class="card-body">
    <h4 class="mb-1">Halo, <?= e($u['nama_lengkap']) ?> <i class="bi bi-hand-thumbs-up-fill text-warning"></i></h4>
    <p class="text-muted mb-0">Selamat bekerja. Pilih menu di bawah untuk memulai deteksi.</p>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6">
    <div class="stat-card bg-brand">
      <i class="bi bi-camera-fill fs-3"></i>
      <h3><?= $total ?></h3>
      <div class="label">Total Deteksi Saya</div>
    </div>
  </div>
  <div class="col-6">
    <div class="stat-card bg-accent">
      <i class="bi bi-calendar-check fs-3"></i>
      <h3><?= $hari_ini ?></h3>
      <div class="label">Deteksi Hari Ini</div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <a href="<?= e(url('petugas/deteksi.php')) ?>" class="btn btn-primary btn-lg-action w-100">
      <i class="bi bi-camera fs-4 d-block mb-1"></i>
      Mulai Deteksi
    </a>
  </div>
  <div class="col-md-6">
    <a href="<?= e(url('petugas/riwayat.php')) ?>" class="btn btn-outline-primary btn-lg-action w-100">
      <i class="bi bi-clock-history fs-4 d-block mb-1"></i>
      Lihat Riwayat
    </a>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-clock-history"></i> 5 Deteksi Terakhir</span>
    <a href="<?= e(url('petugas/riwayat.php')) ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr><th>Waktu</th><th>Hasil</th><th class="text-end">Akurasi</th></tr>
      </thead>
      <tbody>
        <?php if (!$recent): ?>
          <tr><td colspan="3" class="text-center text-muted py-4">
            Belum ada riwayat. <a href="<?= e(url('petugas/deteksi.php')) ?>">Mulai deteksi pertama Anda</a>.
          </td></tr>
        <?php else: foreach ($recent as $r): ?>
          <tr>
            <td><?= e(format_tgl($r['tgl_deteksi'])) ?></td>
            <td><?= penyakit_badge($r['nama_penyakit']) ?></td>
            <td class="text-end fw-semibold"><?= e(format_akurasi((float) $r['akurasi'])) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
