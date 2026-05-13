<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('Admin');

$pdo = db();

// Statistik ringkas
$total_petugas  = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Petugas'")->fetchColumn();
$total_deteksi  = (int) $pdo->query("SELECT COUNT(*) FROM history_deteksi")->fetchColumn();
$total_penyakit = (int) $pdo->query("SELECT COUNT(*) FROM penyakit")->fetchColumn();
$deteksi_hariini = (int) $pdo->query("SELECT COUNT(*) FROM history_deteksi WHERE DATE(tgl_deteksi) = CURDATE()")->fetchColumn();

// Deteksi terbaru
$recent = $pdo->query("
    SELECT h.*, u.nama_lengkap, p.nama_penyakit
    FROM history_deteksi h
    JOIN users u ON h.id_user = u.id_user
    JOIN penyakit p ON h.id_penyakit = p.id_penyakit
    ORDER BY h.tgl_deteksi DESC
    LIMIT 8
")->fetchAll();

// Distribusi per penyakit
$dist = $pdo->query("
    SELECT p.nama_penyakit, COUNT(h.id_deteksi) AS jml
    FROM penyakit p
    LEFT JOIN history_deteksi h ON h.id_penyakit = p.id_penyakit
    GROUP BY p.id_penyakit
    ORDER BY jml DESC
")->fetchAll();

$page_title = 'Dashboard Admin';
$active_menu = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card bg-brand">
      <i class="bi bi-people fs-3"></i>
      <h3><?= $total_petugas ?></h3>
      <div class="label">Total Petugas</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card bg-info">
      <i class="bi bi-camera-fill fs-3"></i>
      <h3><?= $total_deteksi ?></h3>
      <div class="label">Total Deteksi</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card bg-accent">
      <i class="bi bi-calendar-check fs-3"></i>
      <h3><?= $deteksi_hariini ?></h3>
      <div class="label">Deteksi Hari Ini</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card bg-danger">
      <i class="bi bi-journal-medical fs-3"></i>
      <h3><?= $total_penyakit ?></h3>
      <div class="label">Kelas Penyakit</div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-1"></i> Deteksi Terbaru</span>
        <a href="<?= e(url('admin/monitor_riwayat.php')) ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Waktu</th>
              <th>Petugas</th>
              <th>Hasil</th>
              <th class="text-end">Akurasi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$recent): ?>
              <tr><td colspan="4" class="text-center text-muted py-4">Belum ada data deteksi.</td></tr>
            <?php else: foreach ($recent as $r): ?>
              <tr>
                <td><?= e(format_tgl($r['tgl_deteksi'])) ?></td>
                <td><?= e($r['nama_lengkap']) ?></td>
                <td><?= penyakit_badge($r['nama_penyakit']) ?></td>
                <td class="text-end fw-semibold"><?= e(format_akurasi((float) $r['akurasi'])) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">
        <i class="bi bi-bar-chart-line me-1"></i> Distribusi per Penyakit
      </div>
      <div class="card-body">
        <?php if (!$dist): ?>
          <p class="text-muted">Belum ada data.</p>
        <?php else:
          $max = max(array_map(fn($d) => (int) $d['jml'], $dist)) ?: 1;
          foreach ($dist as $d):
            $pct = ($d['jml'] / $max) * 100;
        ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span><?= penyakit_badge($d['nama_penyakit']) ?></span>
              <span class="fw-semibold"><?= (int) $d['jml'] ?></span>
            </div>
            <div class="progress" style="height: 8px;">
              <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
